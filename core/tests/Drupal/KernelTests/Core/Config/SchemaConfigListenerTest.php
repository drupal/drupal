<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Traits\Core\Config\SchemaConfigListenerTestTrait;

/**
 * Tests the functionality of ConfigSchemaChecker in KernelTestBase tests.
 *
 * @group config
 */
class SchemaConfigListenerTest extends KernelTestBase {

  use SchemaConfigListenerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install configuration provided by the module so that the order of the
    // config keys is the same as
    // \Drupal\FunctionalTests\Core\Config\SchemaConfigListenerTest.
    $this->installConfig(['config_test']);
  }

}
