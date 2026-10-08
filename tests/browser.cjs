// Run verify.php with a temporary preview directory first; pass that directory here.
const { chromium } = require('playwright');
const { pathToFileURL } = require('node:url');
const path = require('node:path');
const assert = require('node:assert/strict');

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE || undefined });
    const errors = [];
    let checks = 0;
    const check = (value, message) => { assert.ok(value, message); checks++; };
    const url = pathToFileURL(path.resolve(process.argv[2], 'index.html')).href;
    try {
        const page = await browser.newPage({ viewport: { width: 1560, height: 1100 } });
        page.on('pageerror', (error) => errors.push(error.message));
        await page.goto(url);
        await page.locator('.is-running').first().waitFor();
        const root = page.locator('.schlamp-logos').first();
        const track = root.locator('.schlamp-logos__track');
        const state = () => root.evaluate((element) => {
            const viewport = element.querySelector('.schlamp-logos__viewport');
            const groups = element.querySelectorAll('.schlamp-logos__group');
            const item = groups[0].firstElementChild;
            return {
                visible: Number.parseInt(getComputedStyle(viewport).getPropertyValue('--sl-visible'), 10),
                width: viewport.clientWidth,
                item: item.getBoundingClientRect().width,
                gap: Number.parseFloat(getComputedStyle(groups[0]).gap),
                group: groups[0].getBoundingClientRect().width,
                clone: groups[1].getBoundingClientRect().width,
                scroll: document.documentElement.scrollWidth,
                window: innerWidth,
            };
        });
        for (const [width, visible] of [[1560, 6], [800, 3], [375, 2]]) {
            await page.setViewportSize({ width, height: 1100 });
            await page.waitForTimeout(100);
            const current = await state();
            check(current.visible === visible, `Responsive count at ${width}`);
            check(Math.abs(current.item * visible + current.gap * (visible - 1) - current.width) < 1, `Exact slot widths at ${width}`);
            check(Math.abs(current.group - current.clone) < 0.1 && current.group >= current.width, `Seamless clone coverage at ${width}`);
            check(current.scroll === current.window, `No page overflow at ${width}`);
        }
        await page.setViewportSize({ width: 1560, height: 1100 });
        await page.mouse.move(1, 1);
        await page.waitForTimeout(100);
        const offset = () => track.evaluate((element) => new DOMMatrix(getComputedStyle(element).transform).m41);
        const before = await offset();
        await page.waitForTimeout(160);
        check(await offset() < before, 'Moves left');
        await root.hover();
        await page.waitForTimeout(60);
        const hoverPosition = await offset();
        await page.waitForTimeout(100);
        check(Math.abs(await offset() - hoverPosition) < 0.1, 'Hover pauses at current position');
        check(await page.locator('button').count() === 0, 'No pause buttons');
        await page.mouse.move(1, 1);
        const resumed = await offset();
        await page.waitForTimeout(100);
        check(await offset() < resumed, 'Resume continues motion');
        check(await root.locator('.schlamp-logos__clone a:not([tabindex="-1"])').count() === 0, 'Clones excluded from Tab');
        await page.locator('body > a').first().focus();
        await page.keyboard.press('Tab');
        check(await root.evaluate((element) => element.classList.contains('is-browsing')), 'Keyboard uses static original list');
        check(await track.evaluate((element) => getComputedStyle(element).animationName) === 'none', 'No animation while keyboard browsing');
        await page.locator('#end').focus();
        check(await root.evaluate((element) => !element.classList.contains('is-browsing')), 'Motion restored after leaving');

        check(await page.locator('.schlamp-logos').nth(1).evaluate((element) => getComputedStyle(element).getPropertyValue('--sl-duration').trim()) === '12s', 'Independent instance duration');
        check(await page.locator('.schlamp-logos').nth(2).locator('.schlamp-logos__clone').count() === 0, 'Single logo stays static');
        const fixed = page.locator('.schlamp-logos').nth(3);
        const narrow = page.locator('.schlamp-logos').nth(4);
        const measurements = await fixed.evaluate((element) => {
            const image = element.querySelector('img').getBoundingClientRect();
            return { width: image.width, height: image.height, total: element.getBoundingClientRect().height, background: getComputedStyle(element).backgroundColor };
        });
        check(measurements.width === 290 && measurements.height === 150, 'Image frame is exactly 290 x 150');
        check(measurements.total === 170, 'Total height is 150 + twice 10, no controls or inner padding');
        check(measurements.background === 'rgb(18, 52, 86)', 'Optional color rendered');
        check(await root.evaluate((element) => getComputedStyle(element).backgroundColor) === 'rgba(0, 0, 0, 0)', 'Default transparent');
        check(await narrow.locator('.schlamp-logos__clone').count() > 2, 'Short fixed-width list fills wide viewport');
        for (const strip of [fixed, narrow]) {
            const loop = await strip.evaluate((element) => {
                const groups = element.querySelectorAll('.schlamp-logos__group');
                const track = element.querySelector('.schlamp-logos__track');
                const groupWidth = groups[0].getBoundingClientRect().width;
                const percent = parseFloat(element.style.getPropertyValue('--sl-end'));
                return { distance: -percent / 100 * track.getBoundingClientRect().width, groupWidth, rest: (groups.length - 1) * groupWidth, viewport: element.clientWidth };
            });
            check(Math.abs(loop.distance - loop.groupWidth) < 0.1, 'Loop translates exactly one group');
            check(loop.rest >= loop.viewport, 'Clones cover viewport at loop boundary');
        }
        await page.emulateMedia({ reducedMotion: 'reduce' });
        check(await track.evaluate((element) => getComputedStyle(element).animationName) === 'none', 'Reduced motion disables scrolling');
        check(await root.locator('.schlamp-logos__clone').isHidden(), 'Reduced motion hides duplicates');
        await page.emulateMedia({ reducedMotion: 'no-preference' });
        await page.mouse.move(1, 1);
        await page.screenshot({ path: path.join(process.argv[2], 'desktop.png'), fullPage: true });
        await page.setViewportSize({ width: 375, height: 900 });
        await page.waitForTimeout(100);
        check(Math.abs((await fixed.locator('img').first().boundingBox()).width - 290) < 0.1, 'Fixed width preserved on phone');
        await page.screenshot({ path: path.join(process.argv[2], 'mobile.png'), fullPage: true });
        await page.setViewportSize({ width: 240, height: 900 });
        await page.waitForTimeout(100);
        check(Math.abs((await fixed.locator('img').first().boundingBox()).width - 240) < 0.1, 'Very narrow container safely limits frame');
        const noJs = await browser.newPage({ javaScriptEnabled: false });
        await noJs.goto(url);
        check(await noJs.locator('.schlamp-logos').first().locator('img').count() === 6, 'No-JS keeps all logos');
        check(await noJs.locator('button').count() === 0, 'No-JS has no pause controls');
        check(errors.length === 0, `No browser errors: ${errors.join(', ')}`);
        console.log(`PASS: ${checks} Chromium checks`);
    } finally {
        await browser.close();
    }
})().catch((error) => { console.error(error); process.exitCode = 1; });
