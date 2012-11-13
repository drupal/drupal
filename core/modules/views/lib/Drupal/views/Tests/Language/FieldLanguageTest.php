<?php

/**
 * @file
 * Contains Drupal\views\Tests\Language\FieldLanguageTest.
 */

namespace Drupal\views\Tests\Language;

use Drupal\Core\Language\Language;

/**
 * Tests the field language handler.
 *
 * @see Drupal\language\Plugin\views\field\Language
 */
class FieldLanguageTest extends LanguageTestBase {

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
    $view = $this->getView();
    $view->displayHandlers['default']->overrideOption('fields', array(
      'langcode' => array(
        'id' => 'langcode',
        'table' => 'views_test_data',
        'field' => 'langcode',
      ),
    ));
    $this->executeView($view);

    $this->assertEqual($view->field['langcode']->advanced_render($view->result[0]), 'English');
    $this->assertEqual($view->field['langcode']->advanced_render($view->result[1]), 'Lolspeak');
  }

}
