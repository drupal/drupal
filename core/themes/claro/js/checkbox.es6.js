/**
 * @file
 * Theme override for checkbox.
 */

(Drupal => {
  /**
   * Constucts a checkbox input element.
   *
   * @return {string}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.checkbox = () =>
    '<input type="checkbox" class="form-checkbox form-boolean form-boolean--type-checkbox"/>';
})(Drupal);
