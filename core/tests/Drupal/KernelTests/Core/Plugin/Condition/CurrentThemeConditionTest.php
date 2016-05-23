<?php

namespace Drupal\KernelTests\Core\Plugin\Condition;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\KernelTests\KernelTestBase;

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

    $this->assertEqual($condition->summary(), SafeMarkup::format('The current theme is @theme', array('@theme' => 'test_theme')));
    $this->assertEqual($condition_negated->summary(), SafeMarkup::format('The current theme is not @theme', array('@theme' => 'test_theme')));

    // The expected theme has not been set up yet.
    $this->assertFalse($condition->execute());
    $this->assertTrue($condition_negated->execute());

    // Set the expected theme to be used.
    \Drupal::service('theme_handler')->setDefault('test_theme');
    \Drupal::theme()->resetActiveTheme();

    $this->assertTrue($condition->execute());
    $this->assertFalse($condition_negated->execute());
  }

}
