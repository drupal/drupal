<?php

/**
 * @file
 * Post update functions for Tour.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\tour\Entity\Tour;

/**
 * Convert Joyride selectors to `selector` property.
 *
 * @see tour_tour_presave()
 */
function tour_post_update_joyride_selectors_to_selector_property(array &$sandbox = NULL) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'tour', function (Tour $tour) {
    return _tour_update_joyride($tour, FALSE);
  });
}
