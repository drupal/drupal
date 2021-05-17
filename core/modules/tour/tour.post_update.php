<?php

/**
 * @file
 * Post update functions for Tour.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\tour\Entity\Tour;

/**
 * Convert Joyride selectors to `selector` property.
 */
function tour_post_update_joyride_selectors_to_selector_property(&$sandbox = NULL) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $update_selector = function (Tour $tour) {
    $needs_save = FALSE;
    $tips = $tour->get('tips');
    foreach ($tips as &$tip) {
      if (isset($tip['attributes']['data-class']) || isset($tip['attributes']['data-id'])) {
        $needs_save = TRUE;
        $selector = isset($tip['attributes']['data-class']) ? ".{$tip['attributes']['data-class']}" : NULL;
        $selector = isset($tip['attributes']['data-id']) ? "#{$tip['attributes']['data-id']}" : $selector;
        $tip['selector'] = $selector;

        // Although the attributes property is deprecated, only the properties
        // with 1:1 equivalents are unset.
        unset($tip['attributes']['data-class']);
        unset($tip['attributes']['data-id']);
      }
      if (isset($tip['location'])) {
        $needs_save = TRUE;

        // Joyride only supports four location options: 'top', 'bottom',
        // 'left', and 'right'. Shepherd also accepts these as options, but they
        // result in different behavior. A given Joyride location option will
        // provide the same results in Shepherd if '-start' is appended to it (
        // e.g. the 'left-start' option in Shepherd positions the element the
        // same way that 'left' does in Joyride.
        //
        // @see https://shepherdjs.dev/docs/Step.html
        $tip['position'] = $tip['location'] . '-start';
        unset($tip['location']);
      }
    }

    if ($needs_save) {
      $tour->set('tips', $tips);
    }

    return $needs_save;
  };

  $config_entity_updater->update($sandbox, 'tour', $update_selector);
}
