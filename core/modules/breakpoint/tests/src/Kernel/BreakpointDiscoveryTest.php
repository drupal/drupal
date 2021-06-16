<?php

namespace Drupal\Tests\breakpoint\Kernel;

use Drupal\KernelTests\KernelTestBase;

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
  protected static $modules = [
    'system',
    'breakpoint',
    'breakpoint_module_test',
  ];

  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['breakpoint_theme_test']);
  }

  /**
   * Tests the breakpoint group created for a theme.
   */
  public function testThemeBreakpoints() {
    // Verify the breakpoint group for breakpoint_theme_test was created.
    $expected_breakpoints = [
      'breakpoint_theme_test.mobile' => [
        'label' => 'mobile',
        'mediaQuery' => '(min-width: 0px)',
        'weight' => 0,
        'multipliers' => [
          '1x',
        ],
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.mobile',
        'group' => 'breakpoint_theme_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
      'breakpoint_theme_test.narrow' => [
        'label' => 'narrow',
        'mediaQuery' => '(min-width: 560px)',
        'weight' => 1,
        'multipliers' => [
          '1x',
        ],
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.narrow',
        'group' => 'breakpoint_theme_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
      'breakpoint_theme_test.wide' => [
        'label' => 'wide',
        'mediaQuery' => '(min-width: 851px)',
        'weight' => 2,
        'multipliers' => [
          '1x',
        ],
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.wide',
        'group' => 'breakpoint_theme_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
      'breakpoint_theme_test.tv' => [
        'label' => 'tv',
        'mediaQuery' => 'only screen and (min-width: 1220px)',
        'weight' => 3,
        'multipliers' => [
          '1x',
        ],
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.tv',
        'group' => 'breakpoint_theme_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
    ];

    $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup('breakpoint_theme_test');
    foreach ($expected_breakpoints as $id => $expected_breakpoint) {
      $this->assertEquals($expected_breakpoint, $breakpoints[$id]->getPluginDefinition());
    }

    // Test that the order is as expected.
    $this->assertSame(array_keys($expected_breakpoints), array_keys($breakpoints));
  }

  /**
   * Tests the custom breakpoint group provided by a theme and a module.
   */
  public function testCustomBreakpointGroups() {
    // Verify the breakpoint group for breakpoint_theme_test.group2 was created.
    $expected_breakpoints = [
      'breakpoint_theme_test.group2.narrow' => [
        'label' => 'narrow',
        'mediaQuery' => '(min-width: 560px)',
        'weight' => 0,
        'multipliers' => [
          '1x',
          '2x',
        ],
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.group2.narrow',
        'group' => 'breakpoint_theme_test.group2',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
      'breakpoint_theme_test.group2.wide' => [
        'label' => 'wide',
        'mediaQuery' => '(min-width: 851px)',
        'weight' => 1,
        'multipliers' => [
          '1x',
          '2x',
        ],
        'provider' => 'breakpoint_theme_test',
        'id' => 'breakpoint_theme_test.group2.wide',
        'group' => 'breakpoint_theme_test.group2',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
      'breakpoint_module_test.breakpoint_theme_test.group2.tv' => [
        'label' => 'tv',
        'mediaQuery' => '(min-width: 6000px)',
        'weight' => 2,
        'multipliers' => [
          '1x',
        ],
        'provider' => 'breakpoint_module_test',
        'id' => 'breakpoint_module_test.breakpoint_theme_test.group2.tv',
        'group' => 'breakpoint_theme_test.group2',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
    ];

    $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup('breakpoint_theme_test.group2');
    foreach ($expected_breakpoints as $id => $expected_breakpoint) {
      $this->assertEquals($expected_breakpoint, $breakpoints[$id]->getPluginDefinition());
    }
  }

  /**
   * Tests the breakpoint group created for a module.
   */
  public function testModuleBreakpoints() {
    $expected_breakpoints = [
      'breakpoint_module_test.mobile' => [
        'label' => 'mobile',
        'mediaQuery' => '(min-width: 0px)',
        'weight' => 0,
        'multipliers' => [
          '1x',
        ],
        'provider' => 'breakpoint_module_test',
        'id' => 'breakpoint_module_test.mobile',
        'group' => 'breakpoint_module_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
      'breakpoint_module_test.standard' => [
        'label' => 'standard',
        'mediaQuery' => '(min-width: 560px)',
        'weight' => 1,
        'multipliers' => [
          '1x',
          '2x',
        ],
        'provider' => 'breakpoint_module_test',
        'id' => 'breakpoint_module_test.standard',
        'group' => 'breakpoint_module_test',
        'class' => 'Drupal\\breakpoint\\Breakpoint',
      ],
    ];

    $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup('breakpoint_module_test');
    $this->assertEquals(array_keys($expected_breakpoints), array_keys($breakpoints));
  }

  /**
   * Tests the collection of breakpoint groups.
   */
  public function testBreakpointGroups() {
    $expected = [
      'bartik' => 'Bartik',
      'breakpoint_module_test' => 'Breakpoint test module',
      'breakpoint_theme_test' => 'Breakpoint test theme',
      'breakpoint_theme_test.group2' => 'breakpoint_theme_test.group2',
    ];
    $breakpoint_groups = \Drupal::service('breakpoint.manager')->getGroups();
    // Ensure the order is as expected. Should be sorted by label.
    $this->assertEquals($expected, $breakpoint_groups);

    $expected = [
      'breakpoint_theme_test' => 'theme',
      'breakpoint_module_test' => 'module',
    ];
    $breakpoint_group_providers = \Drupal::service('breakpoint.manager')->getGroupProviders('breakpoint_theme_test.group2');
    $this->assertEquals($expected, $breakpoint_group_providers);
  }

}
