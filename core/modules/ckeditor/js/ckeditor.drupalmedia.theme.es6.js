/**
 * @file
 * Theme elements for the Media Embed CKEditor plugin.
 */

((Drupal) => {
  /**
   * Themes the edit button for a media embed.
   *
   * @return {string}
   *   An HTML string to insert in the CKEditor.
   */
  Drupal.theme.mediaEmbedEditButton = () =>
    `<button class="media-library-item__edit">${Drupal.t(
      'Edit media',
    )}</button>`;
})(Drupal);
