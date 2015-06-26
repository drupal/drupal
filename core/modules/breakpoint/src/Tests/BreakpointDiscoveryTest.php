<?php
/**
 * @file
 * Contains \Drupal\breakpoint\Tests\BreakpointDiscoveryTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests discovery of breakpoints provided by themes and modules.
 *
 * @group breakpoint
 */
class BreakpointDiscoveryTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('system', 'breakpoint', 'breakpoint_module_test');

  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));
    \Drupal::service('theme_handler')->install(array('breakpoint_theme_test'));
  }

  /**
   * Test the breakpoint group created for a theme.
   */
  public function testThemeBreakpoints() {
    // Verify the breakpoint group for breakpoint_theme_test was created.
    $expected_breakpoints = array(
      'breakpoint_theme_test.tv' => array(
        'label' => 'tv',
        'mediaQuery' => 'only screen and (min-width: 1220px)',
        'weight' => 0,
        'multipliers' => array(
          '1x',
        ),
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.tv',
        'group' => 'breakpoint_theme_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
      'breakpoint_theme_test.wide' => array(
        'label' => 'wide',
        'mediaQuery' => '(min-width: 851px)',
        'weight' => 1,
        'multipliers' => array(
          '1x',
        ),
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.wide',
        'group' => 'breakpoint_theme_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
      'breakpoint_theme_test.narrow' => array(
        'label' => 'narrow',
        'mediaQuery' => '(min-width: 560px)',
        'weight' => 2,
        'multipliers' => array(
          '1x',
        ),
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.narrow',
        'group' => 'breakpoint_theme_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
      'breakpoint_theme_test.mobile' => array(
        'label' => 'mobile',
        'mediaQuery' => '(min-width: 0px)',
        'weight' => 3,
        'multipliers' => array(
          '1x',
        ),
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.mobile',
        'group' => 'breakpoint_theme_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
    );

    $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup('breakpoint_theme_test');
    foreach ($expected_breakpoints as $id => $expected_breakpoint) {
      $this->assertEqual($expected_breakpoint, $breakpoints[$id]->getPluginDefinition());
    }

    // Test that the order is as expected.
    $this->assertIdentical(array_keys($expected_breakpoints), array_keys($breakpoints));
  }

  /**
   * Test the custom breakpoint group provided by a theme and a module.
   */
  public function testCustomBreakpointGroups () {
    // Verify the breakpoint group for breakpoint_theme_test.group2 was created.
    $expected_breakpoints = array(
      'breakpoint_theme_test.group2.narrow' => array(
        'label' => 'narrow',
        'mediaQuery' => '(min-width: 560px)',
        'weight' => 2,
        'multipliers' => array(
          '1x',
          '2x',
        ),
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.group2.narrow',
        'group' => 'breakpoint_theme_test.group2',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
      'breakpoint_theme_test.group2.wide' => array(
        'label' => 'wide',
        'mediaQuery' => '(min-width: 851px)',
        'weight' => 1,
        'multipliers' => array(
          '1x',
          '2x',
        ),
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.group2.wide',
        'group' => 'breakpoint_theme_test.group2',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
      'breakpoint_module_test.breakpoint_theme_test.group2.tv' => array(
        'label' => 'tv',
        'mediaQuery' => '(min-width: 6000px)',
        'weight' => 0,
        'multipliers' => array(
          '1x',
        ),
        'provider' => 'breakpoint_module_test',
        'id' => 'breakpoint_module_test.breakpoint_theme_test.group2.tv',
        'group' => 'breakpoint_theme_test.group2',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
    );

    $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup('breakpoint_theme_test.group2');
    foreach ($expected_breakpoints as $id => $expected_breakpoint) {
      $this->assertEqual($expected_breakpoint, $breakpoints[$id]->getPluginDefinition());
    }
  }

  /**
   * Test the breakpoint group created for a module.
   */
  public function testModuleBreakpoints() {
    $expected_breakpoints = array(
      'breakpoint_module_test.mobile' => array(
        'label' => 'mobile',
        'mediaQuery' => '(min-width: 0px)',
        'weight' => 1,
        'multipliers' => array(
          '1x',
        ),
        'provider' => 'breakpoint_module_test',
        'id' => 'breakpoint_module_test.mobile',
        'group' => 'breakpoint_module_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
      'breakpoint_module_test.standard' => array(
        'label' => 'standard',
        'mediaQuery' => '(min-width: 560px)',
        'weight' => 0,
        'multipliers' => array(
          '1x',
          '2x',
        ),
        'provider' => 'breakpoint_module_test',
        'id' => 'breakpoint_module_test.standard',
        'group' => 'breakpoint_module_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ),
    );

    $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup('breakpoint_module_test');
    foreach ($expected_breakpoints as $id => $expected_breakpoint) {
      $this->assertEqual($expected_breakpoint, $breakpoints[$id]->getPluginDefinition());
    }
  }

  /**
   * Test the collection of breakpoint groups.
   */
  public function testBreakpointGroups() {
    $expected = array(
      'bartik' => 'Bartik',
      'breakpoint_module_test' => 'Breakpoint test module',
      'breakpoint_theme_test' => 'Breakpoint test theme',
      'breakpoint_theme_test.group2' => 'breakpoint_theme_test.group2',
    );
    $breakpoint_groups = \Drupal::service('breakpoint.manager')->getGroups();
    // Ensure the order is as expected. Should be sorted by label.
    $this->assertIdentical($expected, $breakpoint_groups);

    $expected = array(
      'breakpoint_theme_test' => 'theme',
      'breakpoint_module_test' => 'module',
    );
    $breakpoint_group_providers = \Drupal::service('breakpoint.manager')->getGroupProviders('breakpoint_theme_test.group2');
    $this->assertEqual($expected, $breakpoint_group_providers);
  }

}
