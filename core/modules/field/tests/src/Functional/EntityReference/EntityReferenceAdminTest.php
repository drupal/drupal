<?php

namespace Drupal\Tests\field\Functional\EntityReference;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests for the administrative UI.
 *
 * @group entity_reference
 */
class EntityReferenceAdminTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->type = $type->id();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer node fields',
      'administer node display',
      'administer views',
      'create ' . $type_name . ' content',
      'edit own ' . $type_name . ' content',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the Entity Reference Admin UI.
   */
  public function testFieldAdminHandler() {
    $bundle_path = 'admin/structure/types/manage/' . $this->type;
    // Create a new view and display it as a entity reference.
    $edit = [
      'id' => 'node_test_view',
      'label' => 'Node Test View',
      'show[wizard_key]' => 'node',
      'show[sort]' => 'none',
      'page[create]' => 1,
      'page[title]' => 'Test Node View',
      'page[path]' => 'test/node/view',
      'page[style][style_plugin]' => 'default',
      'page[style][row_plugin]' => 'fields',
    ];
    $this->drupalPostForm('admin/structure/views/add', $edit, t('Save and edit'));
    $this->drupalPostForm(NULL, [], t('Duplicate as Entity Reference'));
    $this->clickLink(t('Settings'));
    $edit = [
      'style_options[search_fields][title]' => 'title',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply'));

    // Set sort to NID ascending.
    $edit = [
      'name[node_field_data.nid]' => 1,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/node_test_view/entity_reference_1/sort', $edit, t('Add and configure sort criteria'));
    $this->drupalPostForm(NULL, NULL, t('Apply'));

    $this->drupalPostForm('admin/structure/views/view/node_test_view/edit/entity_reference_1', [], t('Save'));
    $this->clickLink(t('Settings'));

    // Create a test entity reference field.
    $field_name = 'test_entity_ref_field';
    $edit = [
      'new_storage_type' => 'field_ui:entity_reference:node',
      'label' => 'Test Entity Reference Field',
      'field_name' => $field_name,
    ];
    $this->drupalPostForm($bundle_path . '/fields/add-field', $edit, t('Save and continue'));

    // Set to unlimited.
    $edit = [
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));

    // Add the view to the test field.
    $edit = [
      'settings[handler]' => 'views',
    ];
    $this->drupalPostForm(NULL, $edit, t('Change handler'));
    $edit = [
      'required' => FALSE,
      'settings[handler_settings][view][view_and_display]' => 'node_test_view:entity_reference_1',
    ];
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
    $target_url = $this->getAbsoluteUrl($result[0]->getAttribute('data-autocomplete-path'));
    $this->drupalGet($target_url, ['query' => ['q' => 'Foo']]);
    $this->assertRaw($node1->getTitle() . ' (' . $node1->id() . ')');
    $this->assertRaw($node2->getTitle() . ' (' . $node2->id() . ')');

    // Try to add a new node, fill the entity reference field and submit the
    // form.
    $this->drupalPostForm('node/add/' . $this->type, [], t('Add another item'));
    $edit = [
      'title[0][value]' => 'Example',
      'field_test_entity_ref_field[0][target_id]' => 'Foo Node (' . $node1->id() . ')',
      'field_test_entity_ref_field[1][target_id]' => 'Foo Node (' . $node2->id() . ')',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    $edit = [
      'title[0][value]' => 'Example',
      'field_test_entity_ref_field[0][target_id]' => 'Test',
    ];
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));

    // Assert that entity reference autocomplete field is validated.
    $this->assertText(t('There are no entities matching "@entity"', ['@entity' => 'Test']));

    $edit = [
      'title[0][value]' => 'Test',
      'field_test_entity_ref_field[0][target_id]' => $node1->getTitle(),
    ];
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));

    // Assert the results multiple times to avoid sorting problem of nodes with
    // the same title.
    $this->assertText(t('Multiple entities match this reference;'));
    $this->assertText(t("@node1", ['@node1' => $node1->getTitle() . ' (' . $node1->id() . ')']));
    $this->assertText(t("@node2", ['@node2' => $node2->getTitle() . ' (' . $node2->id() . ')']));
    $this->assertText(t('Specify the one you want by appending the id in parentheses, like "@example".', ['@example' => $node2->getTitle() . ' (' . $node2->id() . ')']));

    $edit = [
      'title[0][value]' => 'Test',
      'field_test_entity_ref_field[0][target_id]' => $node1->getTitle() . ' (' . $node1->id() . ')',
    ];
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $this->assertLink($node1->getTitle());

    // Tests adding default values to autocomplete widgets.
    Vocabulary::create(['vid' => 'tags', 'name' => 'tags'])->save();
    $taxonomy_term_field_name = $this->createEntityReferenceField('taxonomy_term', ['tags']);
    $field_path = 'node.' . $this->type . '.field_' . $taxonomy_term_field_name;
    $this->drupalGet($bundle_path . '/fields/' . $field_path . '/storage');
    $edit = [
      'cardinality' => -1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_path);
    $term_name = $this->randomString();
    $result = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $term_name)
      ->condition('vid', 'tags')
      ->accessCheck(FALSE)
      ->execute();
    $this->assertIdentical(0, count($result), "No taxonomy terms exist with the name '$term_name'.");
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
    $result = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $term_name)
      ->condition('vid', 'tags')
      ->accessCheck(FALSE)
      ->execute();
    $this->assertIdentical(1, count($result), 'Taxonomy term was auto created when set as field default.');
  }

  /**
   * Tests the formatters for the Entity References.
   */
  public function testAvailableFormatters() {
    // Create a new vocabulary.
    Vocabulary::create(['vid' => 'tags', 'name' => 'tags'])->save();

    // Create entity reference field with taxonomy term as a target.
    $taxonomy_term_field_name = $this->createEntityReferenceField('taxonomy_term', ['tags']);

    // Create entity reference field with user as a target.
    $user_field_name = $this->createEntityReferenceField('user');

    // Create entity reference field with node as a target.
    $node_field_name = $this->createEntityReferenceField('node', [$this->type]);

    // Create entity reference field with date format as a target.
    $date_format_field_name = $this->createEntityReferenceField('date_format');

    // Display all newly created Entity Reference configuration.
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/display');

    // Check for Taxonomy Term select box values.
    // Test if Taxonomy Term Entity Reference Field has the correct formatters.
    $this->assertFieldSelectOptions('fields[field_' . $taxonomy_term_field_name . '][type]', [
      'entity_reference_label',
      'entity_reference_entity_id',
      'entity_reference_rss_category',
      'entity_reference_entity_view',
    ]);

    // Test if User Reference Field has the correct formatters.
    // Author should be available for this field.
    // RSS Category should not be available for this field.
    $this->assertFieldSelectOptions('fields[field_' . $user_field_name . '][type]', [
      'author',
      'entity_reference_entity_id',
      'entity_reference_entity_view',
      'entity_reference_label',
    ]);

    // Test if Node Entity Reference Field has the correct formatters.
    // RSS Category should not be available for this field.
    $this->assertFieldSelectOptions('fields[field_' . $node_field_name . '][type]', [
      'entity_reference_label',
      'entity_reference_entity_id',
      'entity_reference_entity_view',
    ]);

    // Test if Date Format Reference Field has the correct formatters.
    // RSS Category & Entity View should not be available for this field.
    // This could be any field without a ViewBuilder.
    $this->assertFieldSelectOptions('fields[field_' . $date_format_field_name . '][type]', [
      'entity_reference_label',
      'entity_reference_entity_id',
    ]);
  }

  /**
   * Tests field settings for an entity reference field when the field has
   * multiple target bundles and is set to auto-create the target entity.
   */
  public function testMultipleTargetBundles() {
    /** @var \Drupal\taxonomy\Entity\Vocabulary[] $vocabularies */
    $vocabularies = [];
    for ($i = 0; $i < 2; $i++) {
      $vid = mb_strtolower($this->randomMachineName());
      $vocabularies[$i] = Vocabulary::create([
        'name' => $this->randomString(),
        'vid' => $vid,
      ]);
      $vocabularies[$i]->save();
    }

    // Create a new field pointing to the first vocabulary.
    $field_name = $this->createEntityReferenceField('taxonomy_term', [$vocabularies[0]->id()]);
    $field_name = "field_$field_name";
    $field_id = 'node.' . $this->type . '.' . $field_name;
    $path = 'admin/structure/types/manage/' . $this->type . '/fields/' . $field_id;

    $this->drupalGet($path);

    // Expect that there's no 'auto_create_bundle' selected.
    $this->assertNoFieldByName('settings[handler_settings][auto_create_bundle]');

    $edit = [
      'settings[handler_settings][target_bundles][' . $vocabularies[1]->id() . ']' => TRUE,
    ];
    // Enable the second vocabulary as a target bundle.
    $this->drupalPostForm($path, $edit, 'Save settings');
    $this->drupalGet($path);
    // Expect a select element with the two vocabularies as options.
    $this->assertFieldByXPath("//select[@name='settings[handler_settings][auto_create_bundle]']/option[@value='" . $vocabularies[0]->id() . "']");
    $this->assertFieldByXPath("//select[@name='settings[handler_settings][auto_create_bundle]']/option[@value='" . $vocabularies[1]->id() . "']");

    $edit = [
      'settings[handler_settings][auto_create]' => TRUE,
      'settings[handler_settings][auto_create_bundle]' => $vocabularies[1]->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));

    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = FieldConfig::load($field_id);
    // Expect that the target bundle has been saved in the backend.
    $this->assertEqual($field_config->getSetting('handler_settings')['auto_create_bundle'], $vocabularies[1]->id());

    // Delete the other bundle. Field config should not be affected.
    $vocabularies[0]->delete();
    $field_config = FieldConfig::load($field_id);
    $this->assertTrue($field_config->getSetting('handler_settings')['auto_create']);
    $this->assertIdentical($field_config->getSetting('handler_settings')['auto_create_bundle'], $vocabularies[1]->id());

    // Delete the bundle set for entity auto-creation. Auto-created settings
    // should be reset (no auto-creation).
    $vocabularies[1]->delete();
    $field_config = FieldConfig::load($field_id);
    $this->assertSame(FALSE, $field_config->getSetting('handler_settings')['auto_create']);
    $this->assertFalse(isset($field_config->getSetting('handler_settings')['auto_create_bundle']));
  }

  /**
   * Creates a new Entity Reference fields with a given target type.
   *
   * @param string $target_type
   *   The name of the target type
   * @param string[] $bundles
   *   A list of bundle IDs. Defaults to [].
   *
   * @return string
   *   Returns the generated field name
   */
  protected function createEntityReferenceField($target_type, $bundles = []) {
    // Generates a bundle path for the newly created content type.
    $bundle_path = 'admin/structure/types/manage/' . $this->type;

    // Generate a random field name, must be only lowercase characters.
    $field_name = strtolower($this->randomMachineName());

    $storage_edit = $field_edit = [];
    $storage_edit['settings[target_type]'] = $target_type;
    foreach ($bundles as $bundle) {
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
   */
  protected function assertFieldSelectOptions($name, array $expected_options) {
    $xpath = $this->buildXPathQuery('//select[@name=:name]', [':name' => $name]);
    $fields = $this->xpath($xpath);
    if ($fields) {
      $field = $fields[0];
      $options = $field->findAll('xpath', 'option');
      array_walk($options, function (NodeElement &$option) {
        $option = $option->getValue();
      });
      sort($options);
      sort($expected_options);
      $this->assertIdentical($options, $expected_options);
    }
    else {
      $this->fail('Unable to find field ' . $name);
    }
  }

}
