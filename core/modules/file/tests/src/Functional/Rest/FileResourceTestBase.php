<?php

namespace Drupal\Tests\file\Functional\Rest;

use Drupal\file\Entity\File;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;

abstract class FileResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'user'];

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
    'uri',
    'filemime',
    'filesize',
    'status',
    'changed',
  ];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $author;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access content']);
        break;

      case 'PATCH':
      case 'DELETE':
        // \Drupal\file\FileAccessControlHandler::checkAccess() grants 'update'
        // and 'delete' access only to the user that owns the file. So there is
        // no permission to grant: instead, the file owner must be changed from
        // its default (user 1) to the current user.
        $this->makeCurrentUserFileOwner();
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function grantPermissionsToTestedRole(array $permissions) {
    // testPatch() and testDelete() test the 'bc_entity_resource_permissions' BC
    // layer; also call makeCurrentUserFileOwner() then.
    if ($permissions === ['restful patch entity:file'] || $permissions === ['restful delete entity:file']) {
      $this->makeCurrentUserFileOwner();
    }
    parent::grantPermissionsToTestedRole($permissions);
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
    $file->set('status', FILE_STATUS_PERMANENT);
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
        $this->formatExpectedTimestampItemValues($this->entity->getChangedTime()),
      ],
      'created' => [
        $this->formatExpectedTimestampItemValues((int) $this->entity->getCreatedTime()),
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
      'user.permissions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testPost() {
    // @todo https://www.drupal.org/node/1927648
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    if ($method === 'GET') {
      return "The 'access content' permission is required.";
    }
    if ($method === 'PATCH') {
      return 'You are not authorized to update this file entity.';
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

}
