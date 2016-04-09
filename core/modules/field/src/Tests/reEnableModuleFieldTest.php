<?php

namespace Drupal\field\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the behavior of a field module after being disabled and re-enabled.
 *
 * @group field
 */
class reEnableModuleFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'node',
    // We use telephone module instead of test_field because test_field is
    // hidden and does not display on the admin/modules page.
    'telephone'
  );

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));
    $this->drupalLogin($this->drupalCreateUser(array(
      'create article content',
      'edit own article content',
    )));
  }

  /**
   * Test the behavior of a field module after being disabled and re-enabled.
   *
   * @see field_system_info_alter()
   */
  function testReEnabledField() {

    // Add a telephone field to the article content type.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_telephone',
      'entity_type' => 'node',
      'type' => 'telephone',
    ));
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Telephone Number',
    ])->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_telephone', array(
        'type' => 'telephone_default',
        'settings' => array(
          'placeholder' => '123-456-7890',
        ),
      ))
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent('field_telephone', array(
        'type' => 'telephone_link',
        'weight' => 1,
      ))
      ->save();

    // Display the article node form and verify the telephone widget is present.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("field_telephone[0][value]", '', 'Widget found.');

    // Submit an article node with a telephone field so data exist for the
    // field.
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'field_telephone[0][value]' => "123456789",
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('<a href="tel:123456789">');

    // Test that the module can't be uninstalled from the UI while there is data
    // for it's fields.
    $admin_user = $this->drupalCreateUser(array('access administration pages', 'administer modules'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/modules/uninstall');
    $this->assertText("The Telephone number field type is used in the following field: node.field_telephone");

    // Add another telephone field to a different entity type in order to test
    // the message for the case when multiple fields are blocking the
    // uninstallation of a module.
    $field_storage2 = entity_create('field_storage_config', array(
      'field_name' => 'field_telephone_2',
      'entity_type' => 'user',
      'type' => 'telephone',
    ));
    $field_storage2->save();
    FieldConfig::create([
      'field_storage' => $field_storage2,
      'bundle' => 'user',
      'label' => 'User Telephone Number',
    ])->save();

    $this->drupalGet('admin/modules/uninstall');
    $this->assertText("The Telephone number field type is used in the following fields: node.field_telephone, user.field_telephone_2");

    // Delete both fields.
    $field_storage->delete();
    $field_storage2->delete();

    $this->drupalGet('admin/modules/uninstall');
    $this->assertText('Fields pending deletion');
    $this->cronRun();
    $this->assertNoText("The Telephone number field type is used in the following field: node.field_telephone");
    $this->assertNoText('Fields pending deletion');
  }

}
