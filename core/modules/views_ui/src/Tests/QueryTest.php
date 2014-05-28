<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\QueryTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Views;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views_test_data\Plugin\views\query\QueryTest as QueryTestPlugin;

/**
 * Tests query plugins.
 */
class QueryTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Query: UI',
      'description' => 'Tests query plugins UI.',
      'group' => 'Views UI'
    );
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::viewsData().
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['table']['base']['query_id'] = 'query_test';

    return $data;
  }

  /**
   * Tests query plugins settings.
   */
  public function testQueryUI() {
    // Save some query settings.
    $query_settings_path = "admin/structure/views/nojs/display/test_view/default/query";
    $random_value = $this->randomName();
    $this->drupalPostForm($query_settings_path, array('query[options][test_setting]' => $random_value), t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Check that the settings are saved into the view itself.
    $view = Views::getView('test_view');
    $view->initDisplay();
    $view->initQuery();
    $this->assertEqual($random_value, $view->query->options['test_setting'], 'Query settings got saved');
  }

}
