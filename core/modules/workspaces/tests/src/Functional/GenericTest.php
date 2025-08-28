<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Generic module test for workspaces.
 */
#[Group('workspaces')]
class GenericTest extends GenericModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected function preUninstallSteps(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('workspace');
    $workspaces = $storage->loadMultiple();
    $storage->delete($workspaces);
  }

}
