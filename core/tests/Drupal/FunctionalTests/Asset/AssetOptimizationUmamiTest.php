<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Asset;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests asset aggregation with the Umami install profile.
 *
 * Umami includes several core modules as well as the Claro theme, this
 * results in a more complex asset dependency tree to test than the testing
 * profile.
 */
#[Group('asset')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class AssetOptimizationUmamiTest extends AssetOptimizationTest {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * {@inheritdoc}
   */
  protected function requestPage(): void {
    $user = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($user);
    $this->drupalGet('node/add/article');
  }

}
