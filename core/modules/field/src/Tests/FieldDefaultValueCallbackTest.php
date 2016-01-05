<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldDefaultValueCallbackTest.
 */

namespace Drupal\field\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the default value callback.
 *
 * @group field
 */
class FieldDefaultValueCallbackTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_test', 'field_ui');

  /**
   * The field name.
   *
   * @var string
   */
  private $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fieldName = 'field_test';

    // Create Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
        'type' => 'article',
        'name' => 'Article',
      ));
    }

  }

  public function testDefaultValueCallbackForm() {
    // Create a field and storage for checking.
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text',
    ])->save();
    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => $this->fieldName,
      'bundle' => 'article',
    ]);
    $field_config->save();

    $this->drupalLogin($this->rootUser);

    // Check that the default field form is visible when no callback is set.
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_test');
    $this->assertFieldByName('default_value_input[field_test][0][value]', NULL, 'The default field form is visible.');

    // Set a different field value, it should be on the field.
    $default_value = $this->randomString();
    $field_config->setDefaultValue([['value' => $default_value]])->save();
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_test');
    $this->assertFieldByName('default_value_input[field_test][0][value]', $default_value, 'The default field form is visible.');

    // Set a different field value to the field directly, instead of an array.
    $default_value = $this->randomString();
    $field_config->setDefaultValue($default_value)->save();
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_test');
    $this->assertFieldByName('default_value_input[field_test][0][value]', $default_value, 'The default field form is visible.');

    // Set a default value callback instead, and the default field form should
    // not be visible.
    $field_config->setDefaultValueCallback('\Drupal\field\Tests\FieldDefaultValueCallbackProvider::calculateDefaultValue')->save();
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_test');
    $this->assertNoFieldByName('default_value_input[field_test][0][value]', 'Calculated default value', 'The default field form is not visible when a callback is defined.');
  }

}
