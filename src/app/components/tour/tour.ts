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
      title: 'Welcome to Gemini Muse',
      description: 'Discover a gallery of high-fidelity image prompts curated just for you.',
      image: '✨' // Using emoji as placeholder for now, or could be a graphic
    },
    {
      title: 'Multilingual Magic',
      description: 'Access prompts in your native language. Switch between English, Hindi, Tamil, and more with a tap.',
      image: '🌐'
    },
    {
      title: 'One-Tap Creation',
      description: 'Found a style you love? Copy the prompt instantly and start creating masterpieces.',
      image: '🚀'
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
