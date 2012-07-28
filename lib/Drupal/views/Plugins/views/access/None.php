<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\views\access\None.
 */

namespace Drupal\views\Plugins\views\access;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Annotation\Plugin;

/**
 * Access plugin that provides no access control at all.
 *
 * @ingroup views_access_plugins
 */

/**
 * @Plugin(
 *   plugin_id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("Will be available to all users."),
 *   help_topic = "access-none"
 * )
 */
class None extends AccessPluginBase {
  function summary_title() {
    return t('Unrestricted');
  }
}
