<?php

/**
 * @file
 * Post update functions for Language module.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Add the 'include_locked' settings to the 'language_select' widget.
 */
function language_post_update_language_select_widget() {
  foreach (EntityFormDisplay::loadMultiple() as $display_form) {
    $content = $display_form->get('content');
    $changed = FALSE;
    foreach (array_keys($content) as $element) {
      if (isset($content[$element]['type']) && $content[$element]['type'] == 'language_select') {
        $content[$element]['settings']['include_locked'] = TRUE;
        $changed = TRUE;
      }
    }
    if ($changed) {
      $display_form->set('content', $content);
      $display_form->save();
    }
  }
}
