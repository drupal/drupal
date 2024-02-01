<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\user\Entity\User;

// cSpell:ignore downlander

/**
 * Tests the Manage Display page of a fieldable entity type.
 *
 * @group field_ui
 * @group #slow
 */
class ManageFieldsTest extends BrowserTestBase {

  use FieldUiTestTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_test',
    'field_ui',
    'field_ui_test',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer node fields, etc.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer node fields']);
    $this->drupalLogin($this->adminUser);
    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_DISPLAY_ALL)
      ->save();
  }

  /**
   * Tests drop button operations on the manage fields page.
   */
  public function testFieldDropButtonOperations() {
    $assert_session = $this->assertSession();

    $node_type = $this->drupalCreateContentType();
    $bundle = $node_type->id();

    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'type' => 'string',
        'field_name' => 'highlander',
        'entity_type' => 'node',
      ]);
    $storage->save();

    $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $bundle,
      ])
      ->save();

    $this->drupalGet("/admin/structure/types/manage/{$bundle}/fields");

    // Check that the summary element for the string field type exists and has
    // the correct text (which comes from the FieldItemBase class).
    $element = $assert_session->elementExists('css', '#highlander');
    $summary = $assert_session->elementExists('css', '.field-settings-summary-cell > ul > li', $element);
    $field_label = $this->container->get('plugin.manager.field.field_type')->getDefinitions()['string']['label'];
    $this->assertEquals($field_label, $summary->getText());

    // Add an entity reference field, and check that its summary is custom.
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'type' => 'entity_reference',
        'field_name' => 'downlander',
        'entity_type' => 'node',
        'settings' => [
          'target_type' => 'node',
        ],
      ]);
    $storage->save();

    $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $bundle,
        'entity_type' => 'node',
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [$bundle => $bundle],
          ],
        ],
      ])
      ->save();

    $this->drupalGet("/admin/structure/types/manage/{$bundle}/fields");
    $element = $assert_session->elementExists('css', '#downlander');
    $custom_summary_text = 'Reference type: Content';
    $allowed_bundles_text = "Content type: $bundle";
    $this->assertStringContainsString($custom_summary_text, $element->getText());
    $this->assertStringContainsString($allowed_bundles_text, $element->getText());
  }

  /**
   * Tests adding a field.
   */
  public function testAddField() {
    $page = $this->getSession()->getPage();
    $type = $this->drupalCreateContentType([
      'name' => 'Article',
      'type' => 'article',
    ]);

    // Make sure field descriptions appear, both 1 line and multiple lines.
    $this->drupalGet('/admin/structure/types/manage/' . $type->id() . '/fields/add-field');
    $edit = [
      'new_storage_type' => 'field_test_descriptions',
    ];
    $this->submitForm($edit, 'Continue');
    $this->assertSession()->pageTextContains('This one-line field description is important for testing');
    $this->assertSession()->pageTextContains('This multiple line description needs to use an array');
    $this->assertSession()->pageTextContains('This second line contains important information');

    // Create a new field without actually saving it.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $type->id(), 'test_field', 'Test field', 'test_field', [], [], FALSE);
    // Assert that the field was not created.
    $this->assertNull(FieldStorageConfig::loadByName('node', "field_test_field"));

    $this->drupalGet('/admin/structure/types/manage/' . $type->id() . '/fields/add-field');
    $edit = [
      'label' => 'Test field',
      'field_name' => 'test_field',
      'new_storage_type' => 'test_field',
    ];
    $this->submitForm($edit, 'Continue');
    $this->assertSession()->statusMessageNotContains('Saved');

    // Change the storage form values.
    $edit = ['field_storage[subform][cardinality_number]' => 5];
    $this->submitForm($edit, 'Update settings');
    $this->assertSession()->statusMessageNotContains('Saved');

    // Assert that the form values persist.
    $this->assertEquals(5, $page->findField('field_storage[subform][cardinality_number]')->getValue());

    // Try creating a field with the same machine name.
    $this->drupalGet('/admin/structure/types/manage/' . $type->id() . '/fields/add-field');
    $edit = [
      'label' => 'Test field',
      'field_name' => 'test_field',
      'new_storage_type' => 'test_field',
    ];
    $this->submitForm($edit, 'Continue');
    // Assert that the values in the field storage form are reset.
    $this->assertEquals(1, $page->findField('field_storage[subform][cardinality_number]')->getValue());

    // Assert that the field is created with the new settings.
    $this->submitForm([], 'Update settings');
    $this->assertSession()->statusMessageNotContains('Saved');
    $this->submitForm([], 'Save settings');
    $this->assertSession()->statusMessageContains('Saved');

    $this->assertEquals(1, FieldStorageConfig::loadByName('node', 'field_test_field')->getCardinality());
  }

  /**
   * Tests multiple users adding a field with the same name.
   */
  public function testAddFieldWithMultipleUsers() {
    $page = $this->getSession()->getPage();
    // Create two users.
    $user1 = $this->drupalCreateUser(['administer node fields']);
    $user2 = $this->drupalCreateUser(['administer node fields']);

    $node_type = $this->drupalCreateContentType();
    $bundle_path = '/admin/structure/types/manage/' . $node_type->id();

    // Start adding a field as user 1, stop prior to saving, but keep the URL.
    $this->drupalLogin($user1);
    $this->drupalGet($bundle_path . '/fields/add-field');
    $edit = [
      'label' => 'Test field',
      'field_name' => 'test_field',
      'new_storage_type' => 'test_field',
    ];
    $this->submitForm($edit, 'Continue');
    // Make changes to the storage form.
    $edit = ['field_storage[subform][cardinality_number]' => 5];
    $storage_form_url = $this->getUrl();
    $this->submitForm($edit, 'Update settings');
    $this->drupalLogout();

    // Actually add a field as user 2.
    $this->drupalLogin($user2);
    $this->drupalGet($bundle_path . '/fields/add-field');
    $edit = [
      'label' => 'Test field',
      'field_name' => 'test_field',
      'new_storage_type' => 'test_field',
    ];
    $this->submitForm($edit, 'Continue');
    $allowed_no_of_values = $page->findField('field_storage[subform][cardinality_number]')->getValue();
    // Assert that the changes made by any user do not affect other users until
    // the field is saved.
    $this->assertEquals(1, $allowed_no_of_values);
    $this->submitForm(['field_storage[subform][cardinality_number]' => 2], 'Update settings');
    $this->submitForm([], 'Save settings');
    $this->assertSession()->pageTextContains("Saved Test field configuration.");
    $this->drupalLogout();

    // Continue adding a field as user 1, using the URL saved previously.
    $this->drupalLogin($user1);
    $this->drupalGet($storage_form_url);
    // Assert that the user can go on with configuring a field with a machine
    // that is already taken.
    $this->assertSession()->pageTextNotContains('error');
    $this->submitForm([], 'Save settings');
    // An error is thrown only after the final 'Save'.
    $this->assertSession()->statusMessageContains("An error occurred while saving the field: 'field_storage_config' entity with ID 'node.field_test_field' already exists.");
  }

  /**
   * Tests editing field when the field exists in temp store.
   */
  public function testEditFieldWithLeftOverFieldInTempStore() {
    $user = $this->drupalCreateUser(['administer node fields']);

    $node_type = $this->drupalCreateContentType();
    $bundle_path = '/admin/structure/types/manage/' . $node_type->id();

    // Start adding a field but stop prior to saving.
    $this->drupalLogin($user);
    $this->drupalGet($bundle_path . '/fields/add-field');
    $edit = [
      'label' => 'Test field',
      'field_name' => 'test_field',
      'new_storage_type' => 'test_field',
    ];
    $this->submitForm($edit, 'Continue');

    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'type' => 'test_field',
        'field_name' => 'test_field',
        'entity_type' => 'node',
      ]);
    $storage->save();

    $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $node_type->id(),
        'entity_type' => 'node',
      ])
      ->save();

    $this->drupalGet("$bundle_path/fields/node.{$node_type->id()}.test_field");
    $this->submitForm([], 'Save settings');
    $this->assertSession()->statusMessageContains('Saved test_field configuration.', 'status');
  }

  /**
   * Tests creating entity reference field to non-bundleable entity type.
   */
  public function testEntityReferenceToNonBundleableEntity() {
    $type = $this->drupalCreateContentType([
      'name' => 'kittens',
      'type' => 'kittens',
    ]);
    $bundle_path = 'admin/structure/types/manage/' . $type->id();
    $field_name = 'field_user_reference';

    $field_edit = [
      'set_default_value' => '1',
      "default_value_input[$field_name][0][target_id]" => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    ];
    $this->fieldUIAddNewField($bundle_path, 'user_reference', NULL, 'field_ui:entity_reference:user', [], $field_edit);
    $field = FieldConfig::loadByName('node', 'kittens', $field_name);
    $this->assertEquals([['target_id' => $this->adminUser->id()]], $field->getDefaultValue(User::create(['name' => '1337'])));
  }

  /**
   * Tests hook_form_field_storage_config_form_edit_alter().
   *
   * @group legacy
   */
  public function testFieldStorageFormAlter() {
    $this->container->get('module_installer')->install(['field_ui_test_deprecated']);
    $this->rebuildContainer();

    $node_type = $this->drupalCreateContentType();
    $bundle = $node_type->id();
    $this->expectDeprecation('The deprecated alter hook hook_form_field_storage_config_edit_form_alter() is implemented in these functions: field_ui_test_deprecated_form_field_storage_config_edit_form_alter. Use hook_form_field_config_edit_form_alter() instead. See https://www.drupal.org/node/3386675.');
    $this->drupalGet("/admin/structure/types/manage/$bundle/fields/node.$bundle.body");
    $this->assertSession()->elementTextContains('css', '#edit-field-storage', 'Greetings from the field_storage_config_edit_form() alter.');
  }

  /**
   * Tests hook_form_field_storage_config_form_edit_alter().
   *
   * @group legacy
   */
  public function testFieldTypeCardinalityAlter() {
    $node_type = $this->drupalCreateContentType();
    $bundle = $node_type->id();

    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'type' => 'test_field',
        'field_name' => 'field_test_field',
        'entity_type' => 'node',
      ]);
    $storage->save();

    $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $bundle,
        'entity_type' => 'node',
      ])
      ->save();

    $this->drupalGet("/admin/structure/types/manage/$bundle/fields/node.$bundle.field_test_field");
    $this->assertSession()->elementTextContains('css', '#edit-field-storage', 'Greetings from Drupal\field_test\Plugin\Field\FieldType\TestItem::storageSettingsForm');
  }

  /**
   * Tests hook_field_info_entity_type_ui_definitions_alter().
   */
  public function testFieldUiDefinitionsAlter() {
    $user = $this->drupalCreateUser(['administer node fields']);
    $node_type = $this->drupalCreateContentType();
    $this->drupalLogin($user);
    $this->drupalGet('/admin/structure/types/manage/' . $node_type->id() . '/fields/add-field');
    $this->assertSession()->pageTextContains('Boolean (overridden by alter)');
  }

  /**
   * Ensure field category fallback works for field types without a description.
   */
  public function testFieldCategoryFallbackWithoutDescription() {
    $user = $this->drupalCreateUser(['administer node fields']);
    $node_type = $this->drupalCreateContentType();
    $this->drupalLogin($user);
    $this->drupalGet('/admin/structure/types/manage/' . $node_type->id() . '/fields/add-field');
    $field_type = $this->assertSession()->elementExists('xpath', '//label[text()="Test field"]');
    $description_container = $field_type->getParent()->find('css', '.field-option__description');
    $this->assertNotNull($description_container);
    $this->assertEquals('', $description_container->getText());
  }

}
