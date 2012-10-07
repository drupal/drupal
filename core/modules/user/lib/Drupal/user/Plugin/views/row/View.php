<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\row\View
 */

namespace Drupal\user\Plugin\views\row;

use Drupal\system\Plugin\views\row\Entity;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * A row plugin which renders a user.
 *
 * @ingroup views_row_plugins
 *
 * @Plugin(
 *   id = "user",
 *   module = "user",
 *   title = @Translation("User"),
 *   help = @Translation("Display the user with standard user view."),
 *   base = {"users"},
 *   entity_type = "user",
 *   type = "normal"
 * )
 */
class View extends Entity {

  /**
   * Overrides Drupal\system\Plugin\views\row\Entity::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['view_mode']['default'] = 'full';

    return $options;
  }

}
