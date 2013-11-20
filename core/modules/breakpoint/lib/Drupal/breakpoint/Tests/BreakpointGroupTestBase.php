<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointGroupTestBase.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\breakpoint\Entity\BreakpointGroup;

/**
 * Base class for Breakpoint group tests.
 */
abstract class BreakpointGroupTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoint');

  public function setUp() {
    parent::setUp();
  }

  /**
   * Verify that a breakpoint is properly stored.
   */
  public function verifyBreakpointGroup(BreakpointGroup $group, BreakpointGroup $compare_set = NULL) {
    $properties = array(
      'label',
      'id',
      'name',
      'sourceType',
    );

    // Verify breakpoint_group_load().
    $compare_set = is_null($compare_set) ? entity_load('breakpoint_group', $group->id()) : $compare_set;

    foreach ($properties as $property) {
      $t_args = array(
        '%group' => $group->label(),
        '%property' => $property,
      );
      if (is_array($compare_set->{$property})) {
        $this->assertEqual(array_keys($compare_set->{$property}), array_keys($group->{$property}), format_string('breakpoint_group_load: Proper %property for breakpoint group %group.', $t_args), 'Breakpoint API');
      }
      else {
        $t_args = array(
          '%group' => $group->label(),
          '%property' => $property,
          '%property1' => $compare_set->{$property},
          '%property2' => $group->{$property},
        );
        $this->assertEqual($compare_set->{$property}, $group->{$property}, format_string('breakpoint_group_load: Proper %property: %property1 == %property2 for breakpoint group %group.', $t_args), 'Breakpoint API');
      }
    }

    // Ensure that the breakpoint group has the expected breakpoints.
    $this->assertEqual(array_keys($compare_set->getBreakpoints()), array_keys($group->getBreakpoints()));
  }
}
