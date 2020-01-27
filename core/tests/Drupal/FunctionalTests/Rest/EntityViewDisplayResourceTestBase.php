<?php

namespace Drupal\FunctionalTests\Rest;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class EntityViewDisplayResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_view_display';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer node display']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ]);
    $camelids->save();

    // Create a view display.
    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'camelids',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $view_display->save();

    return $view_display;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'bundle' => 'camelids',
      'content' => [
        'links' => [
          'region' => 'content',
          'weight' => 100,
          'settings' => [],
          'third_party_settings' => [],
        ],
      ],
      'dependencies' => [
        'config' => [
          'node.type.camelids',
        ],
        'module' => [
          'user',
        ],
      ],
      'hidden' => [],
      'id' => 'node.camelids.default',
      'langcode' => 'en',
      'mode' => 'default',
      'status' => TRUE,
      'targetEntityType' => 'node',
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
  protected function getExpectedCacheContexts() {
    return [
      'user.permissions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer node display' permission is required.";
  }

}
