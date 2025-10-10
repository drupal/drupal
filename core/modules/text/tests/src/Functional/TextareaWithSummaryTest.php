<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the functionality of the text_textarea_with_summary widget.
 */
#[Group('text')]
class TextareaWithSummaryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['text_test', 'node'];

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
   * Tests validation constraints for a field with delta-specific violations.
   *
   * @see \Drupal\text_test\Hook\TextTestHooks::entityBundleFieldInfoAlter()
   */
  public function testTextAreaWithSummaryValidation(): void {
    // Create a field for validation testing.
    $entity_type_id = 'node';
    $field_name = $this->randomMachineName();
    $entity_type_manager = $this->container->get('entity_type.manager');
    $field_storage = $entity_type_manager->getStorage('field_storage_config')->create([
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      'type' => 'text_with_summary',
      'cardinality' => 2,
    ]);
    $field_storage->save();
    $bundle_id = 'page';
    $field_label = $this->randomMachineName() . '_label';
    $entity_type_manager->getStorage('field_config')->create([
      'field_storage' => $field_storage,
      'bundle' => $bundle_id,
      'label' => $field_label,
      'settings' => [
        'display_summary' => TRUE,
        'required_summary' => FALSE,
      ],
    ])->save();

    // Add the created field to the form.
    $this->container
      ->get('entity_display.repository')
      ->getFormDisplay($entity_type_id, $bundle_id)
      ->setComponent($field_name, ['type' => 'text_textarea_with_summary'])
      ->save();

    // Enable delta-specific validation for the field.
    $this->container->get('state')->set('field_test_constraint', $field_name);
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    // Create a node to verify that validation works.
    $value = $this->randomMachineName();
    $this->drupalGet('node/add/page');
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      "{$field_name}[0][value]" => $value,
      "{$field_name}[1][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusMessageContains("A content item with $field_label $value already exists.");
  }

}
