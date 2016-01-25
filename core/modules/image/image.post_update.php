<?php

/**
 * @file
 * Post-update functions for Image.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Saves the image style dependencies into form and view display entities.
 */
function image_post_update_image_style_dependencies() {
  // Merge view and form displays. Use array_values() to avoid key collisions.
  $displays = array_merge(array_values(EntityViewDisplay::loadMultiple()), array_values(EntityFormDisplay::loadMultiple()));
  /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface[] $displays */
  foreach ($displays as $display) {
    // Re-save each config entity to add missed dependencies.
    $display->save();
  }
}
