<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Condition\CurrentThemeConditionTest.
 */

namespace Drupal\system\Tests\Condition;

use Drupal\Component\Utility\String;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the CurrentThemeCondition plugin.
 *
 * @group Condition
 */
class CurrentThemeConditionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('system', 'theme_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));
  }

  /**
   * Tests the current theme condition.
   */
  public function testCurrentTheme() {
    \Drupal::service('theme_handler')->install(array('test_theme'));

    $manager = \Drupal::service('plugin.manager.condition');
    /** @var $condition \Drupal\Core\Condition\ConditionInterface */
    $condition = $manager->createInstance('current_theme');
    $condition->setConfiguration(array('theme' => 'test_theme'));
    /** @var $condition_negated \Drupal\Core\Condition\ConditionInterface */
    $condition_negated = $manager->createInstance('current_theme');
    $condition_negated->setConfiguration(array('theme' => 'test_theme', 'negate' => TRUE));

    $this->assertEqual($condition->summary(), String::format('The current theme is @theme', array('@theme' => 'test_theme')));
    $this->assertEqual($condition_negated->summary(), String::format('The current theme is not @theme', array('@theme' => 'test_theme')));

    // The expected theme has not been set up yet.
    $this->assertFalse($condition->execute());
    $this->assertTrue($condition_negated->execute());

    // Set the expected theme to be used.
    $this->config('system.theme')->set('default', 'test_theme')->save();
    \Drupal::theme()->resetActiveTheme();

    $this->assertTrue($condition->execute());
    $this->assertFalse($condition_negated->execute());
  }

}
