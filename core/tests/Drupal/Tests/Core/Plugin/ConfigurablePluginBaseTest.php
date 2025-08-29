<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Core\Plugin\ConfigurablePluginBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests ConfigurablePluginBase.
 */
#[CoversClass(ConfigurablePluginBase::class)]
#[Group('Plugin')]
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
