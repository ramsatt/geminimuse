import { Component, Input, Output, EventEmitter, signal } from '@angular/core';
import { CommonModule } from '@angular/common';

export interface FilterState {
  aiTool: string;
  sort: string;
}

@Component({
  selector: 'app-filter-sheet',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './filter-sheet.html',
})
export class FilterSheetComponent {
  @Input() initialState!: FilterState;
  @Output() apply = new EventEmitter<FilterState>();
  @Output() dismiss = new EventEmitter<void>();

  readonly aiTools = ['All', 'Midjourney', 'DALL-E 3', 'Stable Diffusion', 'Firefly'];
  readonly sortOptions = ['Newest', 'Most Copied', 'Random'];

  selectedAiTool = signal<string>('All');
  selectedSort    = signal<string>('Newest');

  ngOnInit() {
    this.selectedAiTool.set(this.initialState.aiTool);
    this.selectedSort.set(this.initialState.sort);
  }

  reset() {
    this.selectedAiTool.set('All');
    this.selectedSort.set('Newest');
  }

  applyFilters() {
    this.apply.emit({
      aiTool: this.selectedAiTool(),
      sort:   this.selectedSort(),
    });
  }

  hasActiveFilters(): boolean {
    return this.selectedAiTool() !== 'All' || this.selectedSort() !== 'Newest';
  }
}
