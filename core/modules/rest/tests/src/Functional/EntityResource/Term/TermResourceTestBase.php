<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Term;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class TermResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'path'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'taxonomy_term';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed',
  ];

  /**
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access content']);
        break;
      case 'POST':
      case 'PATCH':
      case 'DELETE':
        // Grant the 'create url aliases' permission to test the case when
        // the path field is accessible, see
        // \Drupal\Tests\rest\Functional\EntityResource\Node\NodeResourceTestBase
        // for a negative test.
        // @todo Update once https://www.drupal.org/node/2824408 lands.
        $this->grantPermissionsToTestedRole(['administer taxonomy', 'create url aliases']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $vocabulary = Vocabulary::load('camelids');
    if (!$vocabulary) {
      // Create a "Camelids" vocabulary.
      $vocabulary = Vocabulary::create([
        'name' => 'Camelids',
        'vid' => 'camelids',
      ]);
      $vocabulary->save();
    }

    // Create a "Llama" taxonomy term.
    $term = Term::create(['vid' => $vocabulary->id()])
      ->setName('Llama')
      ->setChangedTime(123456789)
      ->set('path', '/llama');
    $term->save();

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'tid' => [
        ['value' => 1],
      ],
      'uuid' => [
        ['value' => $this->entity->uuid()],
      ],
      'vid' => [
        [
          'target_id' => 'camelids',
          'target_type' => 'taxonomy_vocabulary',
          'target_uuid' => Vocabulary::load('camelids')->uuid(),
        ],
      ],
      'name' => [
        ['value' => 'Llama'],
      ],
      'description' => [
        [
          'value' => NULL,
          'format' => NULL,
        ],
      ],
      'parent' => [],
      'weight' => [
        ['value' => 0],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'changed' => [
        $this->formatExpectedTimestampItemValues($this->entity->getChangedTime()),
      ],
      'default_langcode' => [
        [
          'value' => TRUE,
        ],
      ],
      'path' => [
        [
          'alias' => '/llama',
          'pid' => 1,
          'langcode' => 'en',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'vid' => [
        [
          'target_id' => 'camelids',
        ],
      ],
      'name' => [
        [
          'value' => 'Dramallama',
        ],
      ],
      'description' => [
        [
          'value' => 'Dramallamas are the coolest camelids.',
          'format' => NULL,
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
        return "The 'access content' permission is required.";
      case 'POST':
        return "The 'administer taxonomy' permission is required.";
      case 'PATCH':
        return "The following permissions are required: 'edit terms in camelids' OR 'administer taxonomy'.";
      case 'DELETE':
        return "The following permissions are required: 'delete terms in camelids' OR 'administer taxonomy'.";
      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

}
