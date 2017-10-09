<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Media;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;

abstract class MediaResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['media'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'media';

  /**
   * @var \Drupal\media\MediaInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['view media']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['create media']);
        break;

      case 'PATCH':
        $this->grantPermissionsToTestedRole(['update any media']);
        break;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['delete any media']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    if (!MediaType::load('camelids')) {
      // Create a "Camelids" media type.
      $media_type = MediaType::create([
        'name' => 'Camelids',
        'id' => 'camelids',
        'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
        'source' => 'file',
      ]);
      $media_type->save();
      // Create the source field.
      $source_field = $media_type->getSource()->createSourceField($media_type);
      $source_field->getFieldStorageDefinition()->save();
      $source_field->save();
      $media_type
        ->set('source_configuration', [
          'source_field' => $source_field->getName(),
        ])
        ->save();
    }

    // Create a file to upload.
    $file = File::create([
      'uri' => 'public://llama.txt',
    ]);
    $file->setPermanent();
    $file->save();

    // Create a "Llama" media item.
    $media = Media::create([
      'bundle' => 'camelids',
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
    ]);
    $media
      ->setName('Llama')
      ->setPublished(TRUE)
      ->setCreatedTime(123456789)
      ->setOwnerId(static::$auth ? $this->account->id() : 0)
      ->setRevisionUserId(static::$auth ? $this->account->id() : 0)
      ->save();

    return $media;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $file = File::load(1);
    $thumbnail = File::load(2);
    $author = User::load($this->entity->getOwnerId());
    return [
      'mid' => [
        [
          'value' => 1,
        ],
      ],
      'uuid' => [
        [
          'value' => $this->entity->uuid(),
        ],
      ],
      'vid' => [
        [
          'value' => 1,
        ],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'bundle' => [
        [
          'target_id' => 'camelids',
          'target_type' => 'media_type',
          'target_uuid' => MediaType::load('camelids')->uuid(),
        ],
      ],
      'name' => [
        [
          'value' => 'Llama',
        ],
      ],
      'field_media_file' => [
        [
          'description' => NULL,
          'display' => NULL,
          'target_id' => (int) $file->id(),
          'target_type' => 'file',
          'target_uuid' => $file->uuid(),
          'url' => $file->url(),
        ],
      ],
      'thumbnail' => [
        [
          'alt' => 'Thumbnail',
          'width' => 180,
          'height' => 180,
          'target_id' => (int) $thumbnail->id(),
          'target_type' => 'file',
          'target_uuid' => $thumbnail->uuid(),
          'title' => 'Llama',
          'url' => $thumbnail->url(),
        ],
      ],
      'status' => [
        [
          'value' => TRUE,
        ],
      ],
      'created' => [
        $this->formatExpectedTimestampItemValues(123456789),
      ],
      'changed' => [
        $this->formatExpectedTimestampItemValues($this->entity->getChangedTime()),
      ],
      'revision_created' => [
        $this->formatExpectedTimestampItemValues((int) $this->entity->getRevisionCreationTime()),
      ],
      'default_langcode' => [
        [
          'value' => TRUE,
        ],
      ],
      'uid' => [
        [
          'target_id' => (int) $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'revision_user' => [
        [
          'target_id' => (int) $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'revision_log_message' => [],
      'revision_translation_affected' => [
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
      'bundle' => [
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

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    switch ($method) {
      case 'GET';
        return "The 'view media' permission is required and the media item must be published.";

      case 'PATCH':
        return 'You are not authorized to update this media entity of bundle camelids.';

      case 'DELETE':
        return 'You are not authorized to delete this media entity of bundle camelids.';

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testPost() {
    $this->markTestSkipped('POSTing File Media items is not supported until https://www.drupal.org/node/1927648 is solved.');
  }

}
