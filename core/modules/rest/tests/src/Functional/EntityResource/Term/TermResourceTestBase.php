<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Term;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class TermResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy'];

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
        // @todo Update once https://www.drupal.org/node/2824408 lands.
        $this->grantPermissionsToTestedRole(['administer taxonomy']);
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
      ->setChangedTime(123456789);
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
        [
          'value' => '123456789',
        ],
      ],
      'default_langcode' => [
        [
          'value' => TRUE,
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
    ];
  }

}
