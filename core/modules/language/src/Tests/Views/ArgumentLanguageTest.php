<?php

/**
 * @file
 * Contains \Drupal\language\Tests\Views\ArgumentLanguageTest.
 */

namespace Drupal\language\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the argument language handler.
 *
 * @see \Drupal\language\Plugin\views\argument\Language.php
 */
class ArgumentLanguageTest extends LanguageTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Argument: Language',
      'description' => 'Tests the argument language handler.',
      'group' => 'Views Handlers'
    );
  }

  /**
   * Tests the language argument.
   */
  public function testArgument() {
    $view = Views::getView('test_view');
    foreach (array('en' => 'John', 'xx-lolspeak' => 'George') as $langcode => $name) {
      $view->setDisplay();
      $view->displayHandlers->get('default')->overrideOption('arguments', array(
        'langcode' => array(
          'id' => 'langcode',
          'table' => 'views_test_data',
          'field' => 'langcode',
        ),
      ));
      $this->executeView($view, array($langcode));

      $expected = array(array(
        'name' => $name,
      ));
      $this->assertIdenticalResultset($view, $expected, array('views_test_data_name' => 'name'));
      $view->destroy();
    }
  }

}
