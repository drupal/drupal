<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Asset;

/**
 * Tests asset aggregation with the Umami install profile.
 *
 * Umami includes several core modules as well as the Claro theme, this
 * results in a more complex asset dependency tree to test than the testing
 * profile.
 *
 * @group asset
 * @group #slow
 */
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
