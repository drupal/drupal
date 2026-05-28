((Drupal) => {
  Drupal.theme.mediaEmbedPreviewError = () => {
    return '<div class="media-embed-error media-embed-error--preview-error">' + Drupal.t('An error occurred while trying to preview the media. You should save your work and reload this page.') + '</div>';
  };
})(Drupal);
