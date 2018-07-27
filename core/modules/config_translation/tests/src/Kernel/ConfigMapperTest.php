<?php

namespace Drupal\Tests\config_translation\Kernel;

use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Routing\Route;

/**
 * Tests config mapper.
 *
 * @group config_translation
 */
class ConfigMapperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'config_translation',
    'config_translation_test',
    'language',
    'locale',
    'system',
  ];

  /**
   * Tests adding config names to mapper.
   */
  public function testAddingConfigNames() {
    // Get a config names mapper.
    $mappers = \Drupal::service('plugin.manager.config_translation.mapper')->getMappers();
    $mapper = $mappers['system.site_information_settings'];

    // Test that it doesn't contain a config name from config_translation_test.
    $config_names = $mapper->getConfigNames();
    $this->assertNotContains('config_translation_test.content', $config_names);

    // Call populateFromRouteMatch() to dispatch the "config mapper populate"
    // event.
    $mapper->populateFromRouteMatch(new RouteMatch('test', new Route('/')));

    // Test that it contains the new config name from config_translation_test.
    $config_names = $mapper->getConfigNames();
    $this->assertContains('config_translation_test.content', $config_names);
  }

}
