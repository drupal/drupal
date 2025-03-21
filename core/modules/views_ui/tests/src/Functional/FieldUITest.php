<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\views\Views;

/**
 * Tests the UI of field handlers.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\field\FieldPluginBase
 */
class FieldUITest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_view',
    'test_aggregate_count',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
  ];

  /**
   * Tests the UI of field handlers.
   */
  public function testFieldUI(): void {
    // Ensure the field is not marked as hidden on the first run.
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertSession()->pageTextContains('Views test: Name');
    $this->assertSession()->pageTextNotContains('Views test: Name [hidden]');

    // Hides the field and check whether the hidden label is appended.
    $edit_handler_url = 'admin/structure/views/nojs/handler/test_view/default/field/name';
    $this->drupalGet($edit_handler_url);
    $this->submitForm(['options[exclude]' => TRUE], 'Apply');

    $this->assertSession()->pageTextContains('Views test: Name [hidden]');

    // Ensure that the expected tokens appear in the UI.
    $edit_handler_url = 'admin/structure/views/nojs/handler/test_view/default/field/age';
    $this->drupalGet($edit_handler_url);
    $xpath = '//details[@id="edit-options-alter-help"]/ul/li';
    $this->assertSession()->elementTextEquals('xpath', $xpath, '{{ age }} == Age');

    $edit_handler_url = 'admin/structure/views/nojs/handler/test_view/default/field/id';
    $this->drupalGet($edit_handler_url);
    $this->assertSession()->elementTextEquals('xpath', "{$xpath}[1]", '{{ age }} == Age');
    $this->assertSession()->elementTextEquals('xpath', "{$xpath}[2]", '{{ id }} == ID');

    $edit_handler_url = 'admin/structure/views/nojs/handler/test_view/default/field/name';
    $this->drupalGet($edit_handler_url);
    $this->assertSession()->elementTextEquals('xpath', "{$xpath}[1]", '{{ age }} == Age');
    $this->assertSession()->elementTextEquals('xpath', "{$xpath}[2]", '{{ id }} == ID');
    $this->assertSession()->elementTextEquals('xpath', "{$xpath}[3]", '{{ name }} == Name');

    $this->assertSession()->elementNotExists('xpath', '//details[@id="edit-options-more"]');

    // Ensure that dialog titles are not escaped.
    $edit_groupby_url = 'admin/structure/views/nojs/handler/test_view/default/field/name';
    $this->assertSession()->linkByHrefNotExists($edit_groupby_url, 0, 'No aggregation link found.');

    // Enable aggregation on the view.
    $edit = [
      'group_by' => TRUE,
    ];
    $this->drupalGet('/admin/structure/views/nojs/display/test_view/default/group_by');
    $this->submitForm($edit, 'Apply');

    $this->assertSession()->linkByHrefExists($edit_groupby_url, 0, 'Aggregation link found.');

    $edit_handler_url = '/admin/structure/views/ajax/handler-group/test_view/default/field/name';
    $this->drupalGet($edit_handler_url);
    $data = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEquals('Configure aggregation settings for field Views test: Name', $data[3]['dialogOptions']['title']);
  }

  /**
   * Tests the field labels.
   */
  public function testFieldLabel(): void {
    // Create a view with unformatted style and make sure the fields have no
    // labels by default.
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = $this->randomMachineName(16);
    $view['description'] = $this->randomMachineName(16);
    $view['show[wizard_key]'] = 'node';
    $view['page[create]'] = TRUE;
    $view['page[style][style_plugin]'] = 'default';
    $view['page[title]'] = $this->randomMachineName(16);
    $view['page[path]'] = $view['id'];
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');

    $view = Views::getView($view['id']);
    $view->initHandlers();
    $this->assertEquals('', $view->field['title']->options['label'], 'The field label for normal styles are empty.');
  }

  /**
   * Tests the UI of field aggregation settings.
   */
  public function testFieldAggregationSettings(): void {
    $edit_handler_url = 'admin/structure/views/nojs/handler-group/test_aggregate_count/default/field/id';
    $this->drupalGet($edit_handler_url);
    $this->submitForm(['options[group_type]' => 'count'], 'Apply');
    $this->assertSession()
      ->pageTextNotContains('The website encountered an unexpected error. Try again later.');
    $this->drupalGet($edit_handler_url);
    $dropdown = $this->getSession()->getPage()->find('named', ['select', 'options[group_column]']);
    // Ensure the dropdown for group column exists.
    $this->assertNotNull($dropdown, 'The dropdown for options[group_column] does not exist.');
    $this->submitForm(['options[group_type]' => 'count'], 'Apply');
    // Ensure that there is no error after submitting the form.
    $this->assertSession()
      ->pageTextNotContains('The website encountered an unexpected error. Try again later.');
  }

}
