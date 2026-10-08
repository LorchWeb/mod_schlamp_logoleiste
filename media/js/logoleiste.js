(() => {
    'use strict';

    const init = () => document.querySelectorAll('[data-schlamp-logos]').forEach((root) => {
        if (root.dataset.initialized) return;
        root.dataset.initialized = 'true';

        const viewport = root.querySelector('.schlamp-logos__viewport');
        const track = root.querySelector('.schlamp-logos__track');
        const group = root.querySelector('.schlamp-logos__group');
        if (group.children.length < 2 || !window.ResizeObserver) return;

        const clones = [];
        const resize = () => {
            const styles = getComputedStyle(viewport);
            const visible = Math.max(1, Number.parseInt(styles.getPropertyValue('--sl-visible'), 10) || 1);
            const gap = Number.parseFloat(styles.getPropertyValue('--sl-gap')) || 0;
            const fixedWidth = Number.parseFloat(styles.getPropertyValue('--sl-width')) || 0;
            const width = fixedWidth > 0
                ? Math.max(1, Math.min(fixedWidth, viewport.clientWidth))
                : Math.max(1, (viewport.clientWidth - gap * (visible - 1)) / visible);
            root.style.setProperty('--sl-item-width', `${width}px`);
            // A short sequence may need several repeats to cover a wide viewport.
            const cycleWidth = group.children.length * (width + gap);
            const needed = Math.max(1, Math.ceil(viewport.clientWidth / cycleWidth));
            while (clones.length > needed) clones.pop().remove();
            while (clones.length < needed) {
                const clone = group.cloneNode(true);
                clone.classList.add('schlamp-logos__clone');
                clone.setAttribute('aria-hidden', 'true');
                clone.querySelectorAll('a').forEach((link) => link.setAttribute('tabindex', '-1'));
                track.append(clone);
                clones.push(clone);
            }
            root.style.setProperty('--sl-end', `${-100 / (clones.length + 1)}%`);
        };
        resize();
        new ResizeObserver(resize).observe(viewport);
        root.classList.add('is-running');

        // Apply the static list before Tab moves focus to an offscreen original.
        root.addEventListener('keydown', (event) => {
            if (event.key === 'Tab') root.classList.add('is-browsing');
        });
        group.addEventListener('focusin', (event) => {
            if (event.target.matches(':focus-visible')) root.classList.add('is-browsing');
        });
        root.addEventListener('focusout', () => {
            queueMicrotask(() => {
                if (!root.contains(document.activeElement)) root.classList.remove('is-browsing');
            });
        });

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(([entry]) => root.classList.toggle('is-offscreen', !entry.isIntersecting)).observe(root);
        }
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
