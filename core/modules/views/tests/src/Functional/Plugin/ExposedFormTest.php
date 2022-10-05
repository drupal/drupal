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

  /**
   * Nodes to test.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);

    $this->nodes = [];
    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->nodes[] = $this->drupalCreateNode(['type' => 'article']);
      $this->nodes[] = $this->drupalCreateNode(['type' => 'page']);
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
    $this->assertEquals($expected, $errors);
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
   *
   * @dataProvider providerTestExposedBlock
   */
  public function testExposedBlock($display) {
    $view = Views::getView('test_exposed_block');
    $view->setDisplay($display);
    $block = $this->drupalPlaceBlock('views_exposed_filter_block:test_exposed_block-' . $display);

    // Set label to display on the exposed filter form block.
    $block->getPlugin()->setConfigurationValue('label_display', TRUE);
    $block->save();

    // Assert that the only two occurrences of `$view->getTitle()` are the title
    // and h2 tags.
    $this->drupalGet('test_exposed_block');
    $this->assertSession()->elementContains('css', 'title', $view->getTitle());
    $this->assertSession()->elementExists('xpath', '//h2[text()="' . $view->getTitle() . '"]');
    $this->assertSession()->pageTextMatchesCount(2, '/' . $view->getTitle() . '/');

    // Set a custom label on the exposed filter form block.
    $block->getPlugin()->setConfigurationValue('views_label', '<strong>Custom</strong> title<script>alert("hacked!");</script>');
    $block->save();

    // Test that the custom block label is found.
    $this->drupalGet('test_exposed_block');
    $this->assertSession()->responseContains('<strong>Custom</strong> titlealert("hacked!");');

    // Set label to hidden on the exposed filter form block.
    $block->getPlugin()->setConfigurationValue('label_display', FALSE);
    $block->save();

    // Test that the label is removed.
    // Assert that the only occurrence of `$view->getTitle()` is the title tag
    // now that label has been removed.
    $this->drupalGet('test_exposed_block');
    $this->assertSession()->responseNotContains('<strong>Custom</strong> titlealert("hacked!");');
    $this->assertSession()->elementContains('css', 'title', $view->getTitle());
    $this->assertSession()->pageTextMatchesCount(1, '/' . $view->getTitle() . '/');

    // Test there is an exposed form in a block.
    $this->assertSession()->elementsCount('xpath', '//div[@id="' . Html::getUniqueId('block-' . $block->id()) . '"]/form/@id', 1);

    // Test there is not an exposed form in the view page content area.
    $xpath = $this->assertSession()->buildXPathQuery('//div[@class="view-content"]/form/@id', [
      ':id' => Html::getUniqueId('block-' . $block->id()),
    ]);
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // Test there is only one views exposed form on the page.
    $xpath = '//form[@id="' . $this->getExpectedExposedFormId($view) . '"]';
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
    $element = $this->assertSession()->elementExists('xpath', $xpath);

    // Test that the correct option is selected after form submission.
    $this->assertCacheContext('url');
    $this->assertTrue($this->assertSession()->optionExists('Content: Type', 'All')->isSelected());
    $arguments = [
      'All' => ['article', 'page'],
      'article' => ['article'],
      'page' => ['page'],
    ];
    foreach ($arguments as $argument => $bundles) {
      $element->find('css', 'select')->selectOption($argument);
      $element->findButton('Apply')->click();
      $this->assertCacheContext('url');
      $this->assertTrue($this->assertSession()->optionExists('Content: Type', $argument)->isSelected());
      $this->assertNodesExist($bundles);
    }
    $element->findButton('Reset')->click();
    $this->assertNodesExist($arguments['All']);
  }

  /**
   * Data provider for testing different types of displays.
   *
   * @return array
   *   Array of display names to test.
   */
  public function providerTestExposedBlock() {
    return [
      'page_display' => ['page_1'],
      'block_display' => ['block_1'],
    ];
  }

  /**
   * Tests the input required exposed form type.
   */
  public function testInputRequired() {
    $view = View::load('test_exposed_form_buttons');
    $display = &$view->getDisplay('default');
    $display['display_options']['exposed_form']['type'] = 'input_required';
    $view->save();

    $this->drupalGet('test_exposed_form_buttons');
    $this->assertSession()->statusCodeEquals(200);
    $this->helperButtonHasLabel('edit-submit-test-exposed-form-buttons', 'Apply');

    // Ensure that no results are displayed by default when no input is
    // provided.
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'views-row')]");

    $this->drupalGet('test_exposed_form_buttons', ['query' => ['type' => 'article']]);

    // Ensure that results are displayed by default when input is provided.
    $this->assertSession()->elementsCount('xpath', "//div[contains(@class, 'views-row')]", 5);
  }

  /**
   * Tests the "on demand text" for the input required exposed form type.
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
    $this->assertSession()->pageTextContains('Select any filter and click Apply to see results.');

    // Ensure that the "on demand text" is not displayed when an exposed filter
    // is applied.
    $this->drupalGet('test_exposed_form_buttons', ['query' => ['type' => 'article']]);
    $this->assertSession()->pageTextNotContains($on_demand_text);
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

    $view = Views::getView('test_exposed_form_sort_items_per_page');
    $view->setDisplay();
    $sorts = $view->display_handler->getOption('sorts');
    // Change the label to something with special characters.
    $sorts['id']['expose']['label'] = $expected_label = "<script>alert('unsafe&dangerous');</script>";
    // Use a custom sort field identifier.
    $sorts['id']['expose']['field_identifier'] = $field_identifier = $this->randomMachineName() . '-_.~';
    $view->display_handler->setOption('sorts', $sorts);
    $view->save();

    // Test label escaping.
    $this->drupalGet('test_exposed_form_sort_items_per_page');
    $options = $this->assertSession()->selectExists('edit-sort-by')->findAll('css', 'option');
    $this->assertCount(1, $options);
    // Check option existence by option label.
    $this->assertSession()->optionExists('Sort by', $expected_label);
    // Check option existence by option value.
    $this->assertSession()->optionExists('Sort by', $field_identifier);
    $escape_1 = Html::escape($expected_label);
    $escape_2 = Html::escape($escape_1);
    // Make sure we see the single-escaped string in the raw output.
    $this->assertSession()->responseContains($escape_1);
    // But no double-escaped string.
    $this->assertSession()->responseNotContains($escape_2);
    // And not the raw label, either.
    $this->assertSession()->responseNotContains($expected_label);

    // Check that the custom field identifier is used in the URL query string.
    $this->submitForm(['sort_order' => 'DESC'], 'Apply');
    $this->assertCacheContexts($contexts);
    $this->assertIds(range(50, 41));
    $url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('sort_by=' . urlencode($field_identifier), $url);
  }

  /**
   * Checks whether the specified ids are the ones displayed in the view output.
   *
   * @param int[] $ids
   *   The ids to check.
   *
   * @internal
   */
  protected function assertIds(array $ids): void {
    $elements = $this->cssSelect('div.view-test-exposed-form-sort-items-per-page div.views-row span.field-content');
    $actual_ids = [];
    foreach ($elements as $element) {
      $actual_ids[] = (int) $element->getText();
    }

    $this->assertSame($ids, $actual_ids);
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
    $this->assertSession()->responseContains("Apply");
    $this->assertSession()->responseContains('<div class="views-row">');

    $this->submitForm([], 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $form = $this->cssSelect('form.views-exposed-form');
    $this->assertNotEmpty($form, 'The exposed form element was found.');
    // Ensure the exposed form is rendered after submitting the normal form.
    $this->assertSession()->responseContains("Apply");
    $this->assertSession()->responseContains('<div class="views-row">');
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

  /**
   * Asserts that nodes of only given bundles exist.
   *
   * @param array $bundles
   *   Bundles of nodes.
   *
   * @internal
   */
  protected function assertNodesExist(array $bundles): void {
    foreach ($this->nodes as $node) {
      if (in_array($node->bundle(), $bundles)) {
        $this->assertSession()->pageTextContains($node->label());
      }
      else {
        $this->assertSession()->pageTextNotContains($node->label());
      }
    }
  }

}
