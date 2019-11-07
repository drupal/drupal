<?php

namespace Drupal\Tests\path_alias\Functional\Rest;

use Drupal\Core\Language\LanguageInterface;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * Base class for path_alias EntityResource tests.
 */
abstract class PathAliasResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['path_alias'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'path_alias';

  /**
   * @inheritdoc
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * {@inheritdoc}
   */
  protected static $firstCreatedEntityId = 3;

  /**
   * {@inheritdoc}
   */
  protected static $secondCreatedEntityId = 4;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer url aliases']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $path_alias = PathAlias::create([
      'path' => '/<front>',
      'alias' => '/frontpage1',
    ]);
    $path_alias->save();
    return $path_alias;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'id' => [
        [
          'value' => 1,
        ],
      ],
      'revision_id' => [
        [
          'value' => 1,
        ],
      ],
      'langcode' => [
        [
          'value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ],
      ],
      'path' => [
        [
          'value' => '/<front>',
        ],
      ],
      'alias' => [
        [
          'value' => '/frontpage1',
        ],
      ],
      'status' => [
        [
          'value' => TRUE,
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
      'path' => [
        [
          'value' => '/<front>',
        ],
      ],
      'alias' => [
        [
          'value' => '/frontpage1',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return ['user.permissions'];
  }

}
