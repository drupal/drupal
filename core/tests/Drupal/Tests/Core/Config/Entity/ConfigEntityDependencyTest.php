<?php

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\Entity\ConfigEntityDependency;

/**
 * Tests the ConfigEntityDependency class.
 *
 * @group Config
 */
class ConfigEntityDependencyTest extends UnitTestCase {

  public function testEmptyDependencies() {
    $dep = new ConfigEntityDependency('config_test.dynamic.entity_id', array());

    $this->assertEquals('config_test.dynamic.entity_id', $dep->getConfigDependencyName());
    $this->assertEquals(array(), $dep->getDependencies('theme'));
    $this->assertEquals(array(), $dep->getDependencies('config'));
    $this->assertEquals(array('config_test'), $dep->getDependencies('module'));
    $this->assertTrue($dep->hasDependency('module', 'config_test'));
    $this->assertFalse($dep->hasDependency('module', 'views'));
  }

  public function testWithDependencies() {
    $values = array(
      'uuid' => '60db47f4-54fb-4c86-a439-5769fbda4bd1',
      'dependencies' => array(
        'module' => array(
          'node',
          'views'
        ),
        'config' => array(
          'config_test.dynamic.entity_id:745b0ce0-aece-42dd-a800-ade5b8455e84',
        ),
      ),
    );
    $dep = new ConfigEntityDependency('config_test.dynamic.entity_id', $values);

    $this->assertEquals(array(), $dep->getDependencies('theme'));
    $this->assertEquals(array('config_test.dynamic.entity_id:745b0ce0-aece-42dd-a800-ade5b8455e84'), $dep->getDependencies('config'));
    $this->assertEquals(array('node', 'views', 'config_test'), $dep->getDependencies('module'));
    $this->assertTrue($dep->hasDependency('module', 'config_test'));
    $this->assertTrue($dep->hasDependency('module', 'views'));
    $this->assertTrue($dep->hasDependency('module', 'node'));
    $this->assertFalse($dep->hasDependency('module', 'block'));
    $this->assertTrue($dep->hasDependency('config', 'config_test.dynamic.entity_id:745b0ce0-aece-42dd-a800-ade5b8455e84'));
    $this->assertFalse($dep->hasDependency('config', 'config_test.dynamic.another_id:7dfa5cb7-2248-4d52-8c00-cd8e02d1e78e'));
  }

}
