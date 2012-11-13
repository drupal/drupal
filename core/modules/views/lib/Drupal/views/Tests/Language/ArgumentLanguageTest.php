<?php

/**
 * @file
 * Contains Drupal\views\Tests\Language\ArgumentLanguageTest.
 */

namespace Drupal\views\Tests\Language;

use Drupal\Core\Language\Language;

/**
 * Tests the argument language handler.
 *
 * @see Drupal\language\Plugin\views\argument\Language.php
 */
class ArgumentLanguageTest extends LanguageTestBase {

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
    foreach (array('en' => 'John', 'xx-lolspeak' => 'George') as $langcode => $name) {
      $view = $this->getView();
      $view->displayHandlers['default']->overrideOption('arguments', array(
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
