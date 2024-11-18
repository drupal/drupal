<?php

declare(strict_types=1);

namespace Drupal\user_hooks_test\Hook;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;

/**
 * Contains hook implementations.
 */
class UserHooksTest {

  public function __construct(protected StateInterface $state) {
  }

  /**
   * Alters the username.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $name
   *   The username that is displayed for a user.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The object on which the operation is being performed.
   *
   * @return void
   */
  #[Hook('user_format_name_alter')]
  public function userFormatNameAlter(&$name, AccountInterface $account): void {
    if ($this->state->get('user_hooks_test_user_format_name_alter', FALSE)) {
      if ($this->state->get('user_hooks_test_user_format_name_alter_safe', FALSE)) {
        $name = new FormattableMarkup('<em>@uid</em>', ['@uid' => $account->id()]);
      }
      else {
        $name = '<em>' . $account->id() . '</em>';
      }
    }
  }

}
