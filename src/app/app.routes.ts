import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home';

export const routes: Routes = [
  { path: '', component: HomeComponent },
  { 
    path: 'prompt/:id', 
    loadComponent: () => import('./pages/prompt-details/prompt-details').then(m => m.PromptDetailsPage) 
  },
  { path: '**', redirectTo: '' }
];
