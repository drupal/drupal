<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests the entity reference filter UI.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\filter\EntityReference
 */
class FilterEntityReferenceWebTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The host Entity type to add the entity reference field to.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $hostEntityType;

  /**
   * The Entity type to be referenced by the host Entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $targetEntityType;

  /**
   * Entities to be used as reference targets.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $targetEntities;

  /**
   * Host entities which contain the reference fields to the target entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $hostEntities;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Create an entity type, and a referenceable type. Since these are coded
    // into the test view, they are not randomly named.
    $this->hostEntityType = $this->drupalCreateContentType(['type' => 'page']);
    $this->targetEntityType = $this->drupalCreateContentType(['type' => 'article']);

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'node',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'bundle' => $this->hostEntityType->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            $this->targetEntityType->id() => $this->targetEntityType->label(),
          ],
        ],
      ],
    ]);
    $field->save();

    // Create 10 nodes for use as target entities.
    for ($i = 0; $i < 10; $i++) {
      $node = $this->drupalCreateNode(['type' => $this->targetEntityType->id()]);
      $this->targetEntities[$node->id()] = $node;
    }

    // Create 1 host entity to reference target entities from.
    $node = $this->drupalCreateNode(['type' => $this->hostEntityType->id()]);
    $this->hostEntities = [
      $node->id() => $node,
    ];
  }

  /**
   * Tests the filter UI.
   */
  public function testFilterUi(): void {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');

    $options = $this->getUiOptions();
    // Should be sorted by title ASC.
    uasort($this->targetEntities, function (EntityInterface $a, EntityInterface $b) {
      return strnatcasecmp($a->getTitle(), $b->getTitle());
    });
    $i = 0;
    foreach ($this->targetEntities as $id => $entity) {
      $this->assertEqual($options[$i]['label'], $entity->label(), new FormattableMarkup('Expected target entity label found for option :option', [':option' => $i]));
      $i++;
    }

    // Change the sort field and direction.
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_filter_entity_reference/default/filter/field_test_target_id');
    $edit = [
      'options[handler_settings][sort][field]' => 'nid',
      'options[handler_settings][sort][direction]' => 'DESC',
    ];
    $this->submitForm($edit, 'Apply');

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    // Items should now be in reverse id order.
    krsort($this->targetEntities);
    $options = $this->getUiOptions();
    $i = 0;
    foreach ($this->targetEntities as $id => $entity) {
      $this->assertEqual($options[$i]['label'], $entity->label(), new FormattableMarkup('Expected target entity label found for option :option', [':option' => $i]));
      $i++;
    }

    // Change bundle types.
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_filter_entity_reference/default/filter/field_test_target_id');
    $edit = [
      "options[handler_settings][target_bundles][{$this->hostEntityType->id()}]" => TRUE,
      "options[handler_settings][target_bundles][{$this->targetEntityType->id()}]" => TRUE,
    ];
    $this->submitForm($edit, 'Apply');

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    $options = $this->getUiOptions();
    $i = 0;
    foreach ($this->hostEntities + $this->targetEntities as $id => $entity) {
      $this->assertEqual($options[$i]['label'], $entity->label(), new FormattableMarkup('Expected target entity label found for option :option', [':option' => $i]));
      $i++;
    }
  }

  /**
   * Helper method to parse options from the UI.
   *
   * @return array
   *   Array of keyed arrays containing the id and label of each option.
   */
  protected function getUiOptions() {
    /** @var \Behat\Mink\Element\TraversableElement[] $result */
    $result = $this->xpath('//select[@name="options[value][]"]/option');
    $this->assertNotEmpty($result, 'Options found');

    $options = [];
    foreach ($result as $option) {
      $options[] = [
        'id' => (int) $option->getValue(),
        'label' => (string) $option->getText(),
      ];
    }

    return $options;
  }

}
