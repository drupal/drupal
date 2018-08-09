(function(Drupal) {
  /**
   * Call picturefill so newly added responsive images are processed.
   */
  Drupal.behaviors.responsiveImageAJAX = {
    attach() {
      if (window.picturefill) {
        window.picturefill();
      }
    },
  };
})(Drupal);
