<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the default value callback.
 *
 * @group field
 */
class FieldDefaultValueCallbackTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'field_test', 'field_ui'];

  /**
   * {@inheritdoc}
   *
   * @todo Remove and fix test to not rely on super user.
   * @see https://www.drupal.org/project/drupal/issues/3437620
   */
  protected bool $usesSuperUserAccessPolicy = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The field name.
   *
   * @var string
   */
  private $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldName = 'field_test';

    // Create Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);
    }

  }

  public function testDefaultValueCallbackForm(): void {
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
    $this->assertSession()->fieldValueEquals('default_value_input[field_test][0][value]', '');

    // Set a different field value, it should be on the field.
    $default_value = $this->randomString();
    $field_config->setDefaultValue([['value' => $default_value]])->save();
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_test');
    $this->assertSession()->fieldValueEquals('default_value_input[field_test][0][value]', $default_value);

    // Set a different field value to the field directly, instead of an array.
    $default_value = $this->randomString();
    $field_config->setDefaultValue($default_value)->save();
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_test');
    $this->assertSession()->fieldValueEquals('default_value_input[field_test][0][value]', $default_value);

    // Set a default value callback instead, and the default field form should
    // not be visible.
    $field_config->setDefaultValueCallback('\Drupal\field_test\FieldDefaultValueCallbackProvider::calculateDefaultValue')->save();
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_test');
    $this->assertSession()->fieldNotExists('default_value_input[field_test][0][value]');
  }

}
