import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home';

export const routes: Routes = [
  { path: '', component: HomeComponent },
  { 
    path: 'prompt/:id', 
    loadComponent: () => import('./pages/prompt-details/prompt-details').then(m => m.PromptDetailsPage) 
  },
  { 
    path: 'privacy-policy', 
    loadComponent: () => import('./pages/privacy-policy/privacy-policy').then(m => m.PrivacyPolicyPage) 
  },
  { path: '**', redirectTo: '' }
];
