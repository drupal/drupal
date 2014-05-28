<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointTestBase.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\breakpoint\Entity\Breakpoint;

/**
 * Base class for Breakpoint tests.
 */
abstract class BreakpointTestBase extends WebTestBase {

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
  public function verifyBreakpoint(Breakpoint $breakpoint, Breakpoint $compare_breakpoint = NULL) {
    $properties = array(
      'label',
      'mediaQuery',
      'source',
      'sourceType',
      'weight',
      'multipliers',
    );

    // Verify Breakpoint::load().
    $compare_breakpoint = is_null($compare_breakpoint) ? Breakpoint::load($breakpoint->id()) : $compare_breakpoint;
    foreach ($properties as $property) {
      $t_args = array(
        '%breakpoint' => $breakpoint->label(),
        '%property' => $property,
      );
      $this->assertEqual($compare_breakpoint->{$property}, $breakpoint->{$property}, format_string('Proper %property for breakpoint %breakpoint.', $t_args), 'Breakpoint API');
    }
  }
}
