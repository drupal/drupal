<?php

/**
 * @file
 * Contains \Drupal\language\Tests\Views\FieldLanguageTest.
 */

namespace Drupal\language\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the field language handler.
 *
 * @see \Drupal\language\Plugin\views\field\Language
 */
class FieldLanguageTest extends LanguageTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Field: Language',
      'description' => 'Tests the field language handler.',
      'group' => 'Views Handlers',
    );
  }

  /**
   * Tests the language field.
   */
  public function testField() {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('fields', array(
      'langcode' => array(
        'id' => 'langcode',
        'table' => 'views_test_data',
        'field' => 'langcode',
      ),
    ));
    $this->executeView($view);

    $this->assertEqual($view->field['langcode']->advancedRender($view->result[0]), 'English');
    $this->assertEqual($view->field['langcode']->advancedRender($view->result[1]), 'Lolspeak');
  }

}
