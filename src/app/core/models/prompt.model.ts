export type PromptCategory =
  | 'all' | 'portrait' | 'cinematic' | 'fantasy'
  | 'anime' | 'street' | 'nature' | 'sci-fi'
  | 'architecture' | 'food' | 'wildlife' | 'abstract'
  | 'festive' | 'fashion' | 'macro';

export type AiTool =
  | 'midjourney' | 'dall-e-3' | 'stable-diffusion' | 'firefly' | 'ideogram';

export type Difficulty = 'beginner' | 'intermediate' | 'advanced';

export interface Prompt {
  id: number;
  filename: string;
  url: string;
  prompt: string;
  prompt_tn?: string;   // Tamil
  prompt_ml?: string;   // Malayalam
  prompt_te?: string;   // Telugu
  prompt_kn?: string;   // Kannada
  prompt_hi?: string;   // Hindi
  source?: string;

  // Extended fields (optional — populated progressively)
  category?: PromptCategory;
  difficulty?: Difficulty;
  ai_tools?: AiTool[];
  is_new?: boolean;        // added in the last 7 days
  is_premium?: boolean;    // Pro-only prompt
  copy_count?: number;     // social proof counter
  tags?: string[];
  pack_id?: string;        // seasonal pack grouping
  added_date?: string;     // ISO date string
}

// Keep legacy alias for backward compatibility during migration
export type GeminiRef = Prompt;
