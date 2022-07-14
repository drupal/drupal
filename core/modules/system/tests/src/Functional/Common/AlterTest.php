<?php

namespace Drupal\Tests\system\Functional\Common;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests alteration of arguments passed to \Drupal::moduleHandler->alter().
 *
 * @group Common
 */
class AlterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'common_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if the theme has been altered.
   */
  public function testDrupalAlter() {
    // This test depends on Olivero, so make sure that it is always the current
    // active theme.
    \Drupal::service('theme_installer')->install(['olivero']);
    \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('olivero'));

    $array = ['foo' => 'bar'];
    $entity = new \stdClass();
    $entity->foo = 'bar';

    // Verify alteration of a single argument.
    $array_copy = $array;
    $array_expected = ['foo' => 'Drupal theme'];
    \Drupal::moduleHandler()->alter('drupal_alter', $array_copy);
    \Drupal::theme()->alter('drupal_alter', $array_copy);
    $this->assertEquals($array_expected, $array_copy, 'Single array was altered.');

    $entity_copy = clone $entity;
    $entity_expected = clone $entity;
    $entity_expected->foo = 'Drupal theme';
    \Drupal::moduleHandler()->alter('drupal_alter', $entity_copy);
    \Drupal::theme()->alter('drupal_alter', $entity_copy);
    $this->assertEquals($entity_expected, $entity_copy, 'Single object was altered.');

    // Verify alteration of multiple arguments.
    $array_copy = $array;
    $array_expected = ['foo' => 'Drupal theme'];
    $entity_copy = clone $entity;
    $entity_expected = clone $entity;
    $entity_expected->foo = 'Drupal theme';
    $array2_copy = $array;
    $array2_expected = ['foo' => 'Drupal theme'];
    \Drupal::moduleHandler()->alter('drupal_alter', $array_copy, $entity_copy, $array2_copy);
    \Drupal::theme()->alter('drupal_alter', $array_copy, $entity_copy, $array2_copy);
    $this->assertEquals($array_expected, $array_copy, 'First argument to \\Drupal::moduleHandler->alter() was altered.');
    $this->assertEquals($entity_expected, $entity_copy, 'Second argument to \\Drupal::moduleHandler->alter() was altered.');
    $this->assertEquals($array2_expected, $array2_copy, 'Third argument to \\Drupal::moduleHandler->alter() was altered.');

    // Verify alteration order when passing an array of types to \Drupal::moduleHandler->alter().
    // common_test_module_implements_alter() places 'block' implementation after
    // other modules.
    $array_copy = $array;
    $array_expected = ['foo' => 'Drupal block theme'];
    \Drupal::moduleHandler()->alter(['drupal_alter', 'drupal_alter_foo'], $array_copy);
    \Drupal::theme()->alter(['drupal_alter', 'drupal_alter_foo'], $array_copy);
    $this->assertEquals($array_expected, $array_copy, 'hook_TYPE_alter() implementations ran in correct order.');
  }

}
