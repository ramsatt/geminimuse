import { Component, computed, signal, HostListener, OnInit, OnDestroy, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ScrollingModule } from '@angular/cdk/scrolling';
import { Router } from '@angular/router';
import { DataService } from '../../core/services/data.service';
import { AdmobService } from '../../core/services/admob.service';
import { FavoritesService } from '../../core/services/favorites.service';
import { Prompt } from '../../core/models/prompt.model';
import { TourComponent } from '../../components/tour/tour';
import { PromptCardComponent } from '../../components/prompt-card/prompt-card';
import { FilterSheetComponent, FilterState } from '../../components/filter-sheet/filter-sheet';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, ScrollingModule, TourComponent, PromptCardComponent, FilterSheetComponent],
  templateUrl: './home.html',
  styleUrls: ['./home.css']
})
export class HomeComponent implements OnInit, OnDestroy {
  private router = inject(Router);
  private admobService = inject(AdmobService);
  private favoritesService = inject(FavoritesService);
  private viewCount = 0;

  images  = computed(() => this.dataService.items());
  loading = computed(() => !this.dataService.loaded());

  // Tour
  showTour = signal<boolean>(false);

  // Filter sheet visibility
  showFilterSheet = signal<boolean>(false);

  // Search & Filter
  searchQuery       = signal<string>('');
  activeCategory    = signal<string>('All');
  activeAiTool      = signal<string>('All');
  activeSort        = signal<string>('Newest');
  showFavoritesOnly = signal<boolean>(false);

  readonly categories = ['All', 'Portrait', 'Cinematic', 'Fantasy', 'Anime', 'Street', 'Nature', 'Sci-Fi'];

  // New This Week
  newPrompts = computed(() => this.dataService.newPrompts());

  // Badge count on filter icon
  activeFilterCount = computed(() => {
    let n = 0;
    if (this.activeAiTool() !== 'All') n++;
    if (this.activeSort() !== 'Newest') n++;
    return n;
  });

  // Filtered + sorted list
  allFilteredImages = computed(() => {
    const query    = this.searchQuery().toLowerCase();
    const category = this.activeCategory();
    const tool     = this.activeAiTool();
    const sort     = this.activeSort();
    const favOnly  = this.showFavoritesOnly();
    const all      = this.images();
    const favIds   = this.favoritesService.favorites();

    let result = all.filter(img => {
      if (favOnly && !favIds.includes(img.id)) return false;

      if (tool !== 'All' && img.ai_tools && img.ai_tools.length > 0) {
        const toolKey = tool.toLowerCase().replace(/[- ]/g, '-') as any;
        if (!img.ai_tools.includes(toolKey)) return false;
      }

      if (category !== 'All') {
        const pl = img.prompt.toLowerCase();
        if (category === 'Portrait'  && !pl.includes('portrait') && !pl.includes('face') && !pl.includes('man') && !pl.includes('woman')) return false;
        if (category === 'Cinematic' && !pl.includes('cinematic') && !pl.includes('movie')) return false;
        if (category === 'Fantasy'   && !pl.includes('fantasy') && !pl.includes('magic')) return false;
        if (category === 'Anime'     && !pl.includes('anime') && !pl.includes('manga')) return false;
        if (category === 'Street'    && !pl.includes('street') && !pl.includes('urban')) return false;
        if (!['Portrait','Cinematic','Fantasy','Anime','Street'].includes(category)) {
          if (!pl.includes(category.toLowerCase())) return false;
        }
      }

      if (query) {
        return img.prompt.toLowerCase().includes(query) ||
          (img.source && img.source.toLowerCase().includes(query));
      }
      return true;
    });

    if (sort === 'Most Copied') {
      result = [...result].sort((a, b) => (b.copy_count ?? 0) - (a.copy_count ?? 0));
    } else if (sort === 'Random') {
      result = [...result].sort(() => Math.random() - 0.5);
    }

    return result;
  });

  displayLimit  = signal<number>(20);
  visibleImages = computed(() => this.allFilteredImages().slice(0, this.displayLimit()));
  hasMoreItems  = computed(() => this.displayLimit() < this.allFilteredImages().length);

  columnCount = signal<number>(2);
  gridRows = computed(() => {
    const cols = this.columnCount();
    const rows: Prompt[][] = [];
    const imgs = this.visibleImages();
    for (let i = 0; i < imgs.length; i += cols) rows.push(imgs.slice(i, i + cols));
    return rows;
  });

  favoritesCount = computed(() => this.favoritesService.count());

  constructor(public dataService: DataService) {
    if (!localStorage.getItem('app_tour_seen_v1')) this.showTour.set(true);
  }

  onScroll(index: number) {
    if (index + 5 >= this.gridRows().length && this.displayLimit() < this.allFilteredImages().length) {
      this.displayLimit.update(l => l + 16);
    }
  }

  onTourComplete() {
    this.showTour.set(false);
    localStorage.setItem('app_tour_seen_v1', 'true');
  }

  ngOnInit()    { this.updateColumnCount(); this.admobService.showBanner(); }
  ngOnDestroy() { this.admobService.removeBanner(); }

  @HostListener('window:resize')
  onResize() { this.updateColumnCount(); }

  updateColumnCount() {
    const w = window.innerWidth;
    if      (w >= 1440) this.columnCount.set(5);
    else if (w >= 1024) this.columnCount.set(4);
    else if (w >= 768)  this.columnCount.set(3);
    else                this.columnCount.set(2);
  }

  setCategory(cat: string) {
    this.activeCategory.set(cat);
    this.showFavoritesOnly.set(false);
    this.displayLimit.set(20);
  }

  toggleFavorites() {
    this.showFavoritesOnly.update(v => !v);
    this.activeCategory.set('All');
    this.displayLimit.set(20);
  }

  updateSearch(e: Event) {
    this.searchQuery.set((e.target as HTMLInputElement).value);
    this.displayLimit.set(20);
  }

  clearSearch() {
    this.searchQuery.set('');
    this.displayLimit.set(20);
  }

  openFilterSheet()  { this.showFilterSheet.set(true); }
  closeFilterSheet() { this.showFilterSheet.set(false); }

  onFiltersApplied(state: FilterState) {
    this.activeAiTool.set(state.aiTool);
    this.activeSort.set(state.sort);
    this.displayLimit.set(20);
    this.closeFilterSheet();
  }

  get filterSheetState(): FilterState {
    return { aiTool: this.activeAiTool(), sort: this.activeSort() };
  }

  async openImage(prompt: Prompt) {
    this.viewCount++;
    if (this.viewCount % this.admobService.INTERSTITIAL_THRESHOLD === 0) {
      await this.admobService.showInterstitial();
    }
    this.router.navigate(['/prompt', prompt.id]);
  }
}
