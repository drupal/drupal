<?php

namespace Drupal\standard\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Controller for the Standard profile welcome page.
 */
class WelcomeController extends ControllerBase {

  /**
   * Controls access to the welcome page.
   */
  public function access(AccountInterface $account): AccessResult {
    // Older sites will not have this install-time flag set.
    return AccessResult::allowedIf((bool) \Drupal::state()->get('standard.show_welcome', FALSE));
  }

  /**
   * Returns the welcome page render array.
   *
   * @return array
   *   A render array containing the welcome page content.
   */
  public function welcome(): array {
    return [
      '#theme' => 'standard_welcome_page',
      '#attached' => [
        'library' => [
          'standard/welcome_page',
        ],
      ],
    ];
  }

}
