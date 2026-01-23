const fs = require('fs');
const path = require('path');

const promptsFile = path.join(__dirname, '../src/assets/data/prompts.json');

// The scraped prompts from Good Morning Education
const newPrompts = [
  "Create a cinematic portrait of a lone character under dim lighting, soft shadows on the face, shallow depth of field, dramatic mood, film still quality, ultra-realistic textures, dark cinematic color grading.",
  "Generate a close-up cinematic portrait during golden hour, warm sunlight, emotional expression, natural skin tones, soft background blur, cinematic film lighting, professional photography style.",
  "Produce a black-and-white cinematic portrait inspired by classic noir films, high contrast lighting, deep shadows, intense gaze, dramatic composition, timeless atmosphere.",
  "Create a futuristic cinematic portrait with neon lights, cyberpunk city background, reflective surfaces, dramatic lighting, bold colors, sharp focus, cinematic depth.",
  "Generate a cinematic portrait inspired by 1970s film photography, grain texture, muted colors, natural lighting, authentic retro mood.",
  "Produce a cinematic portrait set in rain, reflections on skin, moody lighting, emotional expression, urban night background, shallow depth of field.",
  "Create a romantic cinematic portrait with soft lighting, pastel color grading, intimate mood, gentle expression, professional film still quality.",
  "Generate a cinematic hero portrait, dramatic lighting, strong shadows, powerful pose, cinematic background, high realism.",
  "Produce a cinematic portrait using soft window light, realistic skin texture, minimal background, emotional depth, film-style composition.",
  "Create a fantasy-inspired cinematic portrait, mystical lighting, ethereal atmosphere, detailed textures, cinematic storytelling.",
  "Generate a cinematic portrait in a desert environment, harsh sunlight, dramatic shadows, warm tones, intense expression.",
  "Produce a cinematic portrait in snowy surroundings, cool color grading, visible breath, emotional intensity, shallow focus.",
  "Create a cinematic studio portrait with controlled lighting, dark backdrop, professional color grading, high-end photography look.",
  "Generate a cinematic portrait with raw realism, natural lighting, candid expression, film documentary aesthetic.",
  "Produce a cinematic portrait mid-motion, dynamic pose, dramatic lighting, blurred background, action film feel.",
  "Create a glamorous cinematic portrait inspired by old Hollywood, elegant lighting, smooth textures, timeless beauty.",
  "Generate a cinematic street portrait, urban background, natural shadows, emotional storytelling, realistic atmosphere.",
  "Produce a cinematic portrait with painterly textures, soft lighting, artistic mood, fine-art film aesthetic.",
  "Create an extreme close-up cinematic portrait capturing tears, detailed skin texture, dramatic lighting, intense emotion.",
  "Generate a minimalist cinematic portrait, clean background, subtle lighting, strong expression, professional film composition."
];

try {
  let existingData = [];
  if (fs.existsSync(promptsFile)) {
    existingData = JSON.parse(fs.readFileSync(promptsFile, 'utf8'));
  }

  // Get the max ID
  let maxId = existingData.reduce((max, item) => Math.max(max, item.id), 0);

  // We only downloaded one image for these prompts, so we'll reuse it for all of them
  // In a real scenario, we'd want unique images, but the blog post was text-heavy.
  const imageFilename = "gm_cinematic_main.webp";
  
  const additionalEntries = newPrompts.map((prompt, index) => ({
    id: maxId + index + 1,
    filename: imageFilename,
    url: `assets/gemini/${imageFilename}`, 
    prompt: prompt,
    source: "Good Morning Education"
  }));

  const mergedData = [...existingData, ...additionalEntries];

  fs.writeFileSync(promptsFile, JSON.stringify(mergedData, null, 2));
  console.log(`Successfully added ${additionalEntries.length} new prompts. Total: ${mergedData.length}`);

} catch (err) {
  console.error('Error updating prompts:', err);
}
