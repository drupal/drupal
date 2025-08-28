<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Generic module test for filter.
 */
#[Group('filter')]
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
