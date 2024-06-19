<?php

declare(strict_types=1);

namespace Drupal\Tests\text\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
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
  protected static $modules = ['text', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    $account = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Helper to test toggling the summary area.
   *
   * @internal
   */
  protected function assertSummaryToggle(): void {
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
  public function testTextSummaryBehavior(): void {
    // Test with field defaults.
    $this->assertSummaryToggle();

    // Repeat test with non-empty field description.
    $body_field = FieldConfig::loadByName('node', 'page', 'body');
    $body_field->set('description', 'Text with Summary field description.');
    $body_field->save();

    $this->assertSummaryToggle();

    // Repeat test with unlimited cardinality field.
    $body_field_storage = FieldStorageConfig::loadByName('node', 'body');
    $body_field_storage->setCardinality(-1);
    $body_field_storage->save();

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
    $summary_field = $this->getSession()->getPage()->findField('edit-body-0-summary');

    $this->assertEquals(TRUE, $summary_field->isVisible());
  }

  /**
   * Tests that the textSummary behavior is not run for required summary fields.
   */
  public function testTextSummaryRequiredBehavior(): void {
    // Test with field defaults.
    $this->assertSummaryToggle();

    // Create a second field with a required summary.
    $field_name = $this->randomMachineName();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'text_with_summary',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => $this->randomMachineName() . '_label',
      'settings' => [
        'display_summary' => TRUE,
        'required_summary' => TRUE,
      ],
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', 'page')
      ->setComponent($field_name, [
        'type' => 'text_textarea_with_summary',
      ])
      ->save();

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $summary_field = $page->findField('edit-' . $field_name . '-0-summary');

    $this->assertEquals(TRUE, $summary_field->isVisible());
  }

}
