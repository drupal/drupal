<?php

/**
 * @file
 * Hooks provided by the System module.
 */

use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters theme operation links.
 *
 * @param $theme_groups
 *   An associative array containing groups of themes.
 *
 * @see system_themes_page()
 */
function hook_system_themes_page_alter(&$theme_groups) {
  foreach ($theme_groups as $state => &$group) {
    foreach ($theme_groups[$state] as &$theme) {
      // Add a foo link to each list of theme operations.
      $theme->operations[] = [
        'title' => t('Foo'),
        'url' => Url::fromRoute('system.themes_page'),
        'query' => ['theme' => $theme->getName()],
      ];
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
