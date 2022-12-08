<?php

namespace Drupal\KernelTests\Core\DependencyInjection;

use Drupal\autowire_test\TestInjection;
use Drupal\autowire_test\TestInjection2;
use Drupal\autowire_test\TestService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests auto-wiring services.
 *
 * @group DependencyInjection
 */
class AutowireTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['autowire_test'];

  /**
   * Tests that 'autowire_test.service' has its dependencies injected.
   */
  public function testAutowire(): void {
    // Ensure an autowired interface works.
    $this->assertInstanceOf(TestInjection::class, $this->container->get(TestService::class)->getTestInjection());
    // Ensure an autowired class works.
    $this->assertInstanceOf(TestInjection2::class, $this->container->get(TestService::class)->getTestInjection2());
  }

}
