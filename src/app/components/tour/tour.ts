import { Component, EventEmitter, Output, signal } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-tour',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './tour.html',
})
export class TourComponent {
  @Output() complete = new EventEmitter<void>();
  
  currentStep = signal(0);
  
  steps = [
    {
      title: 'Welcome to GeminiMuse',
      description: 'Your personal AI prompt gallery — curated, multilingual, and ready to inspire.',
      image: '✨',
      gradient: 'from-violet-600 to-indigo-700',
      accent: '#FFD60A',
    },
    {
      title: 'Browse & Discover',
      description: 'Explore hundreds of AI image prompts across portraits, cinematic scenes, fantasy, anime, and more.',
      image: '🎨',
      gradient: 'from-indigo-600 to-blue-600',
      accent: '#FFFFFF',
    },
    {
      title: 'Multilingual Magic',
      description: 'Switch prompts to Tamil, Hindi, Telugu, Kannada, Malayalam or English with a single tap.',
      image: '🌐',
      gradient: 'from-blue-600 to-cyan-600',
      accent: '#FFFFFF',
    },
    {
      title: 'Copy & Create',
      description: 'Found the perfect prompt? Copy it instantly and start generating stunning AI artwork.',
      image: '🚀',
      gradient: 'from-orange-500 to-pink-600',
      accent: '#FFFFFF',
    }
  ];

  next() {
    if (this.currentStep() < this.steps.length - 1) {
      this.currentStep.update(v => v + 1);
    } else {
      this.finish();
    }
  }

  skip() {
    this.finish();
  }

  finish() {
    this.complete.emit();
  }
}
