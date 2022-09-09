/**
 * @file
 * Classy theme overrides for the Media Embed CKEditor plugin.
 */

((Drupal) => {
  /**
   * Themes the error displayed when the media embed preview fails.
   *
   * @param {string} error
   *   The error message to display
   *
   * @return {string}
   *   A string representing a DOM fragment.
   *
   * @see media-embed-error.html.twig
   */
  Drupal.theme.mediaEmbedPreviewError = () =>
    `<div class="media-embed-error media-embed-error--preview-error">${Drupal.t(
      'An error occurred while trying to preview the media. Please save your work and reload this page.',
    )}</div>`;
})(Drupal);
