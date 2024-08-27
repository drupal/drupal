<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;
use Drupal\views_test_data\Plugin\views\filter\FilterTest as FilterPlugin;

/**
 * Tests general filter plugin functionality.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\filter\FilterPluginBase
 */
class FilterTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter', 'test_filter_in_operator_ui'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    $this->drupalLogin($this->drupalCreateUser(['administer views']));
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['filter']['id'] = 'test_filter';

    return $data;
  }

  /**
   * Tests query of the row plugin.
   */
  public function testFilterQuery(): void {
    // Check that we can find the test filter plugin.
    $plugin = $this->container->get('plugin.manager.views.filter')->createInstance('test_filter');
    $this->assertInstanceOf(FilterPlugin::class, $plugin);

    $view = Views::getView('test_filter');
    $view->initDisplay();

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_filter' => [
        'id' => 'test_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '=',
        'value' => 'John',
        'group' => 0,
      ],
    ]);

    $this->executeView($view);

    // Make sure the query have where data.
    $this->assertNotEmpty($view->query->where);

    // Check the data added.
    $where = $view->query->where;
    $this->assertSame('views_test_data.name', $where[0]['conditions'][0]['field'], 'Where condition field matches');
    $this->assertSame('John', $where[0]['conditions'][0]['value'], 'Where condition value matches');
    $this->assertSame('=', $where[0]['conditions'][0]['operator'], 'Where condition operator matches');

    $this->executeView($view);

    // Check that our operator and value match on the filter.
    $this->assertSame('=', $view->filter['test_filter']->operator);
    $this->assertSame('John', $view->filter['test_filter']->value);

    // Check that we have a single element, as a result of applying the '= John'
    // filter.
    $this->assertCount(1, $view->result, 'Results were returned. ' . count($view->result) . ' results.');

    $view->destroy();

    $view->initDisplay();

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_filter' => [
        'id' => 'test_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '<>',
        'value' => 'John',
        'group' => 0,
      ],
    ]);

    $this->executeView($view);

    // Check that our operator and value match on the filter.
    $this->assertSame('<>', $view->filter['test_filter']->operator);
    $this->assertSame('John', $view->filter['test_filter']->value);

    // Check if we have the other elements in the dataset, as a result of
    // applying the '<> John' filter.
    $this->assertCount(4, $view->result, 'Results were returned. ' . count($view->result) . ' results.');

    $view->destroy();
    $view->initDisplay();

    // Set the test_enable option to FALSE. The 'where' clause should not be
    // added to the query.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_filter' => [
        'id' => 'test_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '<>',
        'value' => 'John',
        'group' => 0,
        // Disable this option, so nothing should be added to the query.
        'test_enable' => FALSE,
      ],
    ]);

    // Execute the view again.
    $this->executeView($view);

    // Check if we have all 5 results.
    $this->assertCount(5, $view->result, 'All ' . count($view->displayHandlers) . ' results returned');
  }

  /**
   * Tests an exposed filter when all options are selected.
   */
  public function testInOperatorSelectAllOptions(): void {
    $row['row[type]'] = 'fields';
    $this->drupalGet('admin/structure/views/nojs/display/test_filter_in_operator_ui/default/row');
    $this->submitForm($row, 'Apply');
    $field['name[node_field_data.nid]'] = TRUE;
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_filter_in_operator_ui/default/field');
    $this->submitForm($field, 'Add and configure fields');
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/field/nid');
    $this->submitForm([], 'Apply');
    $edit['options[value][all]'] = TRUE;
    $edit['options[value][article]'] = TRUE;
    $edit['options[value][page]'] = TRUE;
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/type');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_filter_in_operator_ui/edit/default');
    $this->submitForm([], 'Save');
    $this->submitForm([], 'Update preview');
    $this->assertSession()->pageTextNotContains('The submitted value "page" in the Type element is not allowed.');
  }

  /**
   * Tests the limit of the expose operator functionality.
   */
  public function testLimitExposedOperators(): void {

    $this->drupalGet('test_filter_in_operator_ui');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->optionExists('edit-nid-op', '<');
    $this->assertSession()->optionExists('edit-nid-op', '<=');
    $this->assertSession()->optionExists('edit-nid-op', '=');
    $this->assertSession()->optionNotExists('edit-nid-op', '>');
    $this->assertSession()->optionNotExists('edit-nid-op', '>=');

    // Because there are not operators that use the min and max fields, those
    // fields should not be in the exposed form.
    $this->assertSession()->fieldExists('edit-nid-value');
    $this->assertSession()->fieldNotExists('edit-nid-min');
    $this->assertSession()->fieldNotExists('edit-nid-max');

    $edit = [];
    $edit['options[operator]'] = '>';
    $edit['options[expose][operator_list][]'] = ['>', '>=', 'between'];
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/nid');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_filter_in_operator_ui/edit/default');
    $this->submitForm([], 'Save');

    $this->drupalGet('test_filter_in_operator_ui');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->optionNotExists('edit-nid-op', '<');
    $this->assertSession()->optionNotExists('edit-nid-op', '<=');
    $this->assertSession()->optionNotExists('edit-nid-op', '=');
    $this->assertSession()->optionExists('edit-nid-op', '>');
    $this->assertSession()->optionExists('edit-nid-op', '>=');

    $this->assertSession()->fieldExists('edit-nid-value');
    $this->assertSession()->fieldExists('edit-nid-min');
    $this->assertSession()->fieldExists('edit-nid-max');

    // Set the default to an excluded operator.
    $edit = [];
    $edit['options[operator]'] = '=';
    $edit['options[expose][operator_list][]'] = ['<', '>'];
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/nid');
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('You selected the "Is equal to" operator as the default value but is not included in the list of limited operators.');
  }

}
