<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ModeratedNode;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Functional\Rest\NodeResourceTestBase;

/**
 * Extend the Node resource test base and apply moderation to the entity.
 */
abstract class ModeratedNodeResourceTestBase extends NodeResourceTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_moderation'];

  /**
   * The test editorial workflow.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    parent::setUpAuthorization($method);

    switch ($method) {
      case 'POST':
      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['use editorial transition publish', 'use editorial transition create_new_draft']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity = parent::createEntity();
    if (!$this->workflow) {
      $this->workflow = $this->createEditorialWorkflow();
    }
    $this->workflow->getTypePlugin()->addEntityTypeAndBundle($entity->getEntityTypeId(), $entity->bundle());
    $this->workflow->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return array_merge(parent::getExpectedNormalizedEntity(), [
      'moderation_state' => [
        [
          'value' => 'published',
        ],
      ],
      'vid' => [
        [
          'value' => (int) $this->entity->getRevisionId(),
        ],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags() {
    return Cache::mergeTags(parent::getExpectedCacheTags(), ['config:workflows.workflow.editorial']);
  }

}
