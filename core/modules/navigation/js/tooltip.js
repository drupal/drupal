/* cspell:ignore uidom */
/**
 * @file
 *
 * Simple tooltip component.
 *
 * To use it just add:
 *
 * data-drupal-tooltip="title" - Text displayed in tooltip.
 *
 * data-drupal-tooltip-class="extra-class" - Optional class for css.
 *
 * data-drupal-tooltip-position="top" - Tooltip position (default right).
 *
 * @see https://floating-ui.com/ for available placement options.
 */

((Drupal, once, { computePosition, offset, shift, flip }) => {
  /**
   * Theme function for a tooltip.
   *
   * @param {object} dataset
   *   The dataset object.
   * @param {string} dataset.drupalTooltipClass
   *   Extra class for theming.
   * @param {string} dataset.drupalTooltip
   *   The text for tooltip.
   *
   * @return {HTMLElement}
   *   A DOM Node.
   */
  Drupal.theme.tooltipWrapper = (dataset) =>
    `<div class="toolbar-tooltip ${dataset.drupalTooltipClass || ''}">
      ${dataset.drupalTooltip}
    </div>`;

  /**
   * Attaches the tooltip behavior to all required triggers.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the tooltip behavior.
   */
  Drupal.behaviors.tooltipInit = {
    attach: (context) => {
      once('tooltip-trigger', '[data-drupal-tooltip]', context).forEach(
        (trigger) => {
          trigger.insertAdjacentHTML(
            'afterend',
            Drupal.theme.tooltipWrapper(trigger.dataset),
          );
          const tooltip = trigger.nextElementSibling;

          const updatePosition = () => {
            computePosition(trigger, tooltip, {
              strategy: 'fixed',
              placement: trigger.dataset.drupalTooltipPosition || 'right',
              middleware: [
                flip({ padding: 16 }),
                offset(6),
                shift({ padding: 16 }),
              ],
            }).then(({ x, y }) => {
              Object.assign(tooltip.style, {
                left: `${x}px`,
                top: `${y}px`,
              });
            });
          };

          // Small trick to avoid tooltip stays on same place when button size changed.
          const ro = new ResizeObserver(updatePosition);

          ro.observe(trigger);

          trigger.addEventListener('mouseover', updatePosition);
          trigger.addEventListener('focus', updatePosition);
        },
      );
    },
  };
})(Drupal, once, FloatingUIDOM);
