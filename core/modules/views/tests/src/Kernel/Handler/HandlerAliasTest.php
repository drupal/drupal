<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests handler table and field aliases.
 *
 * @group views
 */
class HandlerAliasTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter', 'test_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('user');
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    // User the existing test_filter plugin.
    $data['views_test_data_alias']['table']['real table'] = 'views_test_data';
    $data['views_test_data_alias']['name_alias']['filter']['id'] = 'test_filter';
    $data['views_test_data_alias']['name_alias']['filter']['real field'] = 'name';

    return $data;
  }

  public function testPluginAliases(): void {
    $view = Views::getView('test_filter');
    $view->initDisplay();

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_filter' => [
        'id' => 'test_filter',
        'table' => 'views_test_data_alias',
        'field' => 'name_alias',
        'operator' => '=',
        'value' => 'John',
        'group' => 0,
      ],
    ]);

    $this->executeView($view);

    $filter = $view->filter['test_filter'];

    // Check the definition values are present.
    $this->assertSame('views_test_data', $filter->definition['real table']);
    $this->assertSame('name', $filter->definition['real field']);

    $this->assertSame('views_test_data', $filter->table);
    $this->assertSame('name', $filter->realField);

    // Test an existing user uid field.
    $view = Views::getView('test_alias');
    $view->initDisplay();
    $this->executeView($view);

    $filter = $view->filter['uid_raw'];

    $this->assertSame('uid', $filter->definition['real field']);

    $this->assertSame('uid_raw', $filter->field);
    $this->assertSame('users_field_data', $filter->table);
    $this->assertSame('uid', $filter->realField);
  }

}
