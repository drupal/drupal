<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\SavingTest.
 */

namespace Drupal\system\Tests\Cache;

use stdClass;

/**
 * Tests that variables are saved and restored in the right way.
 */
class SavingTest extends CacheTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Cache saving test',
      'description' => 'Check our variables are saved and restored the right way.',
      'group' => 'Cache'
    );
  }

  /**
   * Test the saving and restoring of a string.
   */
  function testString() {
    $this->checkVariable($this->randomName(100));
  }

  /**
   * Test the saving and restoring of an integer.
   */
  function testInteger() {
    $this->checkVariable(100);
  }

  /**
   * Test the saving and restoring of a double.
   */
  function testDouble() {
    $this->checkVariable(1.29);
  }

  /**
   * Test the saving and restoring of an array.
   */
  function testArray() {
    $this->checkVariable(array('drupal1', 'drupal2' => 'drupal3', 'drupal4' => array('drupal5', 'drupal6')));
  }

  /**
   * Test the saving and restoring of an object.
   */
  function testObject() {
    $test_object = new stdClass();
    $test_object->test1 = $this->randomName(100);
    $test_object->test2 = 100;
    $test_object->test3 = array('drupal1', 'drupal2' => 'drupal3', 'drupal4' => array('drupal5', 'drupal6'));


    cache()->set('test_object', $test_object);
    $cached = cache()->get('test_object');
    $this->assertTrue(isset($cached->data) && $cached->data == $test_object, t('Object is saved and restored properly.'));
  }

  /**
   * Check or a variable is stored and restored properly.
   */
  function checkVariable($var) {
    cache()->set('test_var', $var);
    $cached = cache()->get('test_var');
    $this->assertTrue(isset($cached->data) && $cached->data === $var, t('@type is saved and restored properly.', array('@type' => ucfirst(gettype($var)))));
  }

  /**
   * Test no empty cids are written in cache table.
   */
  function testNoEmptyCids() {
    $this->drupalGet('user/register');
    $this->assertFalse(cache()->get(''), t('No cache entry is written with an empty cid.'));
  }
}
