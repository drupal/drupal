<?php
/**
 * @file
 * Definition of Drupal\views\Tests\Language\FieldLanguage.php
 */

namespace Drupal\views\Tests\Language;

use Drupal\Core\Language\Language;

/**
 * Tests the field language handler.
 *
 * @see Views\language\Plugin\views\field\Language
 */
class FieldLanguage extends LanguageTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Field: Language',
      'description' => 'Tests the field language handler.',
      'group' => 'Views Handlers'
    );
  }

  public function testField() {
    $view = $this->getBasicView();
    $view->display['default']->handler->override_option('fields', array(
      'langcode' => array(
        'id' => 'langcode',
        'table' => 'views_test',
        'field' => 'langcode',
      ),
    ));
    $this->executeView($view);

    $this->assertEqual($view->field['langcode']->advanced_render($view->result[0]), 'English');
    $this->assertEqual($view->field['langcode']->advanced_render($view->result[1]), 'Lolspeak');
  }
}
