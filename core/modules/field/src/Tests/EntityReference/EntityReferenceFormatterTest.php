<?php

/**
 * @file
 * Contains \Drupal\field\Tests\EntityReference\EntityReferenceFormatterTest.
 */

namespace Drupal\field\Tests\EntityReference;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_reference\Tests\EntityReferenceTestTrait;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\system\Tests\Entity\EntityUnitTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the formatters functionality.
 *
 * @group entity_reference
 */
class EntityReferenceFormatterTest extends EntityUnitTestBase {

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
  protected $fieldName = 'field_test';

  /**
   * The entity to be referenced in this test.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencedEntity;

  /**
   * The entity that is not yet saved to its persistent storage to be referenced
   * in this test.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $unsavedReferencedEntity;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('entity_reference');

  protected function setUp() {
    parent::setUp();

    // Grant the 'view test entity' permission.
    $this->installConfig(array('user'));
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('view test entity')
      ->save();

    // The label formatter rendering generates links, so build the router.
    $this->installSchema('system', 'router');
    $this->container->get('router.builder')->rebuild();

    $this->createEntityReferenceField($this->entityType, $this->bundle, $this->fieldName, 'Field test', $this->entityType, 'default', array(), FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Set up a field, so that the entity that'll be referenced bubbles up a
    // cache tag when rendering it entirely.
    entity_create('field_storage_config', array(
      'field_name' => 'body',
      'entity_type' => $this->entityType,
      'type' => 'text',
      'settings' => array(),
    ))->save();
    entity_create('field_config', array(
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'body',
      'label' => 'Body',
    ))->save();
    entity_get_display($this->entityType, $this->bundle, 'default')
      ->setComponent('body', array(
        'type' => 'text_default',
        'settings' => array(),
      ))
      ->save();

    entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    ))->save();

    // Create the entity to be referenced.
    $this->referencedEntity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $this->referencedEntity->body = array(
      'value' => '<p>Hello, world!</p>',
      'format' => 'full_html',
    );
    $this->referencedEntity->save();

    // Create another entity to be referenced but do not save it.
    $this->unsavedReferencedEntity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $this->unsavedReferencedEntity->body = array(
      'value' => '<p>Hello, unsaved world!</p>',
      'format' => 'full_html',
    );
  }

  /**
   * Assert inaccessible items don't change the data of the fields.
   */
  public function testAccess() {
    // Revoke the 'view test entity' permission for this test.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->revokePermission('view test entity')
      ->save();

    $field_name = $this->fieldName;

    $referencing_entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $referencing_entity->save();
    $referencing_entity->{$field_name}->entity = $this->referencedEntity;

    // Assert user doesn't have access to the entity.
    $this->assertFalse($this->referencedEntity->access('view'), 'Current user does not have access to view the referenced entity.');

    $formatter_manager = $this->container->get('plugin.manager.field.formatter');

    // Get all the existing formatters.
    foreach ($formatter_manager->getOptions('entity_reference') as $formatter => $name) {
      // Set formatter type for the 'full' view mode.
      entity_get_display($this->entityType, $this->bundle, 'default')
        ->setComponent($field_name, array(
          'type' => $formatter,
        ))
        ->save();

      // Invoke entity view.
      entity_view($referencing_entity, 'default');

      // Verify the un-accessible item still exists.
      $this->assertEqual($referencing_entity->{$field_name}->target_id, $this->referencedEntity->id(), format_string('The un-accessible item still exists after @name formatter was executed.', array('@name' => $name)));
    }
  }

  /**
   * Tests the ID formatter.
   */
  public function testIdFormatter() {
    $formatter = 'entity_reference_entity_id';
    $build = $this->buildRenderArray([$this->referencedEntity, $this->unsavedReferencedEntity], $formatter);

    $this->assertEqual($build[0]['#markup'], $this->referencedEntity->id(), sprintf('The markup returned by the %s formatter is correct for an item with a saved entity.', $formatter));
    $this->assertEqual($build[0]['#cache']['tags'], $this->referencedEntity->getCacheTags(), sprintf('The %s formatter has the expected cache tags.', $formatter));
    $this->assertTrue(!isset($build[1]), sprintf('The markup returned by the %s formatter is correct for an item with a unsaved entity.', $formatter));
  }

  /**
   * Tests the entity formatter.
   */
  public function testEntityFormatter() {
    $formatter = 'entity_reference_entity_view';
    $build = $this->buildRenderArray([$this->referencedEntity, $this->unsavedReferencedEntity], $formatter);

    // Test the first field item.
    $expected_rendered_name_field_1 = '<div class="field field-entity-test--name field-name-name field-type-string field-label-hidden">
    <div class="field-items">
          <div class="field-item">' . $this->referencedEntity->label() . '</div>
      </div>
</div>
';
    $expected_rendered_body_field_1 = '<div class="clearfix field field-entity-test--body field-name-body field-type-text field-label-above">
      <div class="field-label">Body</div>
    <div class="field-items">
          <div class="field-item"><p>Hello, world!</p></div>
      </div>
</div>
';
    drupal_render($build[0]);
    $this->assertEqual($build[0]['#markup'], 'default | ' . $this->referencedEntity->label() .  $expected_rendered_name_field_1 . $expected_rendered_body_field_1, sprintf('The markup returned by the %s formatter is correct for an item with a saved entity.', $formatter));
    $expected_cache_tags = Cache::mergeTags(
      \Drupal::entityManager()->getViewBuilder($this->entityType)->getCacheTags(),
      $this->referencedEntity->getCacheTags(),
      FilterFormat::load('full_html')->getCacheTags()
    );
    $this->assertEqual($build[0]['#cache']['tags'], $expected_cache_tags, format_string('The @formatter formatter has the expected cache tags.', array('@formatter' => $formatter)));

    // Test the second field item.
    drupal_render($build[1]);
    $this->assertEqual($build[1]['#markup'], $this->unsavedReferencedEntity->label(), sprintf('The markup returned by the %s formatter is correct for an item with a unsaved entity.', $formatter));
  }

