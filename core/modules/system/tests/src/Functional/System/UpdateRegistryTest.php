<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update registry.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class UpdateRegistryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests discovery after installing a module.
   */
  public function testExtensionDiscoveryIsInvalidatedOnModuleInstall(): void {
    $update_registry = $this->container->get('update.post_update_registry');
    $this->assertNotEmpty($update_registry->getUpdateFunctions('system'));
    $this->assertEmpty($update_registry->getUpdateFunctions('help'));

    $this->container->get('module_installer')->install(['help']);

    $this->assertSame(['help_post_update_search_help_dependencies'], array_values($update_registry->getUpdateFunctions('help')));
  }

}
