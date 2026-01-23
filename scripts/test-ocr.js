const Tesseract = require('tesseract.js');
const path = require('path');

const imagePath = path.join(__dirname, '../src/assets/gemini/gemini_prompt_main.jpg');

console.log(`Processing ${imagePath}...`);

Tesseract.recognize(
  imagePath,
  'eng',
  { logger: m => console.log(m) }
).then(({ data: { text } }) => {
  console.log('--- Extracted Text ---');
  console.log(text);
  console.log('----------------------');
});
