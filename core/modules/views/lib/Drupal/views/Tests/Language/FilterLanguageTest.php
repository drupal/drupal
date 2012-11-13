<?php

/**
 * @file
 * Contains Drupal\views\Tests\Language\FilterLanguageTest.
 */

namespace Drupal\views\Tests\Language;

use Drupal\Core\Language\Language;

/**
 * Tests the filter language handler.
 *
 * @see Drupal\language\Plugin\views\filter\Language
 */
class FilterLanguageTest extends LanguageTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Filter: Language',
      'description' => 'Tests the filter language handler.',
      'group' => 'Views Handlers'
    );
  }

  /**
   * Tests the language filter.
   */
  public function testFilter() {
    foreach (array('en' => 'John', 'xx-lolspeak' => 'George') as $langcode => $name) {
      $view = $this->getView();
      $view->displayHandlers['default']->overrideOption('filters', array(
        'langcode' => array(
          'id' => 'langcode',
          'table' => 'views_test_data',
          'field' => 'langcode',
          'value' => array($langcode),
        ),
      ));
      $this->executeView($view);

      $expected = array(array(
        'name' => $name,
      ));
      $this->assertIdenticalResultset($view, $expected, array('views_test_data_name' => 'name'));
      $view->destroy();
    }
  }

}
