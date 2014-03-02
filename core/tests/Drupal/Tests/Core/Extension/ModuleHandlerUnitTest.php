<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ModuleHanderUnitTest.
 */

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_Error_Notice;

/**
 * Tests the ModuleHandler class.
 *
 * @group System
 */
class ModuleHandlerUnitTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'ModuleHandler functionality',
      'description' => 'Tests the ModuleHandler class.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp();
    $this->moduleHandler = new ModuleHandler;
  }

  /**
   * Tests loading of an include from a nonexistent module.
   */
  public function testLoadInclude() {
    // Attepmting to load a file from a non-existent module should return FALSE.
    $this->assertFalse($this->moduleHandler->loadInclude('foo', 'inc'));
  }

}
