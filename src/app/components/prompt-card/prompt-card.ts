import { Component, Input, Output, EventEmitter, inject, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Prompt } from '../../core/models/prompt.model';
import { FavoritesService } from '../../core/services/favorites.service';

@Component({
  selector: 'app-prompt-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './prompt-card.html',
})
export class PromptCardComponent {
  @Input({ required: true }) prompt!: Prompt;
  @Output() cardClick = new EventEmitter<Prompt>();

  private favoritesService = inject(FavoritesService);

  get isFavorite(): boolean {
    return this.favoritesService.isFavorite(this.prompt.id);
  }

  onCardClick() {
    this.cardClick.emit(this.prompt);
  }

  toggleFavorite(event: Event) {
    event.stopPropagation();
    this.favoritesService.toggle(this.prompt.id);
  }
}
