import { Component, computed, signal, HostListener, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DataService, GeminiRef } from '../../services/data';
import { ScrollingModule } from '@angular/cdk/scrolling';
import { Router } from '@angular/router';
import { TourComponent } from '../../components/tour/tour';
import { AdmobService } from '../../services/admob/admob.service';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, ScrollingModule, TourComponent],
  templateUrl: './home.html',
  styleUrls: ['./home.css']
})
export class HomeComponent implements OnInit {
  private router = inject(Router);
  private admobService = inject(AdmobService);
  private viewCount = 0;
  
  images = computed(() => this.dataService.items());
  loading = computed(() => !this.dataService.loaded());
  
  // Tour State
  showTour = signal<boolean>(false);

  // Search & Filter
  searchQuery = signal<string>('');
  activeCategory = signal<string>('All');
  readonly categories = ['All', 'Portrait', 'Cinematic', 'Fantasy', 'Anime', 'Street', 'Nature', 'Sci-Fi'];

  // Filter Logic
  allFilteredImages = computed(() => {
    const query = this.searchQuery().toLowerCase();
    const category = this.activeCategory();
    const all = this.images();

    return all.filter(img => {
      // 1. Filter by category
      if (category !== 'All') {
        const promptLower = img.prompt.toLowerCase();
        if (category === 'Portrait' && !promptLower.includes('portrait') && !promptLower.includes('face') && !promptLower.includes('man') && !promptLower.includes('woman')) return false;
        if (category === 'Cinematic' && !promptLower.includes('cinematic') && !promptLower.includes('movie')) return false;
        if (category === 'Fantasy' && !promptLower.includes('fantasy') && !promptLower.includes('magic')) return false;
        if (category === 'Anime' && !promptLower.includes('anime') && !promptLower.includes('manga')) return false;
        if (category === 'Street' && !promptLower.includes('street') && !promptLower.includes('urban')) return false;
        if (category !== 'Portrait' && category !== 'Cinematic' && category !== 'Fantasy' && category !== 'Anime' && category !== 'Street') {
           if (!promptLower.includes(category.toLowerCase())) return false;
        }
      }

      // 2. Filter by search query
      if (query) {
        return img.prompt.toLowerCase().includes(query) || 
               (img.source && img.source.toLowerCase().includes(query));
      }
      
      return true;
    });
  });

  // Infinite Scroll Logic
  displayLimit = signal<number>(20); // Increased from 10 to fill screen better
  
  visibleImages = computed(() => {
    return this.allFilteredImages().slice(0, this.displayLimit());
  });

  hasMoreItems = computed(() => {
    return this.displayLimit() < this.allFilteredImages().length;
  });

  // Virtual Scroll Grid Logic
  columnCount = signal<number>(2);
  gridRows = computed(() => {
    const displayedImages = this.visibleImages();
    const cols = this.columnCount();
    const rows = [];
    for (let i = 0; i < displayedImages.length; i += cols) {
      rows.push(displayedImages.slice(i, i + cols));
    }
    return rows;
  });

  constructor(public dataService: DataService) {
    // Check local storage for tour
    const hasSeenTour = localStorage.getItem('app_tour_seen_v1');
    if (!hasSeenTour) {
      this.showTour.set(true);
    }
  }

  onScroll(index: number) {
    const currentRows = this.gridRows().length;
    const buffer = 5; // Increased buffer to load earlier (was 3)
    
    if (index + buffer >= currentRows) {
       // Check if we have more items to load
       if (this.displayLimit() < this.allFilteredImages().length) {
         this.displayLimit.update(l => l + 16); // Load 16 items at once (was 8)
       }
    }
  }

  onTourComplete() {
    this.showTour.set(false);
    localStorage.setItem('app_tour_seen_v1', 'true');
  }

  ngOnInit() {
    this.updateColumnCount();
    // Show banner ad
    this.admobService.showBanner();
  }

  ngOnDestroy() {
    this.admobService.removeBanner();
  }

  @HostListener('window:resize')
  onResize() {
    this.updateColumnCount();
  }

  updateColumnCount() {
    const width = window.innerWidth;
    if (width >= 1280) { // xl
      this.columnCount.set(5);
    } else if (width >= 1024) { // lg
      this.columnCount.set(4);
    } else if (width >= 768) { // md
      this.columnCount.set(3);
    } else {
      this.columnCount.set(2);
    }
  }

  setCategory(cat: string) {
    this.activeCategory.set(cat);
    this.displayLimit.set(20); // Reset to initial count
  }

  updateSearch(e: Event) {
    const el = e.target as HTMLInputElement;
    this.searchQuery.set(el.value);
    this.displayLimit.set(20); // Reset to initial count
  }

  async openImage(image: GeminiRef) {
    this.viewCount++;
    
    // Show interstitial every 5 views
    if (this.viewCount % 5 === 0) {
      await this.admobService.showInterstitial();
    }
    
    this.router.navigate(['/prompt', image.id]);
  }
}
