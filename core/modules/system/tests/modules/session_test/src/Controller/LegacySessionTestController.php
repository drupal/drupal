<?php

declare(strict_types=1);

namespace Drupal\session_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller providing page callbacks for legacy session tests.
 */
class LegacySessionTestController extends ControllerBase {

  /**
   * Prints the stored session value to the screen.
   */
  public function get(): array {
    return empty($_SESSION['legacy_test_value'])
      ? []
      : ['#markup' => $this->t('The current value of the stored session variable is: %val', ['%val' => $_SESSION['legacy_test_value']])];
  }

  /**
   * Stores a value in $_SESSION['legacy_test_value'].
   *
   * @param string $test_value
   *   A session value.
   */
  public function set(string $test_value): array {
    $_SESSION['legacy_test_value'] = $test_value;

    return ['#markup' => $this->t('The current value of the stored session variable has been set to %val', ['%val' => $test_value])];
  }

}
