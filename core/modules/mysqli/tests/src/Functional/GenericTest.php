<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Functional;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Generic module test for mysqli.
 */
#[Group('mysqli')]
class GenericTest extends GenericModuleTestBase {

  /**
   * Checks visibility of the module.
   */
  public function testMysqliModule(): void {
    $module = $this->getModule();
    \Drupal::service('module_installer')->install([$module]);
    $info = \Drupal::service('extension.list.module')->getExtensionInfo($module);
    $this->assertTrue($info['hidden']);
    $this->assertSame(ExtensionLifecycle::EXPERIMENTAL, $info['lifecycle']);
  }

}
