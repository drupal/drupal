<?php

namespace Drupal\user\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for user.
 */
class UserViewsHooks {
  /**
   * @file
   * Provide views data for user.module.
   */

  /**
   * Implements hook_views_plugins_argument_validator_alter().
   */
  #[Hook('views_plugins_argument_validator_alter')]
  public function viewsPluginsArgumentValidatorAlter(array &$plugins): void {
    $plugins['entity:user']['title'] = t('User ID');
    $plugins['entity:user']['class'] = 'Drupal\user\Plugin\views\argument_validator\User';
    $plugins['entity:user']['provider'] = 'user';
  }

}
