<?php

namespace Drupal\KernelTests\Core\Plugin\Condition;

use Drupal\Component\Render\FormattableMarkup;
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
  protected static $modules = ['system', 'theme_test'];

  /**
   * Tests the current theme condition.
   */
  public function testCurrentTheme() {
    \Drupal::service('theme_installer')->install(['test_theme']);

    $manager = \Drupal::service('plugin.manager.condition');
    /** @var $condition \Drupal\Core\Condition\ConditionInterface */
    $condition = $manager->createInstance('current_theme');
    $condition->setConfiguration(['theme' => 'test_theme']);
    /** @var $condition_negated \Drupal\Core\Condition\ConditionInterface */
    $condition_negated = $manager->createInstance('current_theme');
    $condition_negated->setConfiguration(['theme' => 'test_theme', 'negate' => TRUE]);

    $this->assertEqual(new FormattableMarkup('The current theme is @theme', ['@theme' => 'test_theme']), $condition->summary());
    $this->assertEqual(new FormattableMarkup('The current theme is not @theme', ['@theme' => 'test_theme']), $condition_negated->summary());

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
