<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional\Rest;

use Drupal\file\Entity\File;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Before;

abstract class FileResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'user'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'file';

  /**
   * @var \Drupal\file\FileInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'uri' => NULL,
    'filemime' => NULL,
    'filesize' => NULL,
    'status' => NULL,
    'changed' => NULL,
  ];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $author;

  /**
   * Marks some tests as skipped because XML cannot be deserialized.
   */
  #[Before]
  public function fileResourceTestBaseSkipTests(): void {
    if ($this->name() === 'testPost') {
      // Drupal does not allow creating file entities independently. It allows
      // you to create file entities that are referenced from another entity
      // (e.g. an image for a node's image field).
      // For that purpose, there is the "file_upload" REST resource plugin.
      // @see \Drupal\file\FileAccessControlHandler::checkCreateAccess()
      // @see \Drupal\file\Plugin\rest\resource\FileUploadResource
      $this->markTestSkipped('Drupal does not allow creating file entities independently.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access content']);
        break;

      case 'PATCH':
        // \Drupal\file\FileAccessControlHandler::checkAccess() grants 'update'
        // access only to the user that owns the file. So there is no permission
        // to grant: instead, the file owner must be changed from its default
        // (user 1) to the current user.
        $this->makeCurrentUserFileOwner();
        return;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['delete any file']);
        break;
    }
  }

  /**
   * Makes the current user the file owner.
   */
  protected function makeCurrentUserFileOwner() {
    $account = static::$auth ? User::load(2) : User::load(0);
    $this->entity->setOwnerId($account->id());
    $this->entity->setOwner($account);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $this->author = User::load(1);

    $file = File::create();
    $file->setOwnerId($this->author->id());
    $file->setFilename('drupal.txt');
    $file->setMimeType('text/plain');
    $file->setFileUri('public://drupal.txt');
    $file->setPermanent();
    $file->save();

    file_put_contents($file->getFileUri(), 'Drupal');

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'changed' => [
        [
          'value' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'created' => [
        [
          'value' => (new \DateTime())->setTimestamp((int) $this->entity->getCreatedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'fid' => [
        [
          'value' => 1,
        ],
      ],
      'filemime' => [
        [
          'value' => 'text/plain',
        ],
      ],
      'filename' => [
        [
          'value' => 'drupal.txt',
        ],
      ],
      'filesize' => [
        [
          'value' => (int) $this->entity->getSize(),
        ],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'status' => [
        [
          'value' => TRUE,
        ],
      ],
      'uid' => [
        [
          'target_id' => (int) $this->author->id(),
          'target_type' => 'user',
          'target_uuid' => $this->author->uuid(),
          'url' => base_path() . 'user/' . $this->author->id(),
        ],
      ],
      'uri' => [
        [
          'url' => base_path() . $this->siteDirectory . '/files/drupal.txt',
          'value' => 'public://drupal.txt',
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
      'uid' => [
        [
          'target_id' => $this->author->id(),
        ],
      ],
      'filename' => [
        [
          'value' => 'drupal.txt',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPatchEntity() {
    return array_diff_key($this->getNormalizedPostEntity(), ['uid' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return [
      'url.site',
      'user.permissions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return match($method) {
      'GET' => "The 'access content' permission is required.",
      'PATCH' => "Only the file owner can update the file entity.",
      'DELETE' => "The 'delete any file' permission is required.",
      default =>  parent::getExpectedUnauthorizedAccessMessage($method),
    };
  }

}
