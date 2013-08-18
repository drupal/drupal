<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointCRUDTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointTestBase;
use Drupal\breakpoint\Entity\Breakpoint;

/**
 * Tests for breakpoint CRUD operations.
 */
class BreakpointCRUDTest extends BreakpointTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint CRUD operations',
      'description' => 'Test creation, loading, updating, deleting of breakpoints.',
      'group' => 'Breakpoint',
    );
  }

  /**
   * Test CRUD operations for breakpoints.
   */
  public function testBreakpointCRUD() {
    // Add a breakpoint with minimum data only.
    $breakpoint = entity_create('breakpoint', array(
      'label' => drupal_strtolower($this->randomName()),
      'mediaQuery' => '(min-width: 600px)',
    ));
    $breakpoint->save();

    $this->verifyBreakpoint($breakpoint);

    // Test breakpoint_load_all
    $all_breakpoints = entity_load_multiple('breakpoint');
    $config_name = $breakpoint->id();
    $this->assertTrue(isset($all_breakpoints[$config_name]), 'breakpoint_load_all: New breakpoint is present when loading all breakpoints.');
    $this->verifyBreakpoint($breakpoint, $all_breakpoints[$config_name]);

    // Update the breakpoint.
    $breakpoint->weight = 1;
    $breakpoint->multipliers['2x'] = '2x';
    $breakpoint->save();
    $this->verifyBreakpoint($breakpoint);

    // Delete the breakpoint.
    $breakpoint->delete();
    $this->assertFalse(breakpoint_load($config_name), 'breakpoint_load: Loading a deleted breakpoint returns false.', 'Breakpoints API');
  }
}
