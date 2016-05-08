<?php

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests alteration of arguments passed to \Drupal::moduleHandler->alter().
 *
 * @group Common
 */
class AlterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'common_test');

  /**
   * Tests if the theme has been altered.
   */
  function testDrupalAlter() {
    // This test depends on Bartik, so make sure that it is always the current
    // active theme.
    \Drupal::service('theme_handler')->install(array('bartik'));
    \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('bartik'));

    $array = array('foo' => 'bar');
    $entity = new \stdClass();
    $entity->foo = 'bar';

    // Verify alteration of a single argument.
    $array_copy = $array;
    $array_expected = array('foo' => 'Drupal theme');
    \Drupal::moduleHandler()->alter('drupal_alter', $array_copy);
    \Drupal::theme()->alter('drupal_alter', $array_copy);
    $this->assertEqual($array_copy, $array_expected, 'Single array was altered.');

    $entity_copy = clone $entity;
    $entity_expected = clone $entity;
    $entity_expected->foo = 'Drupal theme';
    \Drupal::moduleHandler()->alter('drupal_alter', $entity_copy);
    \Drupal::theme()->alter('drupal_alter', $entity_copy);
    $this->assertEqual($entity_copy, $entity_expected, 'Single object was altered.');

    // Verify alteration of multiple arguments.
    $array_copy = $array;
    $array_expected = array('foo' => 'Drupal theme');
    $entity_copy = clone $entity;
    $entity_expected = clone $entity;
    $entity_expected->foo = 'Drupal theme';
    $array2_copy = $array;
    $array2_expected = array('foo' => 'Drupal theme');
    \Drupal::moduleHandler()->alter('drupal_alter', $array_copy, $entity_copy, $array2_copy);
    \Drupal::theme()->alter('drupal_alter', $array_copy, $entity_copy, $array2_copy);
    $this->assertEqual($array_copy, $array_expected, 'First argument to \Drupal::moduleHandler->alter() was altered.');
    $this->assertEqual($entity_copy, $entity_expected, 'Second argument to \Drupal::moduleHandler->alter() was altered.');
    $this->assertEqual($array2_copy, $array2_expected, 'Third argument to \Drupal::moduleHandler->alter() was altered.');

    // Verify alteration order when passing an array of types to \Drupal::moduleHandler->alter().
    // common_test_module_implements_alter() places 'block' implementation after
    // other modules.
    $array_copy = $array;
    $array_expected = array('foo' => 'Drupal block theme');
    \Drupal::moduleHandler()->alter(array('drupal_alter', 'drupal_alter_foo'), $array_copy);
    \Drupal::theme()->alter(array('drupal_alter', 'drupal_alter_foo'), $array_copy);
    $this->assertEqual($array_copy, $array_expected, 'hook_TYPE_alter() implementations ran in correct order.');
  }

}
