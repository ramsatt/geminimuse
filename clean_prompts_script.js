const fs = require('fs');
const path = require('path');

const filePath = path.join('src', 'assets', 'data', 'prompts.json');

try {
    const rawData = fs.readFileSync(filePath, 'utf8');
    const prompts = JSON.parse(rawData);

    const cleanedPrompts = prompts.map(item => {
        let text = item.prompt;

        if (!text) return item;

        // 1. Split into lines
        let lines = text.split('\n');

        // 2. Filter out "garbage" lines
        // Heuristic: A line is valid if it contains at least one sequence of 3+ letters.
        // Also remove lines that look like OCR noise (mostly symbols)
        const textLines = lines.filter(line => {
            const trimmed = line.trim();
            if (trimmed.length < 2) return false;
            
            // Check for valid words (simple check)
            const words = trimmed.match(/[a-zA-Z]{3,}/g);
            if (!words) return false; // No words of 3+ letters

            // Check symbol density
            const symbols = trimmed.replace(/[a-zA-Z0-9\s]/g, '').length;
            const letters = trimmed.replace(/[^a-zA-Z]/g, '').length;
            
            // If symbols more than letters and line is not super long (longer lines with punctuation might be ok)
            if (symbols > letters && letters < 10) return false;

            return true;
        });

        text = textLines.join(' ');

        // 3. Remove specific unwanted prefix/suffix patterns (case insensitive)
        const patternsToRemove = [
            /NEED MORE PROMPT\?.*?prompts/gi,
            /Go to my Telegram.*?button/gi,
            /Prompts?(\s|:|-|>>>)+/gi,
            /Step \d+:.*?prompt/gi,
            /Step \d+:/gi,
            /Google Gemini Prompt/gi,
            /Midjourney Prompt/gi,
            /Follow:?.*$/gim, // Remove "Follow..." to end of string usually
            /@[\w\d_.]+/g, // Remove handles like @creagraphix_design
            /visit button.*?prompts/gi,
            /nanobanana/gi,
            /nano banana/gi,
            /PROVIPT For Nano Banana/gi,
            /PROMPT For Nano Banana/gi,
            /Create on ultra-reaistic/gi, // Typo fix/remove header if it looks like one
            /#\w+/g, // Remove hashtags
            /Open \+ Gemini.*?Prompt/gi, // ID 71
            /Paste the Prompt/gi, // ID 71
            /PROVIPT/gi,
            /step\d+\w+/gi,
            /PROMPT/gi, // General PROMPT removal if not caught before
        ];

        patternsToRemove.forEach(pattern => {
            text = text.replace(pattern, ' ');
        });
        
        // 4. Clean up "Start" phrases that often linger
        // Often the real prompt starts after a colon or similar
        // But we already removed "Prompt:", so let's just trim
        
        // 5. General cleanup
        text = text.replace(/\s+/g, ' ').trim();

        // 6. Fix specific typos or artifacts common in OCR if possible (hard to generalize)
        // e.g. "reaistic" -> "realistic"
        text = text.replace(/reaistic/g, 'realistic');
        text = text.replace(/portait/g, 'portrait');
        
        // Update the item
        item.prompt = text;
        return item;
    });

    fs.writeFileSync(path.join('src', 'assets', 'data', 'prompts.json'), JSON.stringify(cleanedPrompts, null, 2), 'utf8');
    console.log('Successfully wrote to prompts.json');

} catch (err) {
    console.error('Error:', err);
}
