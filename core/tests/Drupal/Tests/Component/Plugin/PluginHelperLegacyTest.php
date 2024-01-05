<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\PluginHelper
 * @group Plugin
 * @group legacy
 */
class PluginHelperLegacyTest extends TestCase {
  use ExpectDeprecationTrait;

  public function testPluginHelperDeprecation(): void {
    $this->expectDeprecation('The Drupal\Component\Plugin\PluginHelper is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Instead, use instanceof() to check for \Drupal\Component\Plugin\ConfigurableInterface. See https://www.drupal.org/node/3198285');
    $this->assertEquals($this instanceof ConfigurableInterface, PluginHelper::isConfigurable($this));
    $plugin = $this->createMock(ConfigurableInterface::class);
    $this->assertEquals($plugin instanceof ConfigurableInterface, PluginHelper::isConfigurable($plugin));
  }

}
