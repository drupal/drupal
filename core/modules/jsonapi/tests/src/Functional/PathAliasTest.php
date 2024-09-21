<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\path_alias\Entity\PathAlias;
use Drupal\Core\Url;

/**
 * JSON:API integration test for the "PathAlias" content entity type.
 *
 * @group jsonapi
 * @group path
 */
class PathAliasTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['path'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'path_alias';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'path_alias--path_alias';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeIsVersionable = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $entity;

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
      'alias' => '/frontpage1',
      'path' => '/<front>',
      'langcode' => 'en',
    ]);
    $path_alias->save();
    return $path_alias;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $base_url = Url::fromUri('base:/jsonapi/path_alias/path_alias/' . $this->entity->uuid())->setAbsolute();
    $self_url = clone $base_url;
    $version_identifier = 'id:' . $this->entity->getRevisionId();
    $self_url = $self_url->setOption('query', ['resourceVersion' => $version_identifier]);
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
        'self' => ['href' => $base_url->toString()],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => static::$resourceTypeName,
        'links' => [
          'self' => ['href' => $self_url->toString()],
        ],
        'attributes' => [
          'alias' => '/frontpage1',
          'path' => '/<front>',
          'langcode' => 'en',
          'status' => TRUE,
          'drupal_internal__id' => 1,
          'drupal_internal__revision_id' => 1,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => static::$resourceTypeName,
        'attributes' => [
          'alias' => '/frontpage1',
          'path' => '/<front>',
          'langcode' => 'en',
        ],
      ],
    ];
  }

}
