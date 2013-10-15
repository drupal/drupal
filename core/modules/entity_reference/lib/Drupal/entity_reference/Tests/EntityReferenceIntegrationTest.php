<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceIntegrationTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests various Entity reference UI components.
 */
class EntityReferenceIntegrationTest extends WebTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test', 'entity_test', 'entity_reference');

  public static function getInfo() {
    return array(
      'name' => 'Entity reference components (widgets, formatters, etc.)',
      'description' => 'Tests for various Entity reference components.',
      'group' => 'Entity Reference',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test user.
    $web_user = $this->drupalCreateUser(array('administer entity_test content'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the autocomplete widget when targeting a config entity type.
   */
  public function testConfigAutocompleteWidget() {
    // Create an Entity reference field targeting a config entity type.
    entity_reference_create_instance($this->entityType, $this->bundle, $this->fieldName, 'Field test', 'config_test');

    // Add the field to the default form mode.
    entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName)->save();

    // Create a test config entity.
    $config_entity_id = $this->randomName();
    $config_entity_label = $this->randomString();
    $config_entity = entity_create('config_test', array('id' => $config_entity_id, 'label' => $config_entity_label));
    $config_entity->save();

    $entity_name = $this->randomName();
    $edit = array(
      'name' => $entity_name,
      'user_id' => mt_rand(0, 128),
      $this->fieldName . '[0][target_id]' => $config_entity_label . ' (' . $config_entity_id . ')',
    );
    $this->drupalPostForm($this->entityType . '/add', $edit, t('Save'));
    $entity = current(entity_load_multiple_by_properties($this->entityType, array('name' => $entity_name)));

    $this->assertTrue($entity, format_string('%entity_type: Entity found in the database.', array('%entity_type' => $this->entityType)));
    $this->assertEqual($entity->{$this->fieldName}->target_id, $config_entity_id);
    $this->assertEqual($entity->{$this->fieldName}->entity->id(), $config_entity_id);
    $this->assertEqual($entity->{$this->fieldName}->entity->label(), $config_entity_label);
  }

}
