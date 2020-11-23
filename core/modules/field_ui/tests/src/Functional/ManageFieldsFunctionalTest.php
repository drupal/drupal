<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the Field UI "Manage fields" screen.
 *
 * @group field_ui
 */
class ManageFieldsFunctionalTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_ui',
    'field_test',
    'taxonomy',
    'image',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The ID of the custom content type created for testing.
   *
   * @var string
   */
  protected $contentType;

  /**
   * The label for a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldLabel;

  /**
   * The input name of a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldNameInput;

  /**
   * The name of a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'administer taxonomy',
      'administer taxonomy_term fields',
      'administer taxonomy_term display',
      'administer users',
      'administer account settings',
      'administer user display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->contentType = $type->id();

    // Create random field name with markup to test escaping.
    $this->fieldLabel = '<em>' . $this->randomMachineName(8) . '</em>';
    $this->fieldNameInput = strtolower($this->randomMachineName(8));
    $this->fieldName = 'field_' . $this->fieldNameInput;

    // Create Basic page and Article node types.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a vocabulary named "Tags".
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();

    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
    ];
    $this->createEntityReferenceField('node', 'article', 'field_' . $vocabulary->id(), 'Tags', 'taxonomy_term', 'default', $handler_settings);

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('field_' . $vocabulary->id())
      ->save();
  }

  /**
   * Runs the field CRUD tests.
   *
   * In order to act on the same fields, and not create the fields over and over
   * again the following tests create, update and delete the same fields.
   */
  public function testCRUDFields() {
    $this->manageFieldsPage();
    $this->createField();
    $this->updateField();
    $this->addExistingField();
    $this->cardinalitySettings();
    $this->fieldListAdminPage();
    $this->deleteField();
    $this->addPersistentFieldStorage();
  }

  /**
   * Tests the manage fields page.
   *
   * @param string $type
   *   (optional) The name of a content type.
   */
  public function manageFieldsPage($type = '') {
    $type = empty($type) ? $this->contentType : $type;
    $this->drupalGet('admin/structure/types/manage/' . $type . '/fields');
    // Check all table columns.
    $table_headers = [
      t('Label'),
      t('Machine name'),
      t('Field type'),
      t('Operations'),
    ];
    foreach ($table_headers as $table_header) {
      // We check that the label appear in the table headings.
      $this->assertRaw($table_header . '</th>');
    }

    // Test the "Add field" action link.
    $this->assertSession()->linkExists('Add field');

    // Assert entity operations for all fields.
    $number_of_links = 3;
    $number_of_links_found = 0;
    $operation_links = $this->xpath('//ul[@class = "dropbutton"]/li/a');
    $url = base_path() . "admin/structure/types/manage/$type/fields/node.$type.body";

    foreach ($operation_links as $link) {
      switch ($link->getAttribute('title')) {
        case 'Edit field settings.':
          $this->assertIdentical($url, $link->getAttribute('href'));
          $number_of_links_found++;
          break;

        case 'Edit storage settings.':
          $this->assertIdentical("$url/storage", $link->getAttribute('href'));
          $number_of_links_found++;
          break;

        case 'Delete field.':
          $this->assertIdentical("$url/delete", $link->getAttribute('href'));
          $number_of_links_found++;
          break;
      }
    }

    $this->assertEqual($number_of_links, $number_of_links_found);
  }

  /**
   * Tests adding a new field.
   *
   * @todo Assert properties can be set in the form and read back in
   * $field_storage and $fields.
   */
  public function createField() {
    // Create a test field.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->contentType, $this->fieldNameInput, $this->fieldLabel);
  }

  /**
   * Tests editing an existing field.
   */
  public function updateField() {
    $field_id = 'node.' . $this->contentType . '.' . $this->fieldName;
    // Go to the field edit page.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/' . $field_id . '/storage');
    $this->assertSession()->assertEscaped($this->fieldLabel);

    // Populate the field settings with new settings.
    $string = 'updated dummy test string';
    $edit = [
      'settings[test_field_storage_setting]' => $string,
    ];
    $this->submitForm($edit, 'Save field settings');

    // Go to the field edit page.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/' . $field_id);
    $edit = [
      'settings[test_field_setting]' => $string,
    ];
    $this->assertText('Default value', 'Default value heading is shown');
    $this->submitForm($edit, 'Save settings');

    // Assert the field settings are correct.
    $this->assertFieldSettings($this->contentType, $this->fieldName, $string);

    // Assert redirection back to the "manage fields" page.
    $this->assertSession()->addressEquals('admin/structure/types/manage/' . $this->contentType . '/fields');
  }

  /**
   * Tests adding an existing field in another content type.
   */
  public function addExistingField() {
    // Check "Re-use existing field" appears.
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $this->assertRaw(t('Re-use an existing field'));

    // Check that fields of other entity types (here, the 'comment_body' field)
    // do not show up in the "Re-use existing field" list.
    $this->assertEmpty($this->xpath('//select[@id="edit-existing-storage-name"]//option[@value="comment"]'), 'The list of options respects entity type restrictions.');
    // Validate the FALSE assertion above by also testing a valid one.
    $this->assertNotEmpty($this->xpath('//select[@id="edit-existing-storage-name"]//option[@value=:field_name]', [':field_name' => $this->fieldName]), 'The list of options shows a valid option.');

    // Add a new field based on an existing field.
    $this->fieldUIAddExistingField("admin/structure/types/manage/page", $this->fieldName, $this->fieldLabel . '_2');
  }

  /**
   * Tests the cardinality settings of a field.
   *
   * We do not test if the number can be submitted with anything else than a
   * numeric value. That is tested already in FormTest::testNumber().
   */
  public function cardinalitySettings() {
    $field_edit_path = 'admin/structure/types/manage/article/fields/node.article.body/storage';

    // Assert the cardinality other field cannot be empty when cardinality is
    // set to 'number'.
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => '',
    ];
    $this->drupalPostForm($field_edit_path, $edit, 'Save field settings');
    $this->assertText('Number of values is required.');

    // Submit a custom number.
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 6,
    ];
    $this->drupalPostForm($field_edit_path, $edit, 'Save field settings');
    $this->assertText('Updated field Body field settings.');
    $this->drupalGet($field_edit_path);
    $this->assertSession()->fieldValueEquals('cardinality', 'number');
    $this->assertSession()->fieldValueEquals('cardinality_number', 6);

    // Check that tabs displayed.
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkByHrefExists('admin/structure/types/manage/article/fields/node.article.body');
    $this->assertSession()->linkExists('Field settings');
    $this->assertSession()->linkByHrefExists($field_edit_path);

    // Add two entries in the body.
    $edit = ['title[0][value]' => 'Cardinality', 'body[0][value]' => 'Body 1', 'body[1][value]' => 'Body 2'];
    $this->drupalPostForm('node/add/article', $edit, 'Save');

    // Assert that you can't set the cardinality to a lower number than the
    // highest delta of this field.
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 1,
    ];
    $this->drupalPostForm($field_edit_path, $edit, 'Save field settings');
    $this->assertRaw(t('There is @count entity with @delta or more values in this field.', ['@count' => 1, '@delta' => 2]));

    // Create a second entity with three values.
    $edit = ['title[0][value]' => 'Cardinality 3', 'body[0][value]' => 'Body 1', 'body[1][value]' => 'Body 2', 'body[2][value]' => 'Body 3'];
    $this->drupalPostForm('node/add/article', $edit, 'Save');

    // Set to unlimited.
    $edit = [
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $this->drupalPostForm($field_edit_path, $edit, 'Save field settings');
    $this->assertText('Updated field Body field settings.');
    $this->drupalGet($field_edit_path);
    $this->assertSession()->fieldValueEquals('cardinality', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->assertSession()->fieldValueEquals('cardinality_number', 1);

    // Assert that you can't set the cardinality to a lower number then the
    // highest delta of this field but can set it to the same.
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 1,
    ];
    $this->drupalPostForm($field_edit_path, $edit, 'Save field settings');
    $this->assertRaw(t('There are @count entities with @delta or more values in this field.', ['@count' => 2, '@delta' => 2]));

    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 2,
    ];
    $this->drupalPostForm($field_edit_path, $edit, 'Save field settings');
    $this->assertRaw(t('There is @count entity with @delta or more values in this field.', ['@count' => 1, '@delta' => 3]));

    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 3,
    ];
    $this->drupalPostForm($field_edit_path, $edit, 'Save field settings');
  }

  /**
   * Tests deleting a field from the field edit form.
   */
  protected function deleteField() {
    // Delete the field.
    $field_id = 'node.' . $this->contentType . '.' . $this->fieldName;
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/' . $field_id);
    $this->clickLink(t('Delete'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that persistent field storage appears in the field UI.
   */
  protected function addPersistentFieldStorage() {
    $field_storage = FieldStorageConfig::loadByName('node', $this->fieldName);
    // Persist the field storage even if there are no fields.
    $field_storage->set('persist_with_no_fields', TRUE)->save();
    // Delete all instances of the field.
    foreach ($field_storage->getBundles() as $node_type) {
      // Delete all the body field instances.
      $this->drupalGet('admin/structure/types/manage/' . $node_type . '/fields/node.' . $node_type . '.' . $this->fieldName);
      $this->clickLink(t('Delete'));
      $this->submitForm([], 'Delete');
    }
    // Check "Re-use existing field" appears.
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $this->assertRaw(t('Re-use an existing field'));

    // Ensure that we test with a label that contains HTML.
    $label = $this->randomString(4) . '<br/>' . $this->randomString(4);
    // Add a new field for the orphaned storage.
    $this->fieldUIAddExistingField("admin/structure/types/manage/page", $this->fieldName, $label);
  }

  /**
   * Asserts field settings are as expected.
   *
   * @param $bundle
   *   The bundle name for the field.
   * @param $field_name
   *   The field name for the field.
   * @param $string
   *   The settings text.
   * @param $entity_type
   *   The entity type for the field.
   */
  public function assertFieldSettings($bundle, $field_name, $string = 'dummy test string', $entity_type = 'node') {
    // Assert field storage settings.
    $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
    $this->assertTrue($field_storage->getSetting('test_field_storage_setting') == $string, 'Field storage settings were found.');

    // Assert field settings.
    $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
    $this->assertTrue($field->getSetting('test_field_setting') == $string, 'Field settings were found.');
  }

  /**
   * Tests that the 'field_prefix' setting works on Field UI.
   */
  public function testFieldPrefix() {
    // Change default field prefix.
    $field_prefix = strtolower($this->randomMachineName(10));
    $this->config('field_ui.settings')->set('field_prefix', $field_prefix)->save();

    // Create a field input and label exceeding the new maxlength, which is 22.
    $field_exceed_max_length_label = $this->randomString(23);
    $field_exceed_max_length_input = $this->randomMachineName(23);

    // Try to create the field.
    $edit = [
      'label' => $field_exceed_max_length_label,
      'field_name' => $field_exceed_max_length_input,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->contentType . '/fields/add-field', $edit, 'Save and continue');
    $this->assertText('Machine-readable name cannot be longer than 22 characters but is currently 23 characters long.');

    // Create a valid field.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->contentType, $this->fieldNameInput, $this->fieldLabel);
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/node.' . $this->contentType . '.' . $field_prefix . $this->fieldNameInput);
    $this->assertSession()->pageTextContains($this->fieldLabel . ' settings for ' . $this->contentType);
  }

  /**
   * Tests that default value is correctly validated and saved.
   */
  public function testDefaultValue() {
    // Create a test field storage and field.
    $field_name = 'test';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'test_field',
    ])->save();
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $this->contentType,
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name)
      ->save();

    $admin_path = 'admin/structure/types/manage/' . $this->contentType . '/fields/' . $field->id();
    $element_id = "edit-default-value-input-$field_name-0-value";
    $element_name = "default_value_input[{$field_name}][0][value]";
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals($element_id, '');

    // Check that invalid default values are rejected.
    $edit = [$element_name => '-1'];
    $this->drupalPostForm($admin_path, $edit, 'Save settings');
    $this->assertText("$field_name does not accept the value -1", 'Form validation failed.');

    // Check that the default value is saved.
    $edit = [$element_name => '1'];
    $this->drupalPostForm($admin_path, $edit, 'Save settings');
    $this->assertText("Saved $field_name configuration", 'The form was successfully submitted.');
    $field = FieldConfig::loadByName('node', $this->contentType, $field_name);
    $this->assertEqual($field->getDefaultValueLiteral(), [['value' => 1]], 'The default value was correctly saved.');

    // Check that the default value shows up in the form
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals($element_id, '1');

    // Check that the default value can be emptied.
    $edit = [$element_name => ''];
    $this->submitForm($edit, 'Save settings');
    $this->assertText("Saved $field_name configuration", 'The form was successfully submitted.');
    $field = FieldConfig::loadByName('node', $this->contentType, $field_name);
    $this->assertEqual($field->getDefaultValueLiteral(), [], 'The default value was correctly saved.');

    // Check that the default value can be empty when the field is marked as
    // required and can store unlimited values.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $field_storage->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $field_storage->save();

    $this->drupalGet($admin_path);
    $edit = [
      'required' => 1,
    ];
    $this->submitForm($edit, 'Save settings');

    $this->drupalGet($admin_path);
    $this->submitForm([], 'Save settings');
    $this->assertText("Saved $field_name configuration", 'The form was successfully submitted.');
    $field = FieldConfig::loadByName('node', $this->contentType, $field_name);
    $this->assertEqual($field->getDefaultValueLiteral(), [], 'The default value was correctly saved.');

    // Check that the default widget is used when the field is hidden.
    $display_repository->getFormDisplay($field->getTargetEntityTypeId(), $field->getTargetBundle())
      ->removeComponent($field_name)
      ->save();
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals($element_id, '');
  }

  /**
   * Tests that deletion removes field storages and fields as expected.
   */
  public function testDeleteField() {
    // Create a new field.
    $bundle_path1 = 'admin/structure/types/manage/' . $this->contentType;
    $this->fieldUIAddNewField($bundle_path1, $this->fieldNameInput, $this->fieldLabel);

    // Create an additional node type.
    $type_name2 = strtolower($this->randomMachineName(8)) . '_test';
    $type2 = $this->drupalCreateContentType(['name' => $type_name2, 'type' => $type_name2]);
    $type_name2 = $type2->id();

    // Add a field to the second node type.
    $bundle_path2 = 'admin/structure/types/manage/' . $type_name2;
    $this->fieldUIAddExistingField($bundle_path2, $this->fieldName, $this->fieldLabel);

    // Delete the first field.
    $this->fieldUIDeleteField($bundle_path1, "node.$this->contentType.$this->fieldName", $this->fieldLabel, $this->contentType);

    // Check that the field was deleted.
    $this->assertNull(FieldConfig::loadByName('node', $this->contentType, $this->fieldName), 'Field was deleted.');
    // Check that the field storage was not deleted
    $this->assertNotNull(FieldStorageConfig::loadByName('node', $this->fieldName), 'Field storage was not deleted.');

    // Delete the second field.
    $this->fieldUIDeleteField($bundle_path2, "node.$type_name2.$this->fieldName", $this->fieldLabel, $type_name2);

    // Check that the field was deleted.
    $this->assertNull(FieldConfig::loadByName('node', $type_name2, $this->fieldName), 'Field was deleted.');
    // Check that the field storage was deleted too.
    $this->assertNull(FieldStorageConfig::loadByName('node', $this->fieldName), 'Field storage was deleted.');
  }

  /**
   * Tests that Field UI respects disallowed field names.
   */
  public function testDisallowedFieldNames() {
    // Reset the field prefix so we can test properly.
    $this->config('field_ui.settings')->set('field_prefix', '')->save();

    $label = 'Disallowed field';
    $edit = [
      'label' => $label,
      'new_storage_type' => 'test_field',
    ];

    // Try with an entity key.
    $edit['field_name'] = 'title';
    $bundle_path = 'admin/structure/types/manage/' . $this->contentType;
    $this->drupalPostForm("$bundle_path/fields/add-field", $edit, 'Save and continue');
    $this->assertText('The machine-readable name is already in use. It must be unique.');

    // Try with a base field.
    $edit['field_name'] = 'sticky';
    $bundle_path = 'admin/structure/types/manage/' . $this->contentType;
    $this->drupalPostForm("$bundle_path/fields/add-field", $edit, 'Save and continue');
    $this->assertText('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests that Field UI respects locked fields.
   */
  public function testLockedField() {
    // Create a locked field and attach it to a bundle. We need to do this
    // programmatically as there's no way to create a locked field through UI.
    $field_name = strtolower($this->randomMachineName(8));
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'test_field',
      'cardinality' => 1,
      'locked' => TRUE,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->contentType,
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name, [
        'type' => 'test_field_widget',
      ])
      ->save();

    // Check that the links for edit and delete are not present.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields');
    $locked = $this->xpath('//tr[@id=:field_name]/td[4]', [':field_name' => $field_name]);
    $this->assertSame('Locked', $locked[0]->getHtml(), 'Field is marked as Locked in the UI');
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/node.' . $this->contentType . '.' . $field_name . '/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that Field UI respects the 'no_ui' flag in the field type definition.
   */
  public function testHiddenFields() {
    // Check that the field type is not available in the 'add new field' row.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/add-field');
    $this->assertEmpty($this->xpath('//select[@id="edit-new-storage-type"]//option[@value="hidden_test_field"]'), "The 'add new field' select respects field types 'no_ui' property.");
    $this->assertNotEmpty($this->xpath('//select[@id="edit-new-storage-type"]//option[@value="shape"]'), "The 'add new field' select shows a valid option.");

    // Create a field storage and a field programmatically.
    $field_name = 'hidden_test_field';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_name,
    ])->save();
    $field = [
      'field_name' => $field_name,
      'bundle' => $this->contentType,
      'entity_type' => 'node',
      'label' => t('Hidden field'),
    ];
    FieldConfig::create($field)->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name)
      ->save();
    $this->assertInstanceOf(FieldConfig::class, FieldConfig::load('node.' . $this->contentType . '.' . $field_name));

    // Check that the newly added field appears on the 'Manage Fields'
    // screen.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields');
    $this->assertSession()->elementTextContains('xpath', '//table[@id="field-overview"]//tr[@id="hidden-test-field"]//td[1]', $field['label']);

    // Check that the field does not appear in the 're-use existing field' row
    // on other bundles.
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $this->assertEmpty($this->xpath('//select[@id="edit-existing-storage-name"]//option[@value=:field_name]', [':field_name' => $field_name]), "The 're-use existing field' select respects field types 'no_ui' property.");
    $this->assertNotEmpty($this->xpath('//select[@id="edit-existing-storage-name"]//option[@value=:field_name]', [':field_name' => 'field_tags']), "The 're-use existing field' select shows a valid option.");

    // Check that non-configurable fields are not available.
    $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    foreach ($field_types as $field_type => $definition) {
      if (empty($definition['no_ui'])) {
        $this->assertNotEmpty($this->xpath('//select[@id="edit-new-storage-type"]//option[@value=:field_type]', [':field_type' => $field_type]), new FormattableMarkup('Configurable field type @field_type is available.', ['@field_type' => $field_type]));
      }
      else {
        $this->assertEmpty($this->xpath('//select[@id="edit-new-storage-type"]//option[@value=:field_type]', [':field_type' => $field_type]), new FormattableMarkup('Non-configurable field type @field_type is not available.', ['@field_type' => $field_type]));
      }
    }
  }

  /**
   * Tests that a duplicate field name is caught by validation.
   */
  public function testDuplicateFieldName() {
    // field_tags already exists, so we're expecting an error when trying to
    // create a new field with the same name.
    $edit = [
      'field_name' => 'tags',
      'label' => $this->randomMachineName(),
      'new_storage_type' => 'entity_reference',
    ];
    $url = 'admin/structure/types/manage/' . $this->contentType . '/fields/add-field';
    $this->drupalPostForm($url, $edit, 'Save and continue');

    $this->assertText('The machine-readable name is already in use. It must be unique.');
    $this->assertSession()->addressEquals($url);
  }

  /**
   * Tests that external URLs in the 'destinations' query parameter are blocked.
   */
  public function testExternalDestinations() {
    $options = [
      'query' => ['destinations' => ['http://example.com']],
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.body/storage', [], 'Save field settings', $options);
    // The external redirect should not fire.
    $this->assertSession()->addressEquals('admin/structure/types/manage/article/fields/node.article.body/storage?destinations%5B0%5D=http%3A//example.com');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertRaw('Attempt to update field <em class="placeholder">Body</em> failed: <em class="placeholder">The internal path component &#039;http://example.com&#039; is external. You are not allowed to specify an external URL together with internal:/.</em>.');
  }

  /**
   * Tests that deletion removes field storages and fields as expected for a term.
   */
  public function testDeleteTaxonomyField() {
    // Create a new field.
    $bundle_path = 'admin/structure/taxonomy/manage/tags/overview';

    $this->fieldUIAddNewField($bundle_path, $this->fieldNameInput, $this->fieldLabel);

    // Delete the field.
    $this->fieldUIDeleteField($bundle_path, "taxonomy_term.tags.$this->fieldName", $this->fieldLabel, 'Tags');

    // Check that the field was deleted.
    $this->assertNull(FieldConfig::loadByName('taxonomy_term', 'tags', $this->fieldName), 'Field was deleted.');
    // Check that the field storage was deleted too.
    $this->assertNull(FieldStorageConfig::loadByName('taxonomy_term', $this->fieldName), 'Field storage was deleted.');
  }

  /**
   * Tests that help descriptions render valid HTML.
   */
  public function testHelpDescriptions() {
    // Create an image field
    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'image',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'label' => 'Image',
      'bundle' => 'article',
    ])->save();

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('field_image')
      ->save();

    $edit = [
      'description' => '<strong>Test with an upload field.',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_image', $edit, 'Save settings');

    // Check that hook_field_widget_form_alter() does believe this is the
    // default value form.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_tags');
    $this->assertText('From hook_field_widget_form_alter(): Default form is true.', 'Default value form in hook_field_widget_form_alter().');

    $edit = [
      'description' => '<em>Test with a non upload field.',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_tags', $edit, 'Save settings');

    $this->drupalGet('node/add/article');
    $this->assertRaw('<strong>Test with an upload field.</strong>');
    $this->assertRaw('<em>Test with a non upload field.</em>');
  }

  /**
   * Tests that the field list administration page operates correctly.
   */
  public function fieldListAdminPage() {
    $this->drupalGet('admin/reports/fields');
    $this->assertText($this->fieldName, 'Field name is displayed in field list.');
    $this->assertSession()->linkByHrefExists('admin/structure/types/manage/' . $this->contentType . '/fields');
  }

  /**
   * Tests the "preconfigured field" functionality.
   *
   * @see \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface
   */
  public function testPreconfiguredFields() {
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');

    // Check that the preconfigured field option exist alongside the regular
    // field type option.
    $this->assertSession()->optionExists('edit-new-storage-type', 'field_ui:test_field_with_preconfigured_options:custom_options');
    $this->assertSession()->optionExists('edit-new-storage-type', 'test_field_with_preconfigured_options');

    // Add a field with every possible preconfigured value.
    $this->fieldUIAddNewField(NULL, 'test_custom_options', 'Test label', 'field_ui:test_field_with_preconfigured_options:custom_options');
    $field_storage = FieldStorageConfig::loadByName('node', 'field_test_custom_options');
    $this->assertEqual($field_storage->getCardinality(), FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->assertEqual($field_storage->getSetting('test_field_storage_setting'), 'preconfigured_storage_setting');

    $field = FieldConfig::loadByName('node', 'article', 'field_test_custom_options');
    $this->assertTrue($field->isRequired());
    $this->assertEqual($field->getSetting('test_field_setting'), 'preconfigured_field_setting');

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $form_display = $display_repository->getFormDisplay('node', 'article');
    $this->assertEqual($form_display->getComponent('field_test_custom_options')['type'], 'test_field_widget_multiple');
    $view_display = $display_repository->getViewDisplay('node', 'article');
    $this->assertEqual($view_display->getComponent('field_test_custom_options')['type'], 'field_test_multiple');
    $this->assertEqual($view_display->getComponent('field_test_custom_options')['settings']['test_formatter_setting_multiple'], 'altered dummy test string');
  }

  /**
   * Tests the access to non-existent field URLs.
   */
  public function testNonExistentFieldUrls() {
    $field_id = 'node.foo.bar';

    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/' . $field_id);
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/' . $field_id . '/storage');
    $this->assertSession()->statusCodeEquals(404);
  }

}
