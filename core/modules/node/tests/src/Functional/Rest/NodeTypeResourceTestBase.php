<?php

namespace Drupal\Tests\node\Functional\Rest;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * ResourceTestBase for NodeType entity.
 */
abstract class NodeTypeResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'node_type';

  /**
   * The NodeType entity.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer content types', 'access content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
      'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
    ]);

    $camelids->save();

    return $camelids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
      'display_submitted' => TRUE,
      'help' => NULL,
      'langcode' => 'en',
      'name' => 'Camelids',
      'new_revision' => TRUE,
      'preview_mode' => 1,
      'status' => TRUE,
      'type' => 'camelids',
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    return "The 'access content' permission is required.";
  }

}
