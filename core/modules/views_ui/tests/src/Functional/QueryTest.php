<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\views\Views;
use Drupal\views\Entity\View;

/**
 * Tests query plugins.
 *
 * @group views_ui
 */
class QueryTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
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
    $view = View::load('test_view');
    $display = &$view->getDisplay('default');
    $display['display_options']['query'] = ['type' => 'query_test'];
    $view->save();

    // Save some query settings.
    $query_settings_path = "admin/structure/views/nojs/display/test_view/default/query";
    $random_value = $this->randomMachineName();
    $this->drupalGet($query_settings_path);
    $this->submitForm(['query[options][test_setting]' => $random_value], 'Apply');
    $this->submitForm([], 'Save');

    // Check that the settings are saved into the view itself.
    $view = Views::getView('test_view');
    $view->initDisplay();
    $view->initQuery();
    $this->assertEquals($random_value, $view->query->options['test_setting'], 'Query settings got saved');
  }

}
