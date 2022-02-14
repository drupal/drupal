<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * JSON:API integration test for the "vocabulary" config entity type.
 *
 * @group jsonapi
 */
class VocabularyTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'taxonomy_vocabulary';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'taxonomy_vocabulary--taxonomy_vocabulary';

  /**
   * {@inheritdoc}
   *
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
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/taxonomy_vocabulary/taxonomy_vocabulary/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'taxonomy_vocabulary--taxonomy_vocabulary',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'langcode' => 'en',
          'status' => TRUE,
          'dependencies' => [],
          'name' => 'Llama',
          'description' => NULL,
          'weight' => 0,
          'drupal_internal__vid' => 'llama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
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
