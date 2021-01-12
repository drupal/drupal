<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views\Entity\View;

/**
 * Tests exposed forms functionality.
 *
 * @group views
 */
class ExposedFormTest extends ViewTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_exposed_form_buttons', 'test_exposed_block', 'test_exposed_form_sort_items_per_page', 'test_exposed_form_pager'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views_ui', 'block', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();

    $this->drupalCreateContentType(['type' => 'article']);

    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode(['type' => 'article']);
    }
  }

  /**
   * Tests the submit button.
   */
  public function testSubmitButton() {
    // Test the submit button value defaults to 'Apply'.
    $this->drupalGet('test_exposed_form_buttons');
    $this->assertSession()->statusCodeEquals(200);
    $this->helperButtonHasLabel('edit-submit-test-exposed-form-buttons', 'Apply');

    // Rename the label of the submit button.
    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();

    $exposed_form = $view->display_handler->getOption('exposed_form');
    $exposed_form['options']['submit_button'] = $expected_label = $this->randomMachineName();
    $view->display_handler->setOption('exposed_form', $exposed_form);
    $view->save();

    // Make sure the submit button label changed.
    $this->drupalGet('test_exposed_form_buttons');
    $this->helperButtonHasLabel('edit-submit-test-exposed-form-buttons', $expected_label);

    // Make sure an empty label uses the default 'Apply' button value too.
    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();

    $exposed_form = $view->display_handler->getOption('exposed_form');
    $exposed_form['options']['submit_button'] = '';
    $view->display_handler->setOption('exposed_form', $exposed_form);
    $view->save();

    // Make sure the submit button label shows 'Apply'.
    $this->drupalGet('test_exposed_form_buttons');
    $this->helperButtonHasLabel('edit-submit-test-exposed-form-buttons', 'Apply');
  }

  /**
   * Tests the exposed form with a non-standard identifier.
   */
  public function testExposedIdentifier() {
    // Alter the identifier of the filter to a random string.
    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();
    $identifier = 'new_identifier';
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'type' => [
        'exposed' => TRUE,
        'field' => 'type',
        'id' => 'type',
        'table' => 'node_field_data',
        'plugin_id' => 'in_operator',
        'entity_type' => 'node',
        'entity_field' => 'type',
        'expose' => [
          'identifier' => $identifier,
          'label' => 'Content: Type',
          'operator_id' => 'type_op',
          'reduce' => FALSE,
          'description' => 'Exposed overridden description',
        ],
      ],
    ]);
    $view->save();
    $this->drupalGet('test_exposed_form_buttons', ['query' => [$identifier => 'article']]);
    $this->assertSession()->fieldValueEquals(Html::getId('edit-' . $identifier), 'article');

    // Alter the identifier of the filter to a random string containing
    // restricted characters.
    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();
    $identifier = 'bad identifier';
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'type' => [
        'exposed' => TRUE,
        'field' => 'type',
        'id' => 'type',
        'table' => 'node_field_data',
        'plugin_id' => 'in_operator',
        'entity_type' => 'node',
        'entity_field' => 'type',
        'expose' => [
          'identifier' => $identifier,
          'label' => 'Content: Type',
          'operator_id' => 'type_op',
          'reduce' => FALSE,
          'description' => 'Exposed overridden description',
        ],
      ],
    ]);
    $this->executeView($view);

    $errors = $view->validate();
    $expected = [
      'default' => ['This identifier has illegal characters.'],
      'page_1' => ['This identifier has illegal characters.'],
    ];
    $this->assertEqual($errors, $expected);
  }

  /**
   * Tests whether the reset button works on an exposed form.
   */
  public function testResetButton() {
    // Test the button is hidden when there is no exposed input.
    $this->drupalGet('test_exposed_form_buttons');
    $this->assertSession()->fieldNotExists('edit-reset');

    $this->drupalGet('test_exposed_form_buttons', ['query' => ['type' => 'article']]);
    // Test that the type has been set.
    $this->assertSession()->fieldValueEquals('edit-type', 'article');

    // Test the reset works.
    $this->drupalGet('test_exposed_form_buttons', ['query' => ['op' => 'Reset']]);
    $this->assertSession()->statusCodeEquals(200);
    // Test the type has been reset.
    $this->assertSession()->fieldValueEquals('edit-type', 'All');

    // Test the button is hidden after reset.
    $this->assertSession()->fieldNotExists('edit-reset');

    // Test the reset works with type set.
    $this->drupalGet('test_exposed_form_buttons', ['query' => ['type' => 'article', 'op' => 'Reset']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('edit-type', 'All');

    // Test the button is hidden after reset.
    $this->assertSession()->fieldNotExists('edit-reset');

    // Rename the label of the reset button.
    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();

    $exposed_form = $view->display_handler->getOption('exposed_form');
    $exposed_form['options']['reset_button_label'] = $expected_label = $this->randomMachineName();
    $exposed_form['options']['reset_button'] = TRUE;
    $view->display_handler->setOption('exposed_form', $exposed_form);
    $view->save();

    // Look whether the reset button label changed.
    $this->drupalGet('test_exposed_form_buttons', ['query' => ['type' => 'article']]);
    $this->assertSession()->statusCodeEquals(200);

    $this->helperButtonHasLabel('edit-reset', $expected_label);
  }

  /**
   * Tests the exposed block functionality.
   */
  public function testExposedBlock() {
    $this->drupalCreateContentType(['type' => 'page']);
    $view = Views::getView('test_exposed_block');
    $view->setDisplay('page_1');
    $block = $this->drupalPlaceBlock('views_exposed_filter_block:test_exposed_block-page_1');

    // Set label to display on the exposed filter form block.
    $block->getPlugin()->setConfigurationValue('label_display', TRUE);
    $block->save();

    // Test that the block label is found.
    $this->drupalGet('test_exposed_block');
    $this->assertText($view->getTitle(), 'Block title found.');

    // Set a custom label on the exposed filter form block.
    $block->getPlugin()->setConfigurationValue('views_label', '<strong>Custom</strong> title<script>alert("hacked!");</script>');
    $block->save();

    // Test that the custom block label is found.
    $this->drupalGet('test_exposed_block');
    $this->assertRaw('<strong>Custom</strong> titlealert("hacked!");');

    // Set label to hidden on the exposed filter form block.
    $block->getPlugin()->setConfigurationValue('label_display', FALSE);
    $block->save();

    // Test that the label is removed.
    $this->drupalGet('test_exposed_block');
    $this->assertNoRaw('<strong>Custom</strong> titlealert("hacked!");');
    $this->assertNoText($view->getTitle(), 'Block title was not displayed.');

    // Test there is an exposed form in a block.
    $xpath = $this->assertSession()->buildXPathQuery('//div[@id=:id]/form/@id', [':id' => Html::getUniqueId('block-' . $block->id())]);
    $result = $this->xpath($xpath);
    $this->assertCount(1, $result);

    // Test there is not an exposed form in the view page content area.
    $xpath = $this->assertSession()->buildXPathQuery('//div[@class="view-content"]/form/@id', [
      ':id' => Html::getUniqueId('block-' . $block->id()),
    ]);
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // Test there is only one views exposed form on the page.
    $elements = $this->xpath('//form[@id=:id]', [':id' => $this->getExpectedExposedFormId($view)]);
    $this->assertCount(1, $elements, 'One exposed form block found.');

    // Test that the correct option is selected after form submission.
    $this->assertCacheContext('url');
    $this->assertTrue($this->assertSession()->optionExists('Content: Type', 'All')->isSelected());
    foreach (['All', 'article', 'page'] as $argument) {
      $this->drupalGet('test_exposed_block', ['query' => ['type' => $argument]]);
      $this->assertCacheContext('url');
      $this->assertTrue($this->assertSession()->optionExists('Content: Type', $argument)->isSelected());
    }
  }

  /**
   * Test the input required exposed form type.
   */
  public function testInputRequired() {
    $view = View::load('test_exposed_form_buttons');
    $display = &$view->getDisplay('default');
    $display['display_options']['exposed_form']['type'] = 'input_required';
    $view->save();

    $this->drupalGet('test_exposed_form_buttons');
    $this->assertSession()->statusCodeEquals(200);
    $this->helperButtonHasLabel('edit-submit-test-exposed-form-buttons', 'Apply');

    // Ensure that no results are displayed.
    $rows = $this->xpath("//div[contains(@class, 'views-row')]");
    $this->assertCount(0, $rows, 'No rows are displayed by default when no input is provided.');

    $this->drupalGet('test_exposed_form_buttons', ['query' => ['type' => 'article']]);

    // Ensure that results are displayed.
    $rows = $this->xpath("//div[contains(@class, 'views-row')]");
    $this->assertCount(5, $rows, 'All rows are displayed by default when input is provided.');
  }

  /**
   * Test the "on demand text" for the input required exposed form type.
   */
  public function testTextInputRequired() {
    $view = Views::getView('test_exposed_form_buttons');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['exposed_form']['type'] = 'input_required';
    // Set up the "on demand text".
    // @see https://www.drupal.org/node/535868
    $on_demand_text = 'Select any filter and click Apply to see results.';
    $display['display_options']['exposed_form']['options']['text_input_required'] = $on_demand_text;
    $display['display_options']['exposed_form']['options']['text_input_required_format'] = filter_default_format();
    $view->save();

    // Ensure that the "on demand text" is displayed when no exposed filters are
    // applied.
    $this->drupalGet('test_exposed_form_buttons');
    $this->assertText('Select any filter and click Apply to see results.');

    // Ensure that the "on demand text" is not displayed when an exposed filter
    // is applied.
    $this->drupalGet('test_exposed_form_buttons', ['query' => ['type' => 'article']]);
    $this->assertNoText($on_demand_text);
  }

  /**
   * Tests exposed forms with exposed sort and items per page.
   */
  public function testExposedSortAndItemsPerPage() {
    for ($i = 0; $i < 50; $i++) {
      $entity = EntityTest::create([]);
      $entity->save();
    }
    $contexts = [
      'languages:language_interface',
      'entity_test_view_grants',
      'theme',
      'url.query_args',
      'languages:language_content',
    ];

    $this->drupalGet('test_exposed_form_sort_items_per_page');
    $this->assertCacheContexts($contexts);
    $this->assertIds(range(1, 10, 1));

    $this->drupalGet('test_exposed_form_sort_items_per_page', ['query' => ['sort_order' => 'DESC']]);
    $this->assertCacheContexts($contexts);
    $this->assertIds(range(50, 41, 1));

    $this->drupalGet('test_exposed_form_sort_items_per_page', ['query' => ['sort_order' => 'DESC', 'items_per_page' => 25]]);
    $this->assertCacheContexts($contexts);
    $this->assertIds(range(50, 26, 1));

    $this->drupalGet('test_exposed_form_sort_items_per_page', ['query' => ['sort_order' => 'DESC', 'items_per_page' => 25, 'offset' => 10]]);
    $this->assertCacheContexts($contexts);
    $this->assertIds(range(40, 16, 1));

    // Change the label to something with special characters.
    $view = Views::getView('test_exposed_form_sort_items_per_page');
    $view->setDisplay();
    $sorts = $view->display_handler->getOption('sorts');
    $sorts['id']['expose']['label'] = $expected_label = "<script>alert('unsafe&dangerous');</script>";
    $view->display_handler->setOption('sorts', $sorts);
    $view->save();

    $this->drupalGet('test_exposed_form_sort_items_per_page');
    $options = $this->xpath('//select[@id=:id]/option', [':id' => 'edit-sort-by']);
    $this->assertCount(1, $options);
    $this->assertSession()->optionExists('edit-sort-by', $expected_label);
    $escape_1 = Html::escape($expected_label);
    $escape_2 = Html::escape($escape_1);
    // Make sure we see the single-escaped string in the raw output.
    $this->assertRaw($escape_1);
    // But no double-escaped string.
    $this->assertNoRaw($escape_2);
    // And not the raw label, either.
    $this->assertNoRaw($expected_label);
  }

  /**
   * Checks whether the specified ids are the ones displayed in the view output.
   *
   * @param int[] $ids
   *   The ids to check.
   *
   * @return bool
   *   TRUE if ids match, FALSE otherwise.
   */
  protected function assertIds(array $ids) {
    $elements = $this->cssSelect('div.view-test-exposed-form-sort-items-per-page div.views-row span.field-content');
    $actual_ids = [];
    foreach ($elements as $element) {
      $actual_ids[] = (int) $element->getText();
    }

    return $this->assertIdentical($ids, $actual_ids);
  }

  /**
   * Returns a views exposed form ID.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to create an ID for.
   *
   * @return string
   *   The form ID.
   */
  protected function getExpectedExposedFormId(ViewExecutable $view) {
    return Html::cleanCssIdentifier('views-exposed-form-' . $view->storage->id() . '-' . $view->current_display);
  }

  /**
   * Tests a view which is rendered after a form with a validation error.
   */
  public function testFormErrorWithExposedForm() {
    $this->drupalGet('views_test_data_error_form_page');
    $this->assertSession()->statusCodeEquals(200);
    $form = $this->cssSelect('form.views-exposed-form');
    $this->assertNotEmpty($form, 'The exposed form element was found.');
    // Ensure the exposed form is rendered before submitting the normal form.
    $this->assertRaw(t('Apply'));
    $this->assertRaw('<div class="views-row">');

    $this->submitForm([], 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $form = $this->cssSelect('form.views-exposed-form');
    $this->assertNotEmpty($form, 'The exposed form element was found.');
    // Ensure the exposed form is rendered after submitting the normal form.
    $this->assertRaw(t('Apply'));
    $this->assertRaw('<div class="views-row">');
  }

  /**
   * Tests the exposed form with a pager.
   */
  public function testExposedFilterPagination() {
    $this->drupalCreateContentType(['type' => 'post']);
    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode(['type' => 'post']);
    }

    $this->drupalGet('test_exposed_form_pager');
    $this->getSession()->getPage()->fillField('type[]', 'post');
    $this->getSession()->getPage()->fillField('created[min]', '-1 month');
    $this->getSession()->getPage()->fillField('created[max]', '+1 month');

    // Ensure the filters can be applied.
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertTrue($this->assertSession()->optionExists('type[]', 'post')->isSelected());
    $this->assertSession()->fieldValueEquals('created[min]', '-1 month');
    $this->assertSession()->fieldValueEquals('created[max]', '+1 month');

    // Ensure the filters are still applied after pressing next.
    $this->clickLink('Next ›');
    $this->assertTrue($this->assertSession()->optionExists('type[]', 'post')->isSelected());
    $this->assertSession()->fieldValueEquals('created[min]', '-1 month');
    $this->assertSession()->fieldValueEquals('created[max]', '+1 month');
  }

}
