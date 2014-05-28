<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointAPITest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointsTestBase;
use Drupal\breakpoint\Entity\Breakpoint;
use Drupal\breakpoint\InvalidBreakpointNameException;
use Drupal\breakpoint\InvalidBreakpointSourceException;
use Drupal\breakpoint\InvalidBreakpointSourceTypeException;
use Drupal\Component\Utility\Unicode;

/**
 * Tests for general breakpoint API functions.
 */
class BreakpointAPITest extends BreakpointTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint general API functions',
      'description' => 'Test general API functions of the breakpoint module.',
      'group' => 'Breakpoint',
    );
  }

  /**
   * Test Breakpoint::buildConfigName().
   */
  public function testConfigName() {
    // Try an invalid sourceType.
    $label = $this->randomName();
    $breakpoint = entity_create('breakpoint', array(
      'label' => $label,
      'name' => Unicode::strtolower($label),
      'source' => 'custom_module',
      'sourceType' => 'oops',
    ));

    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (InvalidBreakpointSourceTypeException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, 'breakpoint_config_name: An exception is thrown when an invalid sourceType is entered.');

    // Try an invalid source.
    $breakpoint = $breakpoint->createDuplicate();
    $breakpoint->sourceType = Breakpoint::SOURCE_TYPE_USER_DEFINED;
    $breakpoint->source = 'custom*_module source';

    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (InvalidBreakpointSourceException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, 'breakpoint_config_name: An exception is thrown when an invalid source is entered.');

    // Try an invalid name (make sure there is at least once capital letter).
    $breakpoint = $breakpoint->createDuplicate();
    $breakpoint->source = 'custom_module';
    $breakpoint->name = drupal_ucfirst($this->randomName());

    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (InvalidBreakpointNameException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, 'breakpoint_config_name: An exception is thrown when an invalid name is entered.');

    // Try a valid breakpoint.
    $breakpoint = $breakpoint->createDuplicate();
    $breakpoint->name = drupal_strtolower($this->randomName());
    $breakpoint->mediaQuery = 'all';

    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (\Exception $e) {
      $exception = TRUE;
    }
    $this->assertFalse($exception, 'breakpoint_config_name: No exception is thrown when a valid breakpoint is passed.');
    $this->assertEqual($breakpoint->id(), Breakpoint::SOURCE_TYPE_USER_DEFINED . '.custom_module.' . $breakpoint->name, 'breakpoint_config_name: A id is set when a valid breakpoint is passed.');
  }
}
