<?php

namespace Drupal\Tests\field\Functional\String;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the creation of string fields.
 *
 * @group text
 */
class StringFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'file'];

  /**
   * A user without any special permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['view test entity', 'administer entity_test content', 'access content']);
    $this->drupalLogin($this->webUser);
  }

  // Test fields.

  /**
   * Test widgets.
   */
  public function testTextfieldWidgets() {
    $this->_testTextfieldWidgets('string', 'string_textfield');
    $this->_testTextfieldWidgets('string_long', 'string_textarea');
  }

  /**
   * Helper function for testTextfieldWidgets().
   */
  public function _testTextfieldWidgets($field_type, $widget_type) {
    // Create a field.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $field_type,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
    ])->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => $widget_type,
        'settings' => [
          'placeholder' => 'A placeholder on ' . $widget_type,
        ],
      ])
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name)
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget is displayed');
    $this->assertNoFieldByName("{$field_name}[0][format]", '1', 'Format selector is not displayed');
    $this->assertRaw(format_string('placeholder="A placeholder on @widget_type"', ['@widget_type' => $widget_type]));

    // Submit with some value.
    $value = $this->randomMachineName();
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]), 'Entity was created');

    // Display the entity.
    $entity = EntityTest::load($id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $rendered_entity = \Drupal::service('renderer')->renderRoot($content);
    $this->assertContains($value, (string) $rendered_entity);
  }

}
