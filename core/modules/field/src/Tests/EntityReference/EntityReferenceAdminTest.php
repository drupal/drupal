<?php

/**
 * @file
 * Contains \Drupal\field\Tests\EntityReference\EntityReferenceAdminTest.
 */

namespace Drupal\field\Tests\EntityReference;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\node\Entity\Node;
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
   * Enable views_ui module to see the no_view_help text.
   *
   * @var array
   */
  public static $modules = ['node', 'field_ui', 'path', 'taxonomy', 'block', 'views_ui'];

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

    // Create a content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->id();

    // Create test user.
    $admin_user = $this->drupalCreateUser(array(
      'access content',
      'administer node fields',
      'administer node display',
      'administer views',
      'create ' . $type_name . ' content',
      'edit own ' . $type_name . ' content',
    ));
    $this->drupalLogin($admin_user);
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
    $this->assertFieldByName('settings[handler]', 'default:node');

    // The base handler settings should be displayed.
    $entity_type_id = 'node';
    $bundles = entity_get_bundles($entity_type_id);
    foreach ($bundles as $bundle_name => $bundle_info) {
      $this->assertFieldByName('settings[handler_settings][target_bundles][' . $bundle_name . ']');
    }

    reset($bundles);

    // Test the sort settings.
    // Option 0: no sort.
    $this->assertFieldByName('settings[handler_settings][sort][field]', '_none');
    $this->assertNoFieldByName('settings[handler_settings][sort][direction]');
    // Option 1: sort by field.
    $this->drupalPostAjaxForm(NULL, array('settings[handler_settings][sort][field]' => 'nid'), 'settings[handler_settings][sort][field]');
    $this->assertFieldByName('settings[handler_settings][sort][direction]', 'ASC');

    // Test that a non-translatable base field is a sort option.
    $this->assertFieldByXPath("//select[@name='settings[handler_settings][sort][field]']/option[@value='nid']");
    // Test that a translatable base field is a sort option.
    $this->assertFieldByXPath("//select[@name='settings[handler_settings][sort][field]']/option[@value='title']");
    // Test that a configurable field is a sort option.
    $this->assertFieldByXPath("//select[@name='settings[handler_settings][sort][field]']/option[@value='body.value']");

    // Set back to no sort.
    $this->drupalPostAjaxForm(NULL, array('settings[handler_settings][sort][field]' => '_none'), 'settings[handler_settings][sort][field]');
    $this->assertNoFieldByName('settings[handler_settings][sort][direction]');

    // Third step: confirm.
    $this->drupalPostForm(NULL, array(
      'required' => '1',
      'settings[handler_settings][target_bundles][' . key($bundles) . ']' => key($bundles),
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
    $this->assertFieldByName('settings[handler_settings][auto_create]');

    // Switch the target type to 'user' and check that the settings specific to
    // its selection handler are displayed.
    $field_name = 'node.' . $this->type . '.field_test';
    $edit = array(
      'settings[target_type]' => 'user',
    );
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $this->assertFieldByName('settings[handler_settings][filter][type]', '_none');

    // Switch the target type to 'node'.
    $field_name = 'node.' . $this->type . '.field_test';
    $edit = array(
      'settings[target_type]' => 'node',
    );
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));

    // Try to select the views handler.
    $edit = array(
      'settings[handler]' => 'views',
    );
    $this->drupalPostAjaxForm($bundle_path . '/fields/' . $field_name, $edit, 'settings[handler]');
    $this->assertRaw(t('No eligible views were found. <a href=":create">Create a view</a> with an <em>Entity Reference</em> display, or add such a display to an <a href=":existing">existing view</a>.', array(
      ':create' => \Drupal::url('views_ui.add'),
      ':existing' => \Drupal::url('entity.view.collection'),
    )));
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    // If no eligible view is available we should see a message.
    $this->assertText('The views entity selection mode requires a view.');

    // Enable the entity_reference_test module which creates an eligible view.
    $this->container->get('module_installer')->install(array('entity_reference_test'));
    $this->resetAll();
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $this->drupalPostAjaxForm($bundle_path . '/fields/' . $field_name, $edit, 'settings[handler]');
    $edit = array(
      'settings[handler_settings][view][view_and_display]' => 'test_entity_reference:entity_reference_1',
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertResponse(200);

    // Switch the target type to 'entity_test'.
    $edit = array(
      'settings[target_type]' => 'entity_test',
    );
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $edit = array(
      'settings[handler]' => 'views',
    );
    $this->drupalPostAjaxForm($bundle_path . '/fields/' . $field_name, $edit, 'settings[handler]');
    $edit = array(
      'required' => FALSE,
      'settings[handler_settings][view][view_and_display]' => 'test_entity_reference_entity_test:entity_reference_1',
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertResponse(200);

    // Create a new view and display it as a entity reference.
    $edit = array(
      'id' => 'node_test_view',
      'label' => 'Node Test View',
      'show[wizard_key]' => 'node',
      'page[create]' => 1,
      'page[title]' => 'Test Node View',
      'page[path]' => 'test/node/view',
      'page[style][style_plugin]' => 'default',
      'page[style][row_plugin]' => 'fields',
    );
    $this->drupalPostForm('admin/structure/views/add', $edit, t('Save and edit'));
    $this->drupalPostForm(NULL, array(), t('Duplicate as Entity Reference'));
    $this->clickLink(t('Settings'));
    $edit = array(
      'style_options[search_fields][title]' => 'title',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm('admin/structure/views/view/node_test_view/edit/entity_reference_1', array(), t('Save'));
    $this->clickLink(t('Settings'));

    // Create a test entity reference field.
    $field_name = 'test_entity_ref_field';
    $edit = array(
      'new_storage_type' => 'field_ui:entity_reference:node',
      'label' => 'Test Entity Reference Field',
      'field_name' => $field_name,
    );
    $this->drupalPostForm($bundle_path . '/fields/add-field', $edit, t('Save and continue'));

    // Set to unlimited.
    $edit = array(
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));

    // Add the view to the test field.
    $edit = array(
      'settings[handler]' => 'views',
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'settings[handler]');
    $edit = array(
      'required' => FALSE,
      'settings[handler_settings][view][view_and_display]' => 'node_test_view:entity_reference_1',
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));

    // Create nodes.
    $node1 = Node::create([
      'type' => $this->type,
      'title' => 'Foo Node',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => $this->type,
      'title' => 'Foo Node',
    ]);
    $node2->save();

    // Try to add a new node and fill the entity reference field.
    $this->drupalGet('node/add/' . $this->type);
    $result = $this->xpath('//input[@name="field_test_entity_ref_field[0][target_id]" and contains(@data-autocomplete-path, "/entity_reference_autocomplete/node/views/")]');
    $target_url = $this->getAbsoluteUrl($result[0]['data-autocomplete-path']);
    $this->drupalGet($target_url, array('query' => array('q' => 'Foo')));
    $this->assertRaw($node1->getTitle() . ' (' . $node1->id() . ')');
    $this->assertRaw($node2->getTitle() . ' (' . $node2->id() . ')');

    // Try to add a new node, fill the entity reference field and submit the
    // form.
    $this->drupalPostForm('node/add/' . $this->type, [], t('Add another item'));
    $edit = array(
      'title[0][value]' => 'Example',
      'field_test_entity_ref_field[0][target_id]' => 'Foo Node (' . $node1->id() . ')',
      'field_test_entity_ref_field[1][target_id]' => 'Foo Node (' . $node2->id() . ')',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    $edit = array(
      'title[0][value]' => 'Example',
      'field_test_entity_ref_field[0][target_id]' => 'Test'
    );
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));

    // Assert that entity reference autocomplete field is validated.
    $this->assertText(t('There are no entities matching "@entity"', ['@entity' => 'Test']));

    $edit = array(
      'title[0][value]' => 'Test',
      'field_test_entity_ref_field[0][target_id]' => $node1->getTitle()
    );
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));

    // Assert the results multiple times to avoid sorting problem of nodes with
    // the same title.
    $this->assertText(t('Multiple entities match this reference;'));
    $this->assertText(t("@node1", ['@node1' => $node1->getTitle() . ' (' . $node1->id() . ')']));
    $this->assertText(t("@node2", ['@node2' => $node2->getTitle() . ' (' . $node2->id() . ')']));

    $edit = array(
      'title[0][value]' => 'Test',
      'field_test_entity_ref_field[0][target_id]' => $node1->getTitle() . ' (' . $node1->id() . ')'
    );
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $this->assertLink($node1->getTitle());

    // Tests adding default values to autocomplete widgets.
    Vocabulary::create(array('vid' => 'tags', 'name' => 'tags'))->save();
    $taxonomy_term_field_name = $this->createEntityReferenceField('taxonomy_term', 'tags');
    $field_path = 'node.' . $this->type . '.field_' . $taxonomy_term_field_name;
    $this->drupalGet($bundle_path . '/fields/' . $field_path . '/storage');
    $edit = [
      'cardinality' => -1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_path);
    $term_name = $this->randomString();
    $edit = [
      // This must be set before new entities will be auto-created.
      'settings[handler_settings][auto_create]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_path);
    $edit = [
      // A term that doesn't yet exist.
      'default_value_input[field_' . $taxonomy_term_field_name . '][0][target_id]' => $term_name,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    // The term should now exist.
    $term = taxonomy_term_load_multiple_by_name($term_name, 'tags')[1];
    $this->assertIdentical(1, count($term), 'Taxonomy term was auto created when set as field default.');
  }

  /**
   * Tests the formatters for the Entity References.
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
      $field_edit['settings[handler_settings][target_bundles][' . $bundle . ']'] = TRUE;
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
