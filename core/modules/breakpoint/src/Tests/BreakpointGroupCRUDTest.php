<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointGroupCRUDTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointGroupTestBase;
use Drupal\breakpoint\Entity\BreakpointGroup;
use Drupal\breakpoint\Entity\Breakpoint;

/**
 * Tests creation, loading, updating, deleting of breakpoint groups.
 *
 * @group breakpoint
 */
class BreakpointGroupCRUDTest extends BreakpointGroupTestBase {

  /**
   * Test CRUD operations for breakpoint groups.
   */
  public function testBreakpointGroupCRUD() {
    // Add breakpoints.
    $breakpoints = array();
    for ($i = 0; $i <= 3; $i++) {
      $width = ($i + 1) * 200;
      $breakpoint = entity_create('breakpoint', array(
        'name' => drupal_strtolower($this->randomMachineName()),
        'weight' => $i,
        'mediaQuery' => "(min-width: {$width}px)",
      ));
      $breakpoint->save();
      $breakpoints[$breakpoint->id()] = $breakpoint;
    }
    // Add a breakpoint group with minimum data only.
    $label = $this->randomMachineName();

    $group = entity_create('breakpoint_group', array(
      'label' => $label,
      'name' => drupal_strtolower($label),
    ));
    $group->save();
    $this->verifyBreakpointGroup($group);

    // Update the breakpoint group.
    $group->addBreakpoints($breakpoints)->save();
    $this->verifyBreakpointGroup($group);

    // Delete the breakpoint group.
    $group->delete();
    $this->assertFalse(entity_load('breakpoint_group', $group->id()), 'breakpoint_group_load: Loading a deleted breakpoint group returns false.', 'Breakpoints API');
  }
}
