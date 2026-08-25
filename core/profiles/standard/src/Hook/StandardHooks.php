<?php

namespace Drupal\standard\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the Standard installation profile.
 */
class StandardHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'standard_welcome_page' => [
        'variables' => [
          'attributes' => [],
        ],
      ],
    ];
  }

}
