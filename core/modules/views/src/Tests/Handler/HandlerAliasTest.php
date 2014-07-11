<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\HandlerAliasTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests handler table and field aliases.
 *
 * @group views
 */
class HandlerAliasTest extends ViewUnitTestBase {

  public static $modules = array('user');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter', 'test_alias');

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::viewsData().
   */
  protected function viewsData() {
    $data = parent::viewsData();
    // User the existing test_filter plugin.
    $data['views_test_data_alias']['table']['real table'] = 'views_test_data';
    $data['views_test_data_alias']['name_alias']['filter']['id'] = 'test_filter';
    $data['views_test_data_alias']['name_alias']['filter']['real field'] = 'name';

    return $data;
  }

  public function testPluginAliases() {
    $view = Views::getView('test_filter');
    $view->initDisplay();

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'test_filter' => array(
        'id' => 'test_filter',
        'table' => 'views_test_data_alias',
        'field' => 'name_alias',
        'operator' => '=',
        'value' => 'John',
        'group' => 0,
      ),
    ));

    $this->executeView($view);

    $filter = $view->filter['test_filter'];

    // Check the definition values are present.
    $this->assertIdentical($filter->definition['real table'], 'views_test_data');
    $this->assertIdentical($filter->definition['real field'], 'name');

    $this->assertIdentical($filter->table, 'views_test_data');
    $this->assertIdentical($filter->realField, 'name');

    // Test an existing user uid field.
    $view = Views::getView('test_alias');
    $view->initDisplay();
    $this->executeView($view);

    $filter = $view->filter['uid_raw'];

    $this->assertIdentical($filter->definition['real field'], 'uid');

    $this->assertIdentical($filter->field, 'uid_raw');
    $this->assertIdentical($filter->table, 'users');
    $this->assertIdentical($filter->realField, 'uid');
  }

}
