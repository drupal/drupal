<?php

namespace Drupal\views\Tests\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

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
  public static $testViews = array('test_exposed_form_buttons', 'test_exposed_block', 'test_exposed_form_sort_items_per_page');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'views_ui', 'block', 'entity_test');

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));

    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode(array('type' => 'article'));
    }
  }

  /**
   * Tests the submit button.
   */
  public function testSubmitButton() {
    // Test the submit button value defaults to 'Apply'.
    $this->drupalGet('test_exposed_form_buttons');
    $this->assertResponse(200);
    $this->helperButtonHasLabel('edit-submit-test-exposed-form-buttons', t('Apply'));

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
    $this->helperButtonHasLabel('edit-submit-test-exposed-form-buttons', t('Apply'));
  }

  /**
   * Tests the exposed form with a non-standard identifier.
   */
  public function testExposedIdentifier() {
    // Alter the identifier of the filter to a random string.
    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();
    $identifier = 'new_identifier';
    $view->displayHandlers->get('default')->overrideOption('filters', array(
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
          'description' => 'Exposed overridden description'
        ],
      ]
    ));
    $view->save();
    $this->drupalGet('test_exposed_form_buttons', array('query' => array($identifier => 'article')));
    $this->assertFieldById(Html::getId('edit-' . $identifier), 'article', "Article type filter set with new identifier.");

    // Alter the identifier of the filter to a random string containing
    // restricted characters.
    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();
    $identifier = 'bad identifier';
    $view->displayHandlers->get('default')->overrideOption('filters', array(
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
          'description' => 'Exposed overridden description'
        ],
      ]
    ));
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
    $this->assertNoField('edit-reset');

    $this->drupalGet('test_exposed_form_buttons', array('query' => array('type' => 'article')));
    // Test that the type has been set.
    $this->assertFieldById('edit-type', 'article', 'Article type filter set.');

    // Test the reset works.
    $this->drupalGet('test_exposed_form_buttons', array('query' => array('op' => 'Reset')));
    $this->assertResponse(200);
    // Test the type has been reset.
    $this->assertFieldById('edit-type', 'All', 'Article type filter has been reset.');

    // Test the button is hidden after reset.
    $this->assertNoField('edit-reset');

    // Test the reset works with type set.
    $this->drupalGet('test_exposed_form_buttons', array('query' => array('type' => 'article', 'op' => 'Reset')));
    $this->assertResponse(200);
    $this->assertFieldById('edit-type', 'All', 'Article type filter has been reset.');

    // Test the button is hidden after reset.
    $this->assertNoField('edit-reset');

    // Rename the label of the reset button.
    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();

    $exposed_form = $view->display_handler->getOption('exposed_form');
    $exposed_form['options']['reset_button_label'] = $expected_label = $this->randomMachineName();
    $exposed_form['options']['reset_button'] = TRUE;
    $view->display_handler->setOption('exposed_form', $exposed_form);
    $view->save();

    // Look whether the reset button label changed.
    $this->drupalGet('test_exposed_form_buttons', array('query' => array('type' => 'article')));
    $this->assertResponse(200);

    $this->helperButtonHasLabel('edit-reset', $expected_label);
  }

  /**
   * Tests the exposed form markup.
   */
  public function testExposedFormRender() {
    $view = Views::getView('test_exposed_form_buttons');
    $this->executeView($view);
    $exposed_form = $view->display_handler->getPlugin('exposed_form');
    $output = $exposed_form->renderExposedForm();
    $this->setRawContent(\Drupal::service('renderer')->renderRoot($output));

    $this->assertFieldByXpath('//form/@id', $this->getExpectedExposedFormId($view), 'Expected form ID found.');

    $view->setDisplay('page_1');
    $expected_action = $view->display_handler->getUrlInfo()->toString();
    $this->assertFieldByXPath('//form/@action', $expected_action, 'The expected value for the action attribute was found.');
    // Make sure the description is shown.
    $result = $this->xpath('//form//div[contains(@id, :id) and normalize-space(text())=:description]', array(':id' => 'edit-type--description', ':description' => t('Exposed description')));
    $this->assertEqual(count($result), 1, 'Filter description was found.');
  }

  /**
   * Tests the exposed block functionality.
   */
  public function testExposedBlock() {
    $this->drupalCreateContentType(['type' => 'page']);
    $view = Views::getView('test_exposed_block');
    $view->setDisplay('page_1');
    $block = $this->drupalPlaceBlock('views_exposed_filter_block:test_exposed_block-page_1');
    $this->drupalGet('test_exposed_block');

    // Test there is an exposed form in a block.
    $xpath = $this->buildXPathQuery('//div[@id=:id]/form/@id', array(':id' => Html::getUniqueId('block-' . $block->id())));
    $this->assertFieldByXpath($xpath, $this->getExpectedExposedFormId($view), 'Expected form found in views block.');

    // Test there is not an exposed form in the view page content area.
    $xpath = $this->buildXPathQuery('//div[@class="view-content"]/form/@id', array(':id' => Html::getUniqueId('block-' . $block->id())));
    $this->assertNoFieldByXpath($xpath, $this->getExpectedExposedFormId($view), 'No exposed form found in views content region.');

    // Test there is only one views exposed form on the page.
    $elements = $this->xpath('//form[@id=:id]', array(':id' => $this->getExpectedExposedFormId($view)));
    $this->assertEqual(count($elements), 1, 'One exposed form block found.');

    // Test that the correct option is selected after form submission.
    $this->assertCacheContext('url');
    $this->assertOptionSelected('edit-type', 'All');
    foreach (['All', 'article', 'page'] as $argument) {
      $this->drupalGet('test_exposed_block', ['query' => ['type' => $argument]]);
      $this->assertCacheContext('url');
      $this->assertOptionSelected('edit-type', $argument);
    }
  }

  /**
   * Test the input required exposed form type.
   */
  public function testInputRequired() {
    $view = entity_load('view', 'test_exposed_form_buttons');
    $display = &$view->getDisplay('default');
    $display['display_options']['exposed_form']['type'] = 'input_required';
    $view->save();

    $this->drupalGet('test_exposed_form_buttons');
    $this->assertResponse(200);
    $this->helperButtonHasLabel('edit-submit-test-exposed-form-buttons', t('Apply'));

    // Ensure that no results are displayed.
    $rows = $this->xpath("//div[contains(@class, 'views-row')]");
    $this->assertEqual(count($rows), 0, 'No rows are displayed by default when no input is provided.');

    $this->drupalGet('test_exposed_form_buttons', array('query' => array('type' => 'article')));

    // Ensure that results are displayed.
    $rows = $this->xpath("//div[contains(@class, 'views-row')]");
    $this->assertEqual(count($rows), 5, 'All rows are displayed by default when input is provided.');
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
    $this->drupalGet('test_exposed_form_buttons', array('query' => array('type' => 'article')));
    $this->assertNoText($on_demand_text);
  }

  /**
   * Tests exposed forms with exposed sort and items per page.
   */
  public function testExposedSortAndItemsPerPage() {
    for ($i = 0; $i < 50; $i++) {
      $entity = EntityTest::create([
      ]);
      $entity->save();
    }
    $contexts = [
      'languages:language_interface',
      'entity_test_view_grants',
      'theme',
      'url.query_args',
      'languages:language_content'
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
      $actual_ids[] = (int) $element;
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

}
