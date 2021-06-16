<?php

namespace Drupal\Tests\taxonomy\Functional\Rest;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class VocabularyResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'taxonomy_vocabulary';

  /**
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer taxonomy']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $vocabulary = Vocabulary::create([
      'name' => 'Llama',
      'vid' => 'llama',
    ]);
    $vocabulary->save();

    return $vocabulary;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'uuid' => $this->entity->uuid(),
      'vid' => 'llama',
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'name' => 'Llama',
      'description' => NULL,
      'weight' => 0,
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
    if ($method === 'GET') {
      return "The following permissions are required: 'access taxonomy overview' OR 'administer taxonomy'.";
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

}
