const fs = require('fs');
const path = require('path');
const Tesseract = require('tesseract.js');

const assetsDir = path.join(__dirname, '../src/assets/gemini');
const outputFile = path.join(__dirname, '../src/assets/data/prompts.json');

// Helper to clean up OCR text
function cleanText(text) {
  if (!text) return "";
  let cleaned = text.trim();
  // Remove common OCR artifacts or headers if they appear constantly (adjust as needed)
  cleaned = cleaned.replace(/^Prompt[:\s]*/i, ''); 
  cleaned = cleaned.replace(/\n/g, ' '); // key: join lines to make single paragraph
  cleaned = cleaned.replace(/\s+/g, ' '); // collapse multiple spaces
  return cleaned;
}

// Main execution
(async () => {
  try {
    if (!fs.existsSync(assetsDir)) {
      console.error(`Directory not found: ${assetsDir}`);
      process.exit(1);
    }

    const files = fs.readdirSync(assetsDir).filter(file => {
      const lower = file.toLowerCase();
      return lower.endsWith('.jpg') || lower.endsWith('.png');
    });

    console.log(`Found ${files.length} images. Starting OCR...`);
    
    const results = [];
    
    // Initialize worker once to speed up re-use
    const worker = await Tesseract.createWorker('eng');

    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const filePath = path.join(assetsDir, file);
      
      console.log(`[${i + 1}/${files.length}] Processing ${file}...`);
      
      try {
        const { data: { text } } = await worker.recognize(filePath);
        const cleaned = cleanText(text);
        
        // If OCR fails or returns empty, fallback to a placeholder maybe? 
        // Or just keep the empty prompt so user knows.
        
        results.push({
          id: i + 1,
          filename: file,
          url: `assets/gemini/${file}`,
          prompt: cleaned || "[No text detected]"
        });

      } catch (err) {
        console.error(`Error processing ${file}:`, err);
        results.push({
          id: i + 1,
          filename: file,
          url: `assets/gemini/${file}`,
          prompt: "[Error extracting prompt]"
        });
      }
    }

    await worker.terminate();

    fs.writeFileSync(outputFile, JSON.stringify(results, null, 2));
    console.log(`Successfully generated ${results.length} prompts in ${outputFile}`);

  } catch (err) {
    console.error('Fatal error:', err);
  }
})();
