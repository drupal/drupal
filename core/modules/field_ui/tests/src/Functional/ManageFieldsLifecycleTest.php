<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the Field UI "Manage fields" screen.
 *
 * @group field_ui
 * @group #slow
 */
class ManageFieldsLifecycleTest extends ManageFieldsFunctionalTestBase {

  /**
   * Runs the field CRUD tests.
   *
   * In order to act on the same fields, and not create the fields over and over
   * again the following tests create, update and delete the same fields.
   */
  public function testCRUDFields(): void {
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
  protected function manageFieldsPage($type = '') {
    $type = empty($type) ? $this->contentType : $type;
    $this->drupalGet('admin/structure/types/manage/' . $type . '/fields');
    // Check all table columns.
    $table_headers = ['Label', 'Machine name', 'Field type', 'Operations'];
    foreach ($table_headers as $table_header) {
      // We check that the label appear in the table headings.
      $this->assertSession()->responseContains($table_header . '</th>');
    }

    // Test the "Create a new field" action link.
    $this->assertSession()->linkExists('Create a new field');

    // Assert entity operations for all fields.
    $number_of_links = 2;
    $number_of_links_found = 0;
    $operation_links = $this->xpath('//ul[@class = "dropbutton"]/li/a');
    $url = base_path() . "admin/structure/types/manage/$type/fields/node.$type.body";

    foreach ($operation_links as $link) {
      switch ($link->getAttribute('title')) {
        case 'Edit field settings.':
          $this->assertSame($url, $link->getAttribute('href'));
          $number_of_links_found++;
          break;

        case 'Delete field.':
          $this->assertSame("$url/delete", $link->getAttribute('href'));
          $number_of_links_found++;
          break;
      }
    }

    $this->assertEquals($number_of_links, $number_of_links_found);
  }

  /**
   * Tests adding a new field.
   *
   * @todo Assert properties can be set in the form and read back in
   * $field_storage and $fields.
   */
  protected function createField() {
    // Create a test field.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->contentType, $this->fieldNameInput, $this->fieldLabel);
  }

  /**
   * Tests editing an existing field.
   */
  protected function updateField() {
    $field_id = 'node.' . $this->contentType . '.' . $this->fieldName;
    // Go to the field edit page.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/' . $field_id);
    $this->assertSession()->assertEscaped($this->fieldLabel);

    // Populate the field settings with new settings.
    $string = 'updated dummy test string';
    $edit = [
      'settings[test_field_setting]' => $string,
      'field_storage[subform][settings][test_field_storage_setting]' => $string,
    ];
    $this->assertSession()->pageTextContains('Default value');
    $this->submitForm($edit, 'Save settings');

    // Assert the field settings are correct.
    $this->assertFieldSettings($this->contentType, $this->fieldName, $string);

    // Assert redirection back to the "manage fields" page.
    $this->assertSession()->addressEquals('admin/structure/types/manage/' . $this->contentType . '/fields');
  }

  /**
   * Tests adding an existing field in another content type.
   */
  protected function addExistingField() {
    // Check "Re-use existing field" appears.
    $this->drupalGet('admin/structure/types/manage/page/fields');
    $this->assertSession()->pageTextContains('Re-use an existing field');
    $this->clickLink('Re-use an existing field');
    // Check that fields of other entity types (here, the 'comment_body' field)
    // do not show up in the "Re-use existing field" list.
    $this->assertSession()->elementNotExists('css', '.js-reuse-table [data-field-id="comment_body"]');
    // Validate the FALSE assertion above by also testing a valid one.
    $this->assertSession()->elementExists('css', ".js-reuse-table [data-field-id='{$this->fieldName}']");
    $new_label = $this->fieldLabel . '_2';
    // Add a new field based on an existing field.
    $this->fieldUIAddExistingField("admin/structure/types/manage/page", $this->fieldName, $new_label);
  }

  /**
   * Tests the cardinality settings of a field.
   *
   * We do not test if the number can be submitted with anything else than a
   * numeric value. That is tested already in FormTest::testNumber().
   */
  protected function cardinalitySettings() {
    $field_edit_path = 'admin/structure/types/manage/article/fields/node.article.body';

    // Assert the cardinality other field cannot be empty when cardinality is
    // set to 'number'.
    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => '',
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $this->assertSession()->pageTextContains('Number of values is required.');

    // Submit a custom number.
    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => 6,
    ];
    $this->submitForm($edit, 'Update settings');
    $this->submitForm([], 'Save settings');
    $this->drupalGet($field_edit_path);
    $this->assertSession()->fieldValueEquals('field_storage[subform][cardinality]', 'number');
    $this->assertSession()->fieldValueEquals('field_storage[subform][cardinality_number]', 6);

    // Add two entries in the body.
    $edit = ['title[0][value]' => 'Cardinality', 'body[0][value]' => 'Body 1', 'body[1][value]' => 'Body 2'];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Assert that you can't set the cardinality to a lower number than the
    // highest delta of this field.
    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => 1,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $this->assertSession()->pageTextContains("There is 1 entity with 2 or more values in this field");

    // Create a second entity with three values.
    $edit = ['title[0][value]' => 'Cardinality 3', 'body[0][value]' => 'Body 1', 'body[1][value]' => 'Body 2', 'body[2][value]' => 'Body 3'];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Set to unlimited.
    $edit = [
      'field_storage[subform][cardinality]' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $this->submitForm([], 'Save settings');
    $this->drupalGet($field_edit_path);
    $this->assertSession()->fieldValueEquals('field_storage[subform][cardinality]', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->assertSession()->fieldValueEquals('field_storage[subform][cardinality_number]', 1);

    // Assert that you can't set the cardinality to a lower number then the
    // highest delta of this field but can set it to the same.
    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => 1,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $this->submitForm([], 'Save settings');
    $this->assertSession()->pageTextContains("There are 2 entities with 2 or more values in this field");

    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => 2,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $this->assertSession()->pageTextContains("There is 1 entity with 3 or more values in this field");

    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => 3,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');

    // Test the cardinality validation is not access sensitive.

    // Remove the cardinality limit 4 so we can add a node the user doesn't have access to.
    $edit = [
      'field_storage[subform][cardinality]' => (string) FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $node = $this->drupalCreateNode([
      'private' => TRUE,
      'uid' => 0,
      'type' => 'article',
    ]);
    $node->body->appendItem('body 1');
    $node->body->appendItem('body 2');
    $node->body->appendItem('body 3');
    $node->body->appendItem('body 4');
    $node->save();

    // Assert that you can't set the cardinality to a lower number then the
    // highest delta of this field (including inaccessible entities) but can
    // set it to the same.
    $this->drupalGet($field_edit_path);
    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => 2,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $this->assertSession()->pageTextContains("There are 2 entities with 3 or more values in this field");
    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => 3,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $this->assertSession()->pageTextContains("There is 1 entity with 4 or more values in this field");
    $edit = [
      'field_storage[subform][cardinality]' => 'number',
      'field_storage[subform][cardinality_number]' => 4,
    ];
    $this->drupalGet($field_edit_path);
    $this->submitForm($edit, 'Update settings');
    $this->submitForm([], 'Save settings');
  }

  /**
   * Tests deleting a field from the field edit form.
   */
  protected function deleteField() {
    // Delete the field.
    $field_id = 'node.' . $this->contentType . '.' . $this->fieldName;
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/' . $field_id);
    $this->clickLink('Delete');
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
      $this->clickLink('Delete');
      $this->submitForm([], 'Delete');
    }
    // Check "Re-use existing field" appears.
    $this->drupalGet('admin/structure/types/manage/page/fields');
    $this->assertSession()->pageTextContains('Re-use an existing field');

    // Ensure that we test with a label that contains HTML.
    $label = $this->randomString(4) . '<br/>' . $this->randomString(4);
    // Add a new field for the orphaned storage.
    $this->fieldUIAddExistingField("admin/structure/types/manage/page", $this->fieldName, $label);
  }

  /**
   * Asserts field settings are as expected.
   *
   * @param string $bundle
   *   The bundle name for the field.
   * @param string $field_name
   *   The field name for the field.
   * @param string $string
   *   The settings text.
   * @param string $entity_type
   *   The entity type for the field.
   *
   * @internal
   */
  protected function assertFieldSettings(string $bundle, string $field_name, string $string = 'dummy test string', string $entity_type = 'node'): void {
    // Assert field storage settings.
    $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
    $this->assertSame($string, $field_storage->getSetting('test_field_storage_setting'), 'Field storage settings were found.');

    // Assert field settings.
    $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
    $this->assertSame($string, $field->getSetting('test_field_setting'), 'Field settings were found.');
  }

  /**
   * Tests that the field list administration page operates correctly.
   */
  protected function fieldListAdminPage() {
    $this->drupalGet('admin/reports/fields');
    $this->assertSession()->pageTextContains($this->fieldName);
    $this->assertSession()->linkByHrefExists('admin/structure/types/manage/' . $this->contentType . '/fields');
  }

}
