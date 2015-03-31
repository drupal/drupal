<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceAdminTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests for the administrative UI.
 *
 * @group entity_reference
 */
class EntityReferenceAdminTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to install.
   *
   * Enable path module to ensure that the selection handler does not fail for
   * entities with a path field.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'entity_reference', 'path', 'taxonomy', 'block', 'views');


  /**
   * The name of the content type created for testing purposes.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create test user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer node fields', 'administer node display'));
    $this->drupalLogin($admin_user);

    // Create a content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->id();
  }

  /**
   * Tests the Entity Reference Admin UI.
   */
  public function testFieldAdminHandler() {
    $bundle_path = 'admin/structure/types/manage/' . $this->type;

    // First step: 'Add new field' on the 'Manage fields' page.
    $this->drupalGet($bundle_path . '/fields/add-field');

    // Check if the commonly referenced entity types appear in the list.
    $this->assertOption('edit-new-storage-type', 'field_ui:entity_reference:node');
    $this->assertOption('edit-new-storage-type', 'field_ui:entity_reference:user');

    $this->drupalPostForm(NULL, array(
      'label' => 'Test label',
      'field_name' => 'test',
      'new_storage_type' => 'entity_reference',
    ), t('Save and continue'));

    // Node should be selected by default.
    $this->assertFieldByName('settings[target_type]', 'node');

    // Check that all entity types can be referenced.
    $this->assertFieldSelectOptions('settings[target_type]', array_keys(\Drupal::entityManager()->getDefinitions()));

    // Second step: 'Field settings' form.
    $this->drupalPostForm(NULL, array(), t('Save field settings'));

    // The base handler should be selected by default.
    $this->assertFieldByName('field[settings][handler]', 'default:node');

    // The base handler settings should be displayed.
    $entity_type_id = 'node';
    $bundles = entity_get_bundles($entity_type_id);
    foreach ($bundles as $bundle_name => $bundle_info) {
      $this->assertFieldByName('field[settings][handler_settings][target_bundles][' . $bundle_name . ']');
    }

    reset($bundles);

    // Test the sort settings.
    // Option 0: no sort.
    $this->assertFieldByName('field[settings][handler_settings][sort][field]', '_none');
    $this->assertNoFieldByName('field[settings][handler_settings][sort][direction]');
    // Option 1: sort by field.
    $this->drupalPostAjaxForm(NULL, array('field[settings][handler_settings][sort][field]' => 'nid'), 'field[settings][handler_settings][sort][field]');
    $this->assertFieldByName('field[settings][handler_settings][sort][direction]', 'ASC');

    // Test that a non-translatable base field is a sort option.
    $this->assertFieldByXPath("//select[@name='field[settings][handler_settings][sort][field]']/option[@value='nid']");
    // Test that a translatable base field is a sort option.
    $this->assertFieldByXPath("//select[@name='field[settings][handler_settings][sort][field]']/option[@value='title']");
    // Test that a configurable field is a sort option.
    $this->assertFieldByXPath("//select[@name='field[settings][handler_settings][sort][field]']/option[@value='body.value']");

    // Set back to no sort.
    $this->drupalPostAjaxForm(NULL, array('field[settings][handler_settings][sort][field]' => '_none'), 'field[settings][handler_settings][sort][field]');
    $this->assertNoFieldByName('field[settings][handler_settings][sort][direction]');

    // Third step: confirm.
    $this->drupalPostForm(NULL, array(
      'field[required]' => '1',
      'field[settings][handler_settings][target_bundles][' . key($bundles) . ']' => key($bundles),
    ), t('Save settings'));

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-test"]/td[1]', 'Test label', 'Field was created and appears in the overview page.');

    // Check that the field settings form can be submitted again, even when the
    // field is required.
    // The first 'Edit' link is for the Body field.
    $this->clickLink(t('Edit'), 1);
    $this->drupalPostForm(NULL, array(), t('Save settings'));

    // Switch the target type to 'taxonomy_term' and check that the settings
    // specific to its selection handler are displayed.
    $field_name = 'node.' . $this->type . '.field_test';
    $edit = array(
      'settings[target_type]' => 'taxonomy_term',
    );
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $this->assertFieldByName('field[settings][handler_settings][auto_create]');

    // Switch the target type to 'user' and check that the settings specific to
    // its selection handler are displayed.
    $field_name = 'node.' . $this->type . '.field_test';
    $edit = array(
      'settings[target_type]' => 'user',
    );
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $this->assertFieldByName('field[settings][handler_settings][filter][type]', '_none');

    // Try to select the views handler.
    $edit = array(
      'field[settings][handler]' => 'views',
    );
    $this->drupalPostAjaxForm($bundle_path . '/fields/' . $field_name, $edit, 'field[settings][handler]');
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertResponse(200);
  }


  /**
   * Tests the formatters for the Entity References
   */
  public function testAvailableFormatters() {
    // Create a new vocabulary.
    Vocabulary::create(array('vid' => 'tags', 'name' => 'tags'))->save();

    // Create entity reference field with taxonomy term as a target.
    $taxonomy_term_field_name = $this->createEntityReferenceField('taxonomy_term', 'tags');

    // Create entity reference field with user as a target.
    $user_field_name = $this->createEntityReferenceField('user');

    // Create entity reference field with node as a target.
    $node_field_name = $this->createEntityReferenceField('node', $this->type);

    // Create entity reference field with date format as a target.
    $date_format_field_name = $this->createEntityReferenceField('date_format');

    // Display all newly created Entity Reference configuration.
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/display');

    // Check for Taxonomy Term select box values.
    // Test if Taxonomy Term Entity Reference Field has the correct formatters.
    $this->assertFieldSelectOptions('fields[field_' . $taxonomy_term_field_name . '][type]', array(
      'entity_reference_label',
      'entity_reference_entity_id',
      'entity_reference_rss_category',
      'entity_reference_entity_view',
      'hidden',
    ));

    // Test if User Reference Field has the correct formatters.
    // Author should be available for this field.
    // RSS Category should not be available for this field.
    $this->assertFieldSelectOptions('fields[field_' . $user_field_name . '][type]', array(
      'author',
      'entity_reference_entity_id',
      'entity_reference_entity_view',
      'entity_reference_label',
      'hidden',
    ));

    // Test if Node Entity Reference Field has the correct formatters.
    // RSS Category should not be available for this field.
    $this->assertFieldSelectOptions('fields[field_' . $node_field_name . '][type]', array(
      'entity_reference_label',
      'entity_reference_entity_id',
      'entity_reference_entity_view',
      'hidden',
    ));

    // Test if Date Format Reference Field has the correct formatters.
    // RSS Category & Entity View should not be available for this field.
    // This could be any field without a ViewBuilder.
    $this->assertFieldSelectOptions('fields[field_' . $date_format_field_name . '][type]', array(
      'entity_reference_label',
      'entity_reference_entity_id',
      'hidden',
    ));
  }

  /**
   * Creates a new Entity Reference fields with a given target type.
   *
   * @param $target_type
   *   The name of the target type
   * @param $bundle
   *   Name of the bundle
   *   Default = NULL
   * @return string
   *   Returns the generated field name
   */
  public function createEntityReferenceField($target_type, $bundle = NULL) {
    // Generates a bundle path for the newly created content type.
    $bundle_path = 'admin/structure/types/manage/' . $this->type;

    // Generate a random field name, must be only lowercase characters.
    $field_name = strtolower($this->randomMachineName());

    $storage_edit = $field_edit = array();
    $storage_edit['settings[target_type]'] = $target_type;
    if ($bundle) {
      $field_edit['field[settings][handler_settings][target_bundles][' . $bundle . ']'] = TRUE;
    }

    $this->fieldUIAddNewField($bundle_path, $field_name, NULL, 'entity_reference', $storage_edit, $field_edit);

    // Returns the generated field name.
    return $field_name;
  }


  /**
   * Checks if a select element contains the specified options.
   *
   * @param string $name
   *   The field name.
   * @param array $expected_options
   *   An array of expected options.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertFieldSelectOptions($name, array $expected_options) {
    $xpath = $this->buildXPathQuery('//select[@name=:name]', array(':name' => $name));
    $fields = $this->xpath($xpath);
    if ($fields) {
      $field = $fields[0];
      $options = $this->getAllOptionsList($field);

      sort($options);
      sort($expected_options);

      return $this->assertIdentical($options, $expected_options);
    }
    else {
      return $this->fail('Unable to find field ' . $name);
    }
  }

  /**
   * Extracts all options from a select element.
   *
   * @param \SimpleXMLElement $element
   *   The select element field information.
   *
   * @return array
   *   An array of option values as strings.
   */
  protected function getAllOptionsList(\SimpleXMLElement $element) {
    $options = array();
    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = (string) $option['value'];
    }

    // Loops trough all the option groups
    foreach ($element->optgroup as $optgroup) {
      $options = array_merge($this->getAllOptionsList($optgroup), $options);
    }

    return $options;
  }

}
