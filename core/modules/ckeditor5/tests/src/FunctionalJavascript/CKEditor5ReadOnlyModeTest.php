<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests read-only mode for CKEditor 5.
 *
 * @group ckeditor5
 * @internal
 */
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
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_second_ckeditor5_field',
      'entity_type' => 'node',
      'type' => 'text_with_summary',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    // Attach an instance of the field to the page content type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Second CKEditor5 field',
    ])->save();
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page')
      ->setComponent('field_second_ckeditor5_field', [
        'type' => 'text_textarea_with_summary',
      ])
      ->save();
  }

  /**
   * Test that disabling a CKEditor 5 field results in an uneditable editor.
   */
  public function testReadOnlyMode() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->addNewTextFormat($page, $assert_session);

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

}
