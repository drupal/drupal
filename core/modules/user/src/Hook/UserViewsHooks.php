<?php

namespace Drupal\user\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for user.
 */
class UserViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_plugins_argument_validator_alter().
   */
  #[Hook('views_plugins_argument_validator_alter')]
  public function viewsPluginsArgumentValidatorAlter(array &$plugins): void {
    $plugins['entity:user']['title'] = $this->t('User ID');
    $plugins['entity:user']['class'] = 'Drupal\user\Plugin\views\argument_validator\User';
    $plugins['entity:user']['provider'] = 'user';
  }

}
