<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Traits;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Sets up the entity types and relationships for entity reference tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait FilterEntityReferenceTrait {

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }

  /**
   * The host content type to add the entity reference field to.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected NodeTypeInterface $hostBundle;

  /**
   * The content type to be referenced by the host content type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected NodeTypeInterface $targetBundle;

  /**
   * Entities to be used as reference targets.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $targetEntities;

  /**
   * Host entities which contain the reference fields to the target entities.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $hostEntities;

  /**
   * Sets up the entity types and relationships.
   */
  protected function setUpEntityTypes(): void {
    // Create an entity type, and a referenceable type. Since these are coded
    // into the test view, they are not randomly named.
    $this->hostBundle = $this->drupalCreateContentType(['type' => 'page']);
    $this->targetBundle = $this->drupalCreateContentType(['type' => 'article']);

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
      'bundle' => $this->hostBundle->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            $this->targetBundle->id() => $this->targetBundle->label(),
          ],
        ],
      ],
    ]);
    $field->save();

    // Create 10 nodes for use as target entities.
    for ($i = 0; $i < 10; $i++) {
      $node = $this->drupalCreateNode([
        'type' => $this->targetBundle->id(),
        'title' => ucfirst($this->targetBundle->id()) . ' ' . $i,
      ]);
      $this->targetEntities[$node->id()] = $node;
    }

    // Create 1 host entity to reference target entities from.
    $node = $this->drupalCreateNode([
      'type' => $this->hostBundle->id(),
      'title' => ucfirst($this->hostBundle->id()) . ' 0',
    ]);
    $this->hostEntities = [
      $node->id() => $node,
    ];

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_config',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'node_type',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_config',
      'bundle' => $this->hostBundle->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'sort' => ['field' => '_none'],
        ],
      ],
    ]);
    $field->save();
  }

}
