<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;

/**
 * Generic module test for workspaces.
 *
 * @group workspaces
 */
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
