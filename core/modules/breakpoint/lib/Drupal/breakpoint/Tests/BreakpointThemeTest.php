<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointsThemeTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointGroupTestBase;
use Drupal\breakpoint\Plugin\Core\Entity\BreakpointGroup;
use Drupal\breakpoint\Plugin\Core\Entity\Breakpoint;

/**
 * Test breakpoints provided by themes.
 */
class BreakpointThemeTest extends BreakpointGroupTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoint_theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint theme functionality',
      'description' => 'Thoroughly test the breakpoints provided by a theme.',
      'group' => 'Breakpoint',
    );
  }

  public function setUp() {
    parent::setUp();
    theme_enable(array('breakpoint_test_theme'));
  }

  /**
   * Test the breakpoints provided by a theme.
   */
  public function testThemeBreakpoints() {
    // Verify the breakpoint group for breakpoint_test_theme was created.
    $breakpoint_group_obj = entity_create('breakpoint_group', array(
      'label' => 'Breakpoint test theme',
      'name' => 'breakpoint_test_theme',
      'source' => 'breakpoint_test_theme',
      'sourceType' => Breakpoint::SOURCE_TYPE_THEME,
      'id' => Breakpoint::SOURCE_TYPE_THEME . '.breakpoint_test_theme.breakpoint_test_theme',
    ));
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array(),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
      'theme.breakpoint_test_theme.tv' => array(),
    );

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertFalse(entity_load('breakpoint_group', $breakpoint_group_obj->id()), 'breakpoint_group_load: Loading a deleted breakpoint group returns false.', 'Breakpoint API');
  }

  /**
   * Test the breakpoints defined by the custom group.
   */
  public function testThemeBreakpointGroup() {
    // Verify the breakpoint group 'test' was created by breakpoint_test_theme.
    $breakpoint_group_obj = entity_create('breakpoint_group', array(
      'label' => 'Test Theme',
      'name' => 'test',
      'sourceType' => Breakpoint::SOURCE_TYPE_THEME,
      'source' => 'breakpoint_test_theme',
      'id' => Breakpoint::SOURCE_TYPE_THEME . '.breakpoint_test_theme.test',
    ));
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array('1.5x', '2.x'),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
    );

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertFalse(entity_load('breakpoint_group', $breakpoint_group_obj->id()), 'breakpoint_group_load: Loading a deleted breakpoint group returns false.', 'Breakpoint API');
  }

  /**
   * Test the breakpoints defined by the custom group in the module.
   */
  public function testThemeBreakpointGroupModule() {
    // Call the import manually, since the testbot needs to enable the module
    // first, otherwise the theme isn't detected.
    _breakpoint_import_breakpoint_groups('breakpoint_theme_test', Breakpoint::SOURCE_TYPE_MODULE);

    // Verify the breakpoint group 'module_test' was created by
    // breakpoint_theme_test module.
    $breakpoint_group_obj = entity_create('breakpoint_group', array(
      'label' => 'Test Module',
      'name' => 'module_test',
      'sourceType' => Breakpoint::SOURCE_TYPE_MODULE,
      'source' => 'breakpoint_theme_test',
      'id' => Breakpoint::SOURCE_TYPE_MODULE . '.breakpoint_theme_test.module_test',
    ));
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array(),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
    );

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Disable the test theme and verify the breakpoint group still exists.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertTrue(entity_load('breakpoint_group', $breakpoint_group_obj->id()), 'Breakpoint group still exists if theme is disabled.');

    // Disable the test module and verify the breakpoint group still exists.
    module_disable(array('breakpoint_theme_test'));
    $this->assertTrue(entity_load('breakpoint_group', $breakpoint_group_obj->id()), 'Breakpoint group still exists if module is disabled.');

    // Uninstall the test module and verify the breakpoint group is deleted.
    module_uninstall(array('breakpoint_theme_test'));
    $this->assertFalse(entity_load('breakpoint_group', $breakpoint_group_obj->id()), 'Breakpoint group is removed if module is uninstalled.');
  }

}