  /**
   * Tests the label formatter.
   */
  public function testLabelFormatter() {
    $formatter = 'entity_reference_label';

    // The 'link' settings is TRUE by default.
    $build = $this->buildRenderArray([$this->referencedEntity, $this->unsavedReferencedEntity], $formatter);

    $expected_field_cacheability = [
      'contexts' => [],
      'tags' => [],
      'max-age' => Cache::PERMANENT,
    ];
    $this->assertEqual($build['#cache'], $expected_field_cacheability, 'The field render array contains the entity access cacheability metadata');
    $expected_item_1 = array(
      '#type' => 'link',
      '#title' => $this->referencedEntity->label(),
      '#url' => $this->referencedEntity->urlInfo(),
      '#options' => $this->referencedEntity->urlInfo()->getOptions(),
      '#cache' => array(
        'contexts' => [
          'user.permissions',
        ],
        'tags' => $this->referencedEntity->getCacheTags(),
      ),
      '#attached' => [],
      '#post_render_cache' => [],
    );
    $this->assertEqual(drupal_render($build[0]), drupal_render($expected_item_1), sprintf('The markup returned by the %s formatter is correct for an item with a saved entity.', $formatter));
    $this->assertEqual(BubbleableMetadata::createFromRenderArray($build[0]), BubbleableMetadata::createFromRenderArray($expected_item_1));

    // The second referenced entity is "autocreated", therefore not saved and
    // lacking any URL info.
    $expected_item_2 = array(
      '#markup' => $this->unsavedReferencedEntity->label(),
      '#cache' => array(
        'contexts' => [
          'user.permissions',
        ],
        'tags' => $this->unsavedReferencedEntity->getCacheTags(),
        'max-age' => Cache::PERMANENT,
      ),
      '#attached' => [],
      '#post_render_cache' => [],
    );
    $this->assertEqual($build[1], $expected_item_2, sprintf('The render array returned by the %s formatter is correct for an item with a unsaved entity.', $formatter));

    // Test with the 'link' setting set to FALSE.
    $build = $this->buildRenderArray([$this->referencedEntity, $this->unsavedReferencedEntity], $formatter, array('link' => FALSE));
    $this->assertEqual($build[0]['#markup'], $this->referencedEntity->label(), sprintf('The markup returned by the %s formatter is correct for an item with a saved entity.', $formatter));
    $this->assertEqual($build[1]['#markup'], $this->unsavedReferencedEntity->label(), sprintf('The markup returned by the %s formatter is correct for an item with a unsaved entity.', $formatter));

    // Test an entity type that doesn't have any link templates, which means
    // \Drupal\Core\Entity\EntityInterface::urlInfo() will throw an exception
    // and the label formatter will output only the label instead of a link.
    $field_storage_config = FieldStorageConfig::loadByName($this->entityType, $this->fieldName);
    $field_storage_config->setSetting('target_type', 'entity_test_label');
    $field_storage_config->save();

    $referenced_entity_with_no_link_template = entity_create('entity_test_label', array(
      'name' => $this->randomMachineName(),
    ));
    $referenced_entity_with_no_link_template->save();

    $build = $this->buildRenderArray([$referenced_entity_with_no_link_template], $formatter, array('link' => TRUE));
    $this->assertEqual($build[0]['#markup'], $referenced_entity_with_no_link_template->label(), sprintf('The markup returned by the %s formatter is correct for an entity type with no valid link template.', $formatter));
  }

  /**
   * Sets field values and returns a render array as built by
   * \Drupal\Core\Field\FieldItemListInterface::view().
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $referenced_entities
   *   An array of entity objects that will be referenced.
   * @param string $formatter
   *   The formatted plugin that will be used for building the render array.
   * @param array $formatter_options
   *   Settings specific to the formatter. Defaults to the formatter's default
   *   settings.
   *
   * @return array
   *   A render array.
   */
  protected function buildRenderArray(array $referenced_entities, $formatter, $formatter_options = array()) {
    // Create the entity that will have the entity reference field.
    $referencing_entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));

    $items = $referencing_entity->get($this->fieldName);

    // Assign the referenced entities.
    foreach ($referenced_entities as $referenced_entity) {
      $items[] = ['entity' => $referenced_entity];
    }

    // Build the renderable array for the field.
    return $items->view(array('type' => $formatter, 'settings' => $formatter_options));
  }

}
