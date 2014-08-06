<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceAdminTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the administrative UI.
 *
 * @group entity_reference
 */
class EntityReferenceAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * Enable path module to ensure that the selection handler does not fail for
   * entities with a path field.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'entity_reference', 'path');

  public function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer node fields'));
    $this->drupalLogin($admin_user);

    // Create a content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->type;
  }

  /**
   * Tests the Entity Reference Admin UI.
   */
  public function testFieldAdminHandler() {
    $bundle_path = 'admin/structure/types/manage/' . $this->type;

    // First step: 'Add new field' on the 'Manage fields' page.
    $this->drupalPostForm($bundle_path . '/fields', array(
      'fields[_add_new_field][label]' => 'Test label',
      'fields[_add_new_field][field_name]' => 'test',
      'fields[_add_new_field][type]' => 'entity_reference',
    ), t('Save'));

    // Node should be selected by default.
    $this->assertFieldByName('field[settings][target_type]', 'node');

    // Check that all entity types can be referenced.
    $this->assertFieldSelectOptions('field[settings][target_type]', array_keys(\Drupal::entityManager()->getDefinitions()));

    // Second step: 'Instance settings' form.
    $this->drupalPostForm(NULL, array(), t('Save field settings'));

    // The base handler should be selected by default.
    $this->assertFieldByName('instance[settings][handler]', 'default');

    // The base handler settings should be displayed.
    $entity_type_id = 'node';
    $bundles = entity_get_bundles($entity_type_id);
    foreach ($bundles as $bundle_name => $bundle_info) {
      $this->assertFieldByName('instance[settings][handler_settings][target_bundles][' . $bundle_name . ']');
    }

    reset($bundles);

    // Test the sort settings.
    // Option 0: no sort.
    $this->assertFieldByName('instance[settings][handler_settings][sort][field]', '_none');
    $this->assertNoFieldByName('instance[settings][handler_settings][sort][direction]');
    // Option 1: sort by field.
    $this->drupalPostAjaxForm(NULL, array('instance[settings][handler_settings][sort][field]' => 'nid'), 'instance[settings][handler_settings][sort][field]');
    $this->assertFieldByName('instance[settings][handler_settings][sort][direction]', 'ASC');

    // Test that a non-translatable base field is a sort option.
    $this->assertFieldByXPath("//select[@name='instance[settings][handler_settings][sort][field]']/option[@value='nid']");
    // Test that a translatable base field is a sort option.
    $this->assertFieldByXPath("//select[@name='instance[settings][handler_settings][sort][field]']/option[@value='title']");
    // Test that a configurable field is a sort option.
    $this->assertFieldByXPath("//select[@name='instance[settings][handler_settings][sort][field]']/option[@value='body.value']");

    // Set back to no sort.
    $this->drupalPostAjaxForm(NULL, array('instance[settings][handler_settings][sort][field]' => '_none'), 'instance[settings][handler_settings][sort][field]');
    $this->assertNoFieldByName('instance[settings][handler_settings][sort][direction]');

    // Third step: confirm.
    $this->drupalPostForm(NULL, array(
      'instance[settings][handler_settings][target_bundles][' . key($bundles) . ']' => key($bundles),
    ), t('Save settings'));

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-test"]/td[1]', 'Test label', 'Field was created and appears in the overview page.');
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
      return $this->assertIdentical(sort($options), sort($expected_options));
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

    if (isset($element->optgroup)) {
      $options += $this->getAllOptionsList($element->optgroup);
    }

    return $options;
  }

}
