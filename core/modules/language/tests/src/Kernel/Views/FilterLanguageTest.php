<?php

namespace Drupal\Tests\language\Kernel\Views;

use Drupal\views\Views;

/**
 * Tests the filter language handler.
 *
 * @group language
 * @see \Drupal\language\Plugin\views\filter\Language
 */
class FilterLanguageTest extends LanguageTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Tests the language filter.
   */
  public function testFilter() {
    $view = Views::getView('test_view');
    foreach (array('en' => 'John', 'xx-lolspeak' => 'George') as $langcode => $name) {
      $view->setDisplay();
      $view->displayHandlers->get('default')->overrideOption('filters', array(
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

      $expected = [
        '***LANGUAGE_site_default***',
        '***LANGUAGE_language_interface***',
        '***LANGUAGE_language_content***',
        'en',
        'xx-lolspeak',
        'und',
        'zxx'
      ];
      $this->assertIdentical(array_keys($view->filter['langcode']->getValueOptions()), $expected);

      $view->destroy();
    }
  }

}
