<?php

namespace Drupal\Tests\comment\Functional\Rest;

use Drupal\comment\Entity\CommentType;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * ResourceTestBase for CommentType entity.
 */
abstract class CommentTypeResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'comment_type';

  /**
   * The CommentType entity.
   *
   * @var \Drupal\comment\CommentTypeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer comment types']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" comment type.
    $camelids = CommentType::create([
      'id' => 'camelids',
      'label' => 'Camelids',
      'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
      'target_entity_type_id' => 'node',
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
      'id' => 'camelids',
      'label' => 'Camelids',
      'langcode' => 'en',
      'status' => TRUE,
      'target_entity_type_id' => 'node',
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

}
