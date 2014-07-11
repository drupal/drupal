<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\ConfigDependencyManagerTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\Entity\ConfigDependencyManager;

/**
 * Tests the ConfigDependencyManager class.
 *
 * @group Config
 */
class ConfigDependencyManagerTest extends UnitTestCase {

  public function testNoConfiguration() {
    $dep_manger = new ConfigDependencyManager();
    $this->assertEmpty($dep_manger->getDependentEntities('entity', 'config_test.dynamic.entity_id:745b0ce0-aece-42dd-a800-ade5b8455e84'));
  }

  public function testNoConfigEntities() {
    $dep_manger = new ConfigDependencyManager();
    $dep_manger->setData(array(
      'simple.config' => array(
        'key' => 'value',
      ),
    ));
    $this->assertEmpty($dep_manger->getDependentEntities('entity', 'config_test.dynamic.entity_id:745b0ce0-aece-42dd-a800-ade5b8455e84'));

    // Configuration is always dependent on its provider.
    $dependencies = $dep_manger->getDependentEntities('module', 'simple');
    $this->assertArrayHasKey('simple.config', $dependencies);
    $this->assertCount(1, $dependencies);
  }

}
