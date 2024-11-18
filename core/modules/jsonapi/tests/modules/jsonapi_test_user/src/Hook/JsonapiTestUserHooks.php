<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_user\Hook;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for jsonapi_test_user.
 */
class JsonapiTestUserHooks {

  /**
   * Implements hook_user_format_name_alter().
   */
  #[Hook('user_format_name_alter')]
  public function userFormatNameAlter(&$name, AccountInterface $account): void {
    if ($account->isAnonymous()) {
      $name = 'User ' . $account->id();
    }
  }

}
