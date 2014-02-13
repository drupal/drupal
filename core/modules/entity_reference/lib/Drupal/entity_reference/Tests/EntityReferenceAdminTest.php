<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceAdminTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Entity Reference Admin UI.
 */
class EntityReferenceAdminTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Entity Reference admin UI',
      'description' => 'Tests for the administrative UI.',
      'group' => 'Entity Reference',
    );
  }

  public static $modules = array('node', 'field_ui', 'entity_reference');

  public function setUp() {
    parent::setUp();

    // Create test user.
    $this->admin_user = $this->drupalCreateUser(array('access content', 'administer node fields'));
    $this->drupalLogin($this->admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->type;
  }

  protected function assertFieldSelectOptions($name, $expected_options) {
    $xpath = $this->buildXPathQuery('//select[@name=:name]', array(':name' => $name));
    $fields = $this->xpath($xpath);
    if ($fields) {
      $field = $fields[0];
      $options = $this->getAllOptionsList($field);
      return $this->assertIdentical($options, $expected_options);
    }
    else {
      return $this->fail(t('Unable to find field @name', array('@name' => $name)));
    }
  }

  /**
   * Extract all the options of a select element.
   */
  protected function getAllOptionsList($element) {
    $options = array();
    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = (string) $option['value'];
    }
    // TODO: support optgroup.
    return $options;
  }

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
    foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
      $this->assertFieldByXPath("//select[@name='field[settings][target_type]']/option[@value='" . $entity_type_id . "']");
    }

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
    // Set back to no sort.
    $this->drupalPostAjaxForm(NULL, array('instance[settings][handler_settings][sort][field]' => '_none'), 'instance[settings][handler_settings][sort][field]');

    // Third step: confirm.
    $this->drupalPostForm(NULL, array(
      'instance[settings][handler_settings][target_bundles][' . key($bundles) . ']' => key($bundles),
    ), t('Save settings'));

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-test"]/td[1]', 'Test label', 'Field was created and appears in the overview page.');
  }
}
