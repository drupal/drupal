<?php

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Config.
 *
 * @coversDefaultClass \Drupal\Core\Config\ExtensionInstallStorage
 *
 * @group Config
 *
 * @see \Drupal\Core\Config\Config
 */
class ExtensionInstallStorageTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @group legacy
   * @dataProvider providerTestProfileDeprecation
   * @expectedDeprecation All \Drupal\Core\Config\ExtensionInstallStorage::__construct() arguments will be required in drupal:9.0.0. See https://www.drupal.org/node/2538996
   */
  public function testProfileDeprecation($container_profile) {
    $config_storage = $this->prophesize(StorageInterface::class)->reveal();
    $container = new ContainerBuilder();
    $container->setParameter('install_profile', $container_profile);
    \Drupal::setContainer($container);
    $storage = new TestExtensionInstallStorage($config_storage);
    $this->assertSame($container_profile, $storage->getProfile());
  }

  /**
   * Data provider for ::testProfileDeprecation
   */
  public function providerTestProfileDeprecation() {
    return [
      'null profile' => [NULL],
      'test profile' => ['test'],
    ];
  }

}

class TestExtensionInstallStorage extends ExtensionInstallStorage {

  /**
   * Gets the install profile value.
   *
   * @return string|null
   */
  public function getProfile() {
    return $this->installProfile;
  }

}
