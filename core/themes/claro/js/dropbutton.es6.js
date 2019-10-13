/**
 * @file
 * Theme overrides for Claro.
 */

(Drupal => {
  /**
   * Overrides the dropbutton toggle markup.
   *
   * We have to keep the 'dropbutton-toggle' CSS class because the dropbutton JS
   * operates with that one.
   *
   * @param {object} options
   *   Options object.
   * @param {string} [options.title]
   *   The button text.
   *
   * @return {string}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.dropbuttonToggle = options =>
    `<li class="dropbutton-toggle"><button type="button" class="dropbutton__toggle"><span class="visually-hidden">${options.title}</span></button></li>`;
})(Drupal);
