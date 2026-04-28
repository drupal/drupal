<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests read-only mode for CKEditor 5.
 *
 * @internal
 */
#[Group('ckeditor5')]
#[RunTestsInSeparateProcesses]
class CKEditor5ReadOnlyModeTest extends CKEditor5TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5_read_only_mode',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createCkeditorField('field_second_ckeditor5_field', 'Second CKEditor5 field');
    $this->createCkeditorField('field_ckeditor5_disable', 'CKEditor5 field which is disabled on toggle');
    $this->createCkeditorField('field_ckeditor5_enable', 'CKEditor5 field which is enabled on toggle');
    $this->createField('field_ckeditor5_toggle', 'CKEditor5 Toggle', 'boolean', 'boolean_checkbox');
    $this->addNewTextFormat();
  }

  /**
   * Create a CKEditor 5 field on the page node.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $field_label
   *   The label for the field.
   */
  protected function createCkeditorField(string $field_name, string $field_label): void {
    $this->createField($field_name, $field_label, 'text_long', 'text_textarea');
  }

  /**
   * Create a field on the page node.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $field_label
   *   The label for the field.
   * @param string $field_type
   *   The type of the field.
   * @param string $field_widget
   *   The widget for the field.
   */
  protected function createField(string $field_name, string $field_label, string $field_type, string $field_widget): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_type,
      'cardinality' => 1,
    ]);
    $field_storage->save();

    // Attach an instance of the field to the page content type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => $field_label,
    ])->save();
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page')
      ->setComponent($field_name, [
        'type' => $field_widget,
      ])
      ->save();
  }

  /**
   * Test that disabling a CKEditor 5 field results in an uneditable editor.
   */
  public function testReadOnlyMode(): void {
    $assert_session = $this->assertSession();

    // Check that both CKEditor 5 fields are editable.
    $this->drupalGet('node/add');
    $assert_session->elementAttributeContains('css', '.field--name-body .ck-editor .ck-content', 'contenteditable', 'true');
    $assert_session->elementAttributeContains('css', '.field--name-field-second-ckeditor5-field .ck-editor .ck-content', 'contenteditable', 'true');

    $this->container->get('state')->set('ckeditor5_read_only_mode_body_enabled', TRUE);

    // Check that the first body field is no longer editable.
    $this->drupalGet('node/add');
    $assert_session->elementAttributeContains('css', '.field--name-body .ck-editor .ck-content', 'contenteditable', 'false');
    $assert_session->elementAttributeContains('css', '.field--name-field-second-ckeditor5-field .ck-editor .ck-content', 'contenteditable', 'true');

    $this->container->get('state')->set('ckeditor5_read_only_mode_second_ckeditor5_field_enabled', TRUE);

    // Both fields are disabled, check that both fields are no longer editable.
    $this->drupalGet('node/add');
    $assert_session->elementAttributeContains('css', '.field--name-body .ck-editor .ck-content', 'contenteditable', 'false');
    $assert_session->elementAttributeContains('css', '.field--name-field-second-ckeditor5-field .ck-editor .ck-content', 'contenteditable', 'false');
  }

  /**
   * Test that the CKEditor 5 read-only mode is set dynamically.
   */
  public function testReadOnlyModeDynamic(): void {
    $this->drupalGet('node/add');

    $this->assertSession()->elementAttributeContains('css', '.field--name-field-ckeditor5-disable .ck-editor .ck-content', 'contenteditable', 'true');
    $this->assertSession()->elementAttributeContains('css', '.field--name-field-ckeditor5-enable .ck-editor .ck-content', 'contenteditable', 'false');

    $this->getSession()->getPage()->checkField('field_ckeditor5_toggle[value]');

    $this->assertSession()->elementAttributeContains('css', '.field--name-field-ckeditor5-disable .ck-editor .ck-content', 'contenteditable', 'false');
    $this->assertSession()->elementAttributeContains('css', '.field--name-field-ckeditor5-enable .ck-editor .ck-content', 'contenteditable', 'true');

    $this->getSession()->getPage()->uncheckField('field_ckeditor5_toggle[value]');

    $this->assertSession()->elementAttributeContains('css', '.field--name-field-ckeditor5-disable .ck-editor .ck-content', 'contenteditable', 'true');
    $this->assertSession()->elementAttributeContains('css', '.field--name-field-ckeditor5-enable .ck-editor .ck-content', 'contenteditable', 'false');
  }

}
