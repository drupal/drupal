/**
 * @file
 *
 * Element that improves sub-menu UX by implementing the Safe Triangle strategy.
 * @see https://www.smashingmagazine.com/2023/08/better-context-menus-safe-triangles
 */

((Drupal, once) => {
  /**
   * Update CSS variables values for positioning the safe triangle element.
   *
   * @param {CSSStyleDeclaration} style
   *   Style property of the parent button.
   * @param {number} clientX
   *   Horizontal position relative to the element.
   * @param {number} clientY
   *   Vertical position relative to the element.
   */
  function handleMouseMove({ currentTarget: { style }, clientX, clientY }) {
    style.setProperty('--safe-triangle-cursor-x', `${clientX}px`);
    style.setProperty('--safe-triangle-cursor-y', `${clientY}px`);
  }

  /**
   * Attaches the safe triangle behavior to all required triggers.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the safe triangle behavior.
   * @prop {Drupal~behaviorDetach} detach
   *   Removes the safe triangle element.
   */
  Drupal.behaviors.safeTriangleInit = {
    attach: (context) => {
      once('safe-triangle', '[data-has-safe-triangle]', context).forEach(
        (button) => {
          button.insertAdjacentHTML(
            'beforeend',
            '<div data-safe-triangle></div>',
          );
          button.addEventListener('mousemove', handleMouseMove);
        },
      );
    },
    detach: (context, settings, trigger) => {
      if (trigger === 'unload') {
        once
          .remove('safe-triangle', '[data-has-safe-triangle]', context)
          .forEach((button) => {
            button.querySelector('[data-safe-triangle]')?.remove();
            button.removeEventListener('mousemove', handleMouseMove);
          });
      }
    },
  };
})(Drupal, once);
