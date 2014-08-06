<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointGroupAPITest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointsTestBase;
use Drupal\breakpoint\Entity\BreakpointGroup;
use Drupal\breakpoint\Entity\Breakpoint;
use Drupal\breakpoint\InvalidBreakpointNameException;
use Drupal\breakpoint\InvalidBreakpointSourceException;
use Drupal\breakpoint\InvalidBreakpointSourceTypeException;
use Drupal\Component\Utility\Unicode;

/**
 * Tests general API functions of the breakpoint module.
 *
 * @group breakpoint
 */
class BreakpointGroupAPITest extends BreakpointGroupTestBase {

  /**
   * Test Breakpoint::buildConfigName().
   */
  public function testConfigName() {
    // Try an invalid sourceType.
    $label = $this->randomMachineName();
    $breakpoint_group = entity_create('breakpoint_group', array(
      'label' => $label,
      'name' => drupal_strtolower($label),
      'source' => 'custom_module',
      'sourceType' => 'oops',
    ));

    $exception = FALSE;
    try {
      $breakpoint_group->save();
    }
    catch (InvalidBreakpointSourceTypeException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, 'An exception is thrown when an invalid sourceType is entered.');

    // Try an invalid source.
    $breakpoint_group = $breakpoint_group->createDuplicate();
    $breakpoint_group->name = '';
    $breakpoint_group->sourceType = Breakpoint::SOURCE_TYPE_USER_DEFINED;
    $breakpoint_group->source = 'custom*_module source';

    $exception = FALSE;
    try {
      $breakpoint_group->save();
    }
    catch (InvalidBreakpointSourceException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, 'An exception is thrown when an invalid source is entered.');

    // Try a valid breakpoint_group.
    $breakpoint_group = $breakpoint_group->createDuplicate();
    $breakpoint_group->name = 'test';
    $breakpoint_group->source = 'custom_module_source';

    $exception = FALSE;
    try {
      $breakpoint_group->save();
    }
    catch (\Exception $e) {
      $exception = TRUE;
    }
    $this->assertFalse($exception, 'No exception is thrown when a valid data is passed.');
  }
}
