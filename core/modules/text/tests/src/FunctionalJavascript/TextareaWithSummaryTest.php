<?php

namespace Drupal\Tests\text\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript functionality of the text_textarea_with_summary widget.
 *
 * @group text
 */
class TextareaWithSummaryTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['text', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    $account = $this->drupalCreateUser(['create page content', 'edit own page content']);
    $this->drupalLogin($account);
  }

  /**
   * Helper to test toggling the summary area.
   */
  protected function assertSummaryToggle() {
    $this->drupalGet('node/add/page');
    $widget = $this->getSession()->getPage()->findById('edit-body-wrapper');
    $summary_field = $widget->findField('edit-body-0-summary');

    $this->assertEquals(FALSE, $summary_field->isVisible(), 'Summary field is hidden by default.');
    $this->assertEquals(FALSE, $widget->hasButton('Hide summary'), 'No Hide summary link by default.');

    $widget->pressButton('Edit summary');
    $this->assertEquals(FALSE, $widget->hasButton('Edit summary'), 'Edit summary link is removed after clicking.');
    $this->assertEquals(TRUE, $summary_field->isVisible(), 'Summary field is shown.');

    $widget->pressButton('Hide summary');
    $this->assertEquals(FALSE, $widget->hasButton('Hide summary'), 'Hide summary link is removed after clicking.');
    $this->assertEquals(FALSE, $summary_field->isVisible(), 'Summary field is hidden again.');
    $this->assertEquals(TRUE, $widget->hasButton('Edit summary'), 'Edit summary link is visible again.');
  }

  /**
   * Tests the textSummary javascript behavior.
   */
  public function testTextSummaryBehavior() {
    // Test with field defaults.
    $this->assertSummaryToggle();

    // Repeat test with non-empty field description.
    $body_field = FieldConfig::loadByName('node', 'page', 'body');
    $body_field->set('description', 'Text with Summary field description.');
    $body_field->save();

    $this->assertSummaryToggle();

    // Test summary is shown when non-empty.
    $node = $this->createNode([
      'body' => [
        [
          'value' => $this->randomMachineName(32),
          'summary' => $this->randomMachineName(32),
          'format' => filter_default_format(),
        ],
      ],
    ]);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $page = $this->getSession()->getPage();
    $summary_field = $page->findField('edit-body-0-summary');

    $this->assertEquals(TRUE, $summary_field->isVisible(), 'Non-empty summary field is shown by default.');
  }

}
