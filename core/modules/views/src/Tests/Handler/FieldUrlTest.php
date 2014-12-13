<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldUrlTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Component\Utility\String;
use Drupal\Core\Url;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Url handler.
 *
 * @group views
 */
class FieldUrlTest extends ViewUnitTestBase {

  public static $modules = array('system');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Test URLs.
   *
   * @var array
   *   Associative array, keyed by test URL, with a boolean value indicating
   *   whether this is a valid URL.
   */
  protected $urls = array(
    'http://www.drupal.org/' => TRUE,
    '<front>' => TRUE,
    'admin' => TRUE,
    '/admin' => TRUE,
    'some-non-existing-local-path' => FALSE,
    '/some-non-existing-local-path' => FALSE,
    '<script>alert("xss");</script>' => FALSE,
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'url_alias');
  }

  /**
   * {@inheritdoc}
   */
  function viewsData() {
    // Reuse default data, changing the ID from a numeric field to a URL field.
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'url';
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function dataSet() {
    $dataset = array();
    foreach ($this->urls as $url => $valid) {
      $dataset[] = array('name' => $url);
    }
    return $dataset;
  }

  /**
   * Tests the URL field handler.
   */
  public function testFieldUrl() {
    $expected = array();
    foreach ($this->urls as $url => $valid) {
      // In any case, the URL that is shown should always be properly escaped.
      $text = String::checkPlain($url);

      // If the URL is not rendered as a link, it should just be shown as is.
      $expected[FALSE][] = $text;

      // If the URL is rendered as a link and is a valid, it should be rendered
      // normally. If it is not valid, it should be treated as a local resource.
      $url = $valid ? \Drupal::service('path.validator')->getUrlIfValidWithoutAccessCheck($url) : Url::fromUri('base://' . trim($url, '/'));
      $expected[TRUE][] = \Drupal::l($text, $url);
    }

    $view = Views::getView('test_view');
    foreach ($expected as $display_as_link => $results) {
      $view->setDisplay();

      $view->displayHandlers->get('default')->overrideOption('fields', array(
        'name' => array(
          'id' => 'name',
          'table' => 'views_test_data',
          'field' => 'name',
          'relationship' => 'none',
          'display_as_link' => $display_as_link,
        ),
      ));

      $this->executeView($view);

      foreach ($results as $key => $result) {
        $this->assertEqual($result, $view->field['name']->advancedRender($view->result[$key]));
      }

      $view->destroy();
    }
  }

}
