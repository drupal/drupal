<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\ExposedFormTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests exposed forms functionality.
 *
 * @group views
 */
class ExposedFormTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_exposed_form_buttons', 'test_exposed_block');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'views_ui', 'block');

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
    $this->drupalSetContent(drupal_render($output));

    $this->assertFieldByXpath('//form/@id', $this->getExpectedExposedFormId($view), 'Expected form ID found.');

    $expected_action = _url($view->display_handler->getUrl());
    $this->assertFieldByXPath('//form/@action', $expected_action, 'The expected value for the action attribute was found.');
  }

  /**
   * Tests the exposed block functionality.
   */
  public function testExposedBlock() {
    $view = Views::getView('test_exposed_block');
    $view->setDisplay('page_1');
    $block = $this->drupalPlaceBlock('views_exposed_filter_block:test_exposed_block-page_1');
    $this->drupalGet('test_exposed_block');

    // Test there is an exposed form in a block.
    $xpath = $this->buildXPathQuery('//div[@id=:id]/form/@id', array(':id' => drupal_html_id('block-' . $block->id())));
    $this->assertFieldByXpath($xpath, $this->getExpectedExposedFormId($view), 'Expected form found in views block.');

    // Test there is not an exposed form in the view page content area.
    $xpath = $this->buildXPathQuery('//div[@class="view-content"]/form/@id', array(':id' => drupal_html_id('block-' . $block->id())));
    $this->assertNoFieldByXpath($xpath, $this->getExpectedExposedFormId($view), 'No exposed form found in views content region.');

    // Test there is only one views exposed form on the page.
    $elements = $this->xpath('//form[@id=:id]', array(':id' => $this->getExpectedExposedFormId($view)));
    $this->assertEqual(count($elements), 1, 'One exposed form block found.');
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
