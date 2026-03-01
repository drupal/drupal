/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

((Drupal) => {
  Drupal.behaviors.ginSticky = {
    attach: () => {
      once('ginSticky', '.region-sticky-watcher').forEach(() => {
        // Watch sticky header
        const observer = new IntersectionObserver(
          ([e]) => {
            const regionSticky = document.querySelector('.region-sticky');
            regionSticky.classList.toggle('region-sticky--is-sticky', e.intersectionRatio < 1);
            regionSticky.toggleAttribute('data-offset-top', e.intersectionRatio < 1);
            Drupal.displace(true);
          },
          { threshold: [1] }
        );
        const element = document.querySelector('.region-sticky-watcher');
        if (element) {
          observer.observe(element);
        }
      });
    }
  };
})(Drupal);
