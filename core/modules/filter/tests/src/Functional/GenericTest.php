<?php

namespace Drupal\Tests\filter\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;

/**
 * Generic module test for filter.
 *
 * @group filter
 */
class GenericTest extends GenericModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected function preUninstallSteps(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('filter_format');
    $text_formats = $storage->loadMultiple();
    $storage->delete($text_formats);
  }

}
