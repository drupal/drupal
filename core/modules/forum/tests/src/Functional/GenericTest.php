<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;

/**
 * Generic module test for forum.
 *
 * @group forum
 * @group legacy
 */
class GenericTest extends GenericModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected function preUninstallSteps(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $storage->loadMultiple();
    $storage->delete($terms);
  }

}
