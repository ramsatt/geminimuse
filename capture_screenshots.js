const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
  const browser = await puppeteer.launch({
    headless: "new",
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  const page = await browser.newPage();
  
  // Set viewport to a typical high-end phone (Pixel 4/5 XL ish) - 9:16 aspect ratio
  // 1080 x 1920
  await page.setViewport({ width: 1080, height: 1920, deviceScaleFactor: 1 });

  console.log('Navigating to Home Page...');
  await page.goto('http://localhost:4200', { waitUntil: 'networkidle0' });
  
  // Wait for content to load
  await page.waitForSelector('img'); 
  // Custom delay to ensure images render
  await new Promise(r => setTimeout(r, 3000));

  console.log('Capturing Home Page...');
  await page.screenshot({ path: 'screenshots/1_home_screen.png' });

  // Click on the first image card to go to details
  // Assuming the structure from previous files: .cursor-pointer or inside the grid
  console.log('Navigating to Details Page...');
  
  // Try to find a prompt card. Based on code it might be an 'img' inside a 'div' with click handler
  // Let's click the first image that is not the logo/header
  // We can use a selector for the loop items we saw in home (not seen home code but guessing)
  // Or just click the first img that looks like a prompt item.
  
  // Let's try to click a generic element that looks like a card
  const clicked = await page.evaluate(() => {
    const images = document.querySelectorAll('img');
    for (let img of images) {
      // Avoid header/logo if possible, usually prompt images are larger or in a grid
      if (img.src.includes('assets/images/')) {
        img.click();
        return true;
      }
    }
    return false;
  });

  if (clicked) {
    await page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => new Promise(r => setTimeout(r, 2000))); // Wait in case navigation is SPA
    
    await new Promise(r => setTimeout(r, 2000)); // Wait for details animation

    console.log('Capturing Details Page...');
    await page.screenshot({ path: 'screenshots/2_details_screen.png' });
    
    // Add more screenshots if needed, e.g. scrolled down
    // window.scrollTo(0, 500);
    // await new Promise(r => setTimeout(r, 1000));
    // await page.screenshot({ path: 'screenshots/3_details_scrolled.png' });
  } else {
    console.error('Could not find an image to click for details page.');
  }

  await browser.close();
  console.log('Screenshots captured in /screenshots folder.');
})();
