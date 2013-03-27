<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ModuleHanderUnitTest.
 */

namespace Drupal\Tests\Core\Extension;

if (!defined('DRUPAL_ROOT')) {
  define('DRUPAL_ROOT', dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)))));
}

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Tests\UnitTestCase;

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

  function testloadInclude() {
    // Make sure that load include does not throw notices on nonexisiting
    // modules.
    $this->moduleHandler->loadInclude('foo', 'inc');
  }
}
