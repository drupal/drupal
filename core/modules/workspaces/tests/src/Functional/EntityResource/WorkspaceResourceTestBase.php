<?php

namespace Drupal\Tests\workspaces\Functional\EntityResource;

use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;
use Drupal\workspaces\Entity\Workspace;

/**
 * Base class for workspace EntityResource tests.
 */
abstract class WorkspaceResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workspaces'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'workspace';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $firstCreatedEntityId = 'running_on_faith';

  /**
   * {@inheritdoc}
   */
  protected static $secondCreatedEntityId = 'running_on_faith_2';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['view any workspace']);
        break;
      case 'POST':
        $this->grantPermissionsToTestedRole(['create workspace']);
        break;
      case 'PATCH':
        $this->grantPermissionsToTestedRole(['edit any workspace']);
        break;
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['delete any workspace']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $workspace = Workspace::create([
      'id' => 'layla',
      'label' => 'Layla',
    ]);
    $workspace->save();
    return $workspace;
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity() {
    $workspace = $this->entity->createDuplicate();
    $workspace->id = 'layla_dupe';
    $workspace->label = 'Layla_dupe';
    $workspace->save();
    return $workspace;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $author = User::load($this->entity->getOwnerId());
    return [
      'created' => [
        $this->formatExpectedTimestampItemValues((int) $this->entity->getCreatedTime()),
      ],
      'changed' => [
        $this->formatExpectedTimestampItemValues($this->entity->getChangedTime()),
      ],
      'id' => [
        [
          'value' => 'layla',
        ],
      ],
      'label' => [
        [
          'value' => 'Layla',
        ],
      ],
      'revision_id' => [
        [
          'value' => 2,
        ],
      ],
      'parent' => [],
      'uid' => [
        [
          'target_id' => (int) $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'uuid' => [
        [
          'value' => $this->entity->uuid(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'id' => [
        [
          'value' => static::$firstCreatedEntityId,
        ],
      ],
      'label' => [
        [
          'value' => 'Running on faith',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecondNormalizedPostEntity() {
    $normalized_post_entity = $this->getNormalizedPostEntity();
    $normalized_post_entity['id'][0]['value'] = static::$secondCreatedEntityId;

    return $normalized_post_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPatchEntity() {
    return [
      'label' => [
        [
          'value' => 'Running on faith',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    switch ($method) {
      case 'GET':
        return "The 'view any workspace' permission is required.";
      break;
      case 'POST':
        return "The 'create workspace' permission is required.";
      break;
      case 'PATCH':
        return "The 'edit any workspace' permission is required.";
      break;
      case 'DELETE':
        return "The 'delete any workspace' permission is required.";
      break;
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

}
