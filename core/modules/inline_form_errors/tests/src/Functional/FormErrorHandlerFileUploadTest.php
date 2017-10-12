<?php

namespace Drupal\Tests\inline_form_errors\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests file upload scenario's with Inline Form Errors.
 *
 * @group inline_form_errors
 */
class FormErrorHandlerFileUploadTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'file', 'field_ui', 'inline_form_errors'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    // Add a file field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_ief_file',
      'type' => 'file',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_ief_file',
      'label' => 'field_ief_file',
      'entity_type' => 'node',
      'bundle' => 'page',
      'required' => TRUE,
      'settings' => ['file_extensions' => 'png gif jpg jpeg'],
    ])->save();

    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_ief_file', [
      'type' => 'file_generic',
      'settings' => [],
    ])->save();

    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
      'label' => 'hidden',
      'type' => 'file_default',
    ])->save();

    // Create and login a user.
    $account = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer nodes',
      'create page content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests that the required field error is displayed as inline error message.
   */
  public function testFileUploadErrors() {
    $this->drupalGet('node/add/page');
    $edit = [
      'edit-title-0-value' => $this->randomString(),
    ];
    $this->submitForm($edit, t('Save'));

    $error_text = $this->getSession()->getPage()->find('css', '.field--name-field-ief-file .form-item--error-message')->getText();

    $this->assertEquals('field_ief_file field is required.', $error_text);
  }

}
