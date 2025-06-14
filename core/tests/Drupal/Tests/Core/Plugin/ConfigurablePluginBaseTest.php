<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Core\Plugin\ConfigurablePluginBase;
use PHPUnit\Framework\TestCase;

/**
 * Tests ConfigurablePluginBase.
 *
 * @group Plugin
 *
 * @coversDefaultClass \Drupal\Core\Plugin\ConfigurablePluginBase
 */
class ConfigurablePluginBaseTest extends TestCase {

  /**
   * Tests the Constructor.
   */
  public function testConstructor(): void {
    $provided_configuration = [
      'foo' => 'bar',
    ];
    $merged_configuration = ['default' => 'default'] + $provided_configuration;
    $plugin = new ConfigurablePluginBaseTestClass($provided_configuration, '', []);
    $this->assertSame($merged_configuration, $plugin->getConfiguration());
  }

}

/**
 * Test class for ConfigurablePluginBase.
 */
class ConfigurablePluginBaseTestClass extends ConfigurablePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'default' => 'default',
    ];
  }

}
