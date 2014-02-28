<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointsThemeTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointGroupTestBase;
use Drupal\breakpoint\Entity\BreakpointGroup;
use Drupal\breakpoint\Entity\Breakpoint;

/**
 * Test breakpoints provided by themes.
 */
class BreakpointThemeTest extends BreakpointGroupTestBase {

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
    $breakpoint_group_obj->addBreakpoints(entity_load_multiple('breakpoint',
      array(
        'theme.breakpoint_test_theme.mobile',
        'theme.breakpoint_test_theme.narrow',
        'theme.breakpoint_test_theme.wide',
        'theme.breakpoint_test_theme.tv',
      )
    ));

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
    $breakpoint_group_obj->addBreakpoints(entity_load_multiple('breakpoint',
      array(
        'theme.breakpoint_test_theme.mobile',
        'theme.breakpoint_test_theme.narrow',
        'theme.breakpoint_test_theme.wide',
      )
    ));

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertFalse(entity_load('breakpoint_group', $breakpoint_group_obj->id()), 'breakpoint_group_load: Loading a deleted breakpoint group returns false.', 'Breakpoint API');
  }

}
