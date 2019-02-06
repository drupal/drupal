<?php

/**
 * @file
 * Post update functions for Media library.
 */

use Drupal\media\Entity\MediaType;

/**
 * Create and configure Media Library form and view displays for media types.
 */
function media_library_post_update_display_modes() {
  // The Media Library needs a special form display and view display to make
  // sure the Media Library is displayed properly. These were not automatically
  // created for custom media types, so let's make sure this is fixed.
  $types = [];
  foreach (MediaType::loadMultiple() as $type) {
    $form_display_created = _media_library_configure_form_display($type);
    $view_display_created = _media_library_configure_view_display($type);
    if ($form_display_created || $view_display_created) {
      $types[] = $type->label();
    }
  }
  if ($types) {
    return t('Media Library form and view displays have been created for the following media types: @types.', [
      '@types' => implode(', ', $types),
    ]);
  }
}
