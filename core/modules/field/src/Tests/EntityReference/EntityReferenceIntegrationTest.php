<?php

namespace Drupal\field\Tests\EntityReference;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\config\Tests\AssertConfigEntityImportTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests various Entity reference UI components.
 *
 * @group entity_reference
 */
class EntityReferenceIntegrationTest extends WebTestBase {

  use AssertConfigEntityImportTrait;
  use EntityReferenceTestTrait;

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
  protected $fieldName;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['config_test', 'entity_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test user.
    $web_user = $this->drupalCreateUser(array('administer entity_test content', 'administer entity_test fields', 'view test entity'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the entity reference field with all its supported field widgets.
   */
  public function testSupportedEntityTypesAndWidgets() {
    foreach ($this->getTestEntities() as $key => $referenced_entities) {
      $this->fieldName = 'field_test_' . $referenced_entities[0]->getEntityTypeId();

      // Create an Entity reference field.
      $this->createEntityReferenceField($this->entityType, $this->bundle, $this->fieldName, $this->fieldName, $referenced_entities[0]->getEntityTypeId(), 'default', array(), 2);

      // Test the default 'entity_reference_autocomplete' widget.
      entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName)->save();

      $entity_name = $this->randomMachineName();
      $edit = array(
        'name[0][value]' => $entity_name,
        $this->fieldName . '[0][target_id]' => $referenced_entities[0]->label() . ' (' . $referenced_entities[0]->id() . ')',
        // Test an input of the entity label without a ' (entity_id)' suffix.
        $this->fieldName . '[1][target_id]' => $referenced_entities[1]->label(),
      );
      $this->drupalPostForm($this->entityType . '/add', $edit, t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Try to post the form again with no modification and check if the field
      // values remain the same.
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->container->get('entity_type.manager')->getStorage($this->entityType);
      $entity = current($storage->loadByProperties(['name' => $entity_name]));
      $this->drupalGet($this->entityType . '/manage/' . $entity->id() . '/edit');
      $this->assertFieldByName($this->fieldName . '[0][target_id]', $referenced_entities[0]->label() . ' (' . $referenced_entities[0]->id() . ')');
      $this->assertFieldByName($this->fieldName . '[1][target_id]', $referenced_entities[1]->label() . ' (' . $referenced_entities[1]->id() . ')');

      $this->drupalPostForm(NULL, array(), t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Test the 'entity_reference_autocomplete_tags' widget.
      entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName, array(
        'type' => 'entity_reference_autocomplete_tags',
      ))->save();

      $entity_name = $this->randomMachineName();
      $target_id = $referenced_entities[0]->label() . ' (' . $referenced_entities[0]->id() . ')';
      // Test an input of the entity label without a ' (entity_id)' suffix.
      $target_id .= ', ' . $referenced_entities[1]->label();
      $edit = array(
        'name[0][value]' => $entity_name,
        $this->fieldName . '[target_id]' => $target_id,
      );
      $this->drupalPostForm($this->entityType . '/add', $edit, t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Try to post the form again with no modification and check if the field
      // values remain the same.
      $entity = current($storage->loadByProperties(['name' => $entity_name]));
      $this->drupalGet($this->entityType . '/manage/' . $entity->id() . '/edit');
      $this->assertFieldByName($this->fieldName . '[target_id]', $target_id . ' (' . $referenced_entities[1]->id() . ')');

      $this->drupalPostForm(NULL, array(), t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Test all the other widgets supported by the entity reference field.
      // Since we don't know the form structure for these widgets, just test
      // that editing and saving an already created entity works.
      $exclude = array('entity_reference_autocomplete', 'entity_reference_autocomplete_tags');
      $entity = current($storage->loadByProperties(['name' => $entity_name]));
      $supported_widgets = \Drupal::service('plugin.manager.field.widget')->getOptions('entity_reference');
      $supported_widget_types = array_diff(array_keys($supported_widgets), $exclude);

      foreach ($supported_widget_types as $widget_type) {
        entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName, array(
          'type' => $widget_type,
        ))->save();

        $this->drupalPostForm($this->entityType . '/manage/' . $entity->id() . '/edit', array(), t('Save'));
        $this->assertFieldValues($entity_name, $referenced_entities);
      }

      // Reset to the default 'entity_reference_autocomplete' widget.
      entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName)->save();

      // Set first entity as the default_value.
      $field_edit = array(
        'default_value_input[' . $this->fieldName . '][0][target_id]' => $referenced_entities[0]->label() . ' (' . $referenced_entities[0]->id() . ')',
      );
      if ($key == 'content') {
        $field_edit['settings[handler_settings][target_bundles][' . $referenced_entities[0]->getEntityTypeId() . ']'] = TRUE;
      }
      $this->drupalPostForm($this->entityType . '/structure/' . $this->bundle . '/fields/' . $this->entityType . '.' . $this->bundle . '.' . $this->fieldName, $field_edit, t('Save settings'));
      // Ensure the configuration has the expected dependency on the entity that
      // is being used a default value.
      $field = FieldConfig::loadByName($this->entityType, $this->bundle, $this->fieldName);
      $this->assertTrue(in_array($referenced_entities[0]->getConfigDependencyName(), $field->getDependencies()[$key]), SafeMarkup::format('Expected @type dependency @name found', ['@type' => $key, '@name' => $referenced_entities[0]->getConfigDependencyName()]));
      // Ensure that the field can be imported without change even after the
      // default value deleted.
      $referenced_entities[0]->delete();
      // Reload the field since deleting the default value can change the field.
      \Drupal::entityManager()->getStorage($field->getEntityTypeId())->resetCache([$field->id()]);
      $field = FieldConfig::loadByName($this->entityType, $this->bundle, $this->fieldName);
      $this->assertConfigEntityImport($field);

      // Once the default value has been removed after saving the dependency
      // should be removed.
      $field = FieldConfig::loadByName($this->entityType, $this->bundle, $this->fieldName);
      $field->save();
      $dependencies = $field->getDependencies();
      $this->assertFalse(isset($dependencies[$key]) && in_array($referenced_entities[0]->getConfigDependencyName(), $dependencies[$key]), SafeMarkup::format('@type dependency @name does not exist.', ['@type' => $key, '@name' => $referenced_entities[0]->getConfigDependencyName()]));
    }
  }

  /**
   * Asserts that the reference field values are correct.
   *
   * @param string $entity_name
   *   The name of the test entity.
   * @param \Drupal\Core\Entity\EntityInterface[] $referenced_entities
   *   An array of referenced entities.
   */
  protected function assertFieldValues($entity_name, $referenced_entities) {
    $entity = current($this->container->get('entity_type.manager')->getStorage(
    $this->entityType)->loadByProperties(['name' => $entity_name]));

    $this->assertTrue($entity, format_string('%entity_type: Entity found in the database.', array('%entity_type' => $this->entityType)));

    $this->assertEqual($entity->{$this->fieldName}->target_id, $referenced_entities[0]->id());
    $this->assertEqual($entity->{$this->fieldName}->entity->id(), $referenced_entities[0]->id());
    $this->assertEqual($entity->{$this->fieldName}->entity->label(), $referenced_entities[0]->label());

    $this->assertEqual($entity->{$this->fieldName}[1]->target_id, $referenced_entities[1]->id());
    $this->assertEqual($entity->{$this->fieldName}[1]->entity->id(), $referenced_entities[1]->id());
    $this->assertEqual($entity->{$this->fieldName}[1]->entity->label(), $referenced_entities[1]->label());
  }

  /**
   * Creates two content and two config test entities.
   *
   * @return array
   *   An array of entity objects.
   */
  protected function getTestEntities() {
    $config_entity_1 = entity_create('config_test', array('id' => $this->randomMachineName(), 'label' => $this->randomMachineName()));
    $config_entity_1->save();
    $config_entity_2 = entity_create('config_test', array('id' => $this->randomMachineName(), 'label' => $this->randomMachineName()));
    $config_entity_2->save();

    $content_entity_1 = EntityTest::create(array('name' => $this->randomMachineName()));
    $content_entity_1->save();
    $content_entity_2 = EntityTest::create(array('name' => $this->randomMachineName()));
    $content_entity_2->save();

    return array(
      'config' => array(
        $config_entity_1,
        $config_entity_2,
      ),
      'content' => array(
        $content_entity_1,
        $content_entity_2,
      ),
    );
  }

}
