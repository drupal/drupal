<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\workspaces\Entity\Workspace;

/**
 * JSON:API integration test for the "Workspace" content entity type.
 *
 * @group jsonapi
 * @group #slow
 */
class WorkspaceTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'workspace';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'workspace--workspace';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeIsVersionable = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $uniqueFieldNames = ['id'];

  /**
   * {@inheritdoc}
   */
  protected static $firstCreatedEntityId = 'autumn_campaign';

  /**
   * {@inheritdoc}
   */
  protected static $secondCreatedEntityId = 'autumn_campaign';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['view any workspace']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['create workspace']);
        break;

      case 'PATCH':
        $this->grantPermissionsToTestedRole(['edit any workspace']);
        break;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['delete any workspace']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity(): EntityInterface {
    $entity = Workspace::create([
      'id' => 'campaign',
      'label' => 'Campaign',
      'uid' => $this->account->id(),
      'created' => 123456789,
    ]);
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $author = User::load($this->entity->getOwnerId());
    $base_url = Url::fromUri('base:/jsonapi/workspace/workspace/' . $this->entity->uuid())->setAbsolute();
    $self_url = clone $base_url;
    $version_identifier = 'id:' . $this->entity->getRevisionId();
    $self_url = $self_url->setOption('query', ['resourceVersion' => $version_identifier]);
    $version_query_string = '?resourceVersion=' . urlencode($version_identifier);
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
          'created' => '1973-11-29T21:33:09+00:00',
          'changed' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'label' => 'Campaign',
          'drupal_internal__id' => 'campaign',
          'drupal_internal__revision_id' => 2,
        ],
        'relationships' => [
          'parent' => [
            'data' => NULL,
            'links' => [
              'related' => [
                'href' => $base_url->toString() . '/parent' . $version_query_string,
              ],
              'self' => [
                'href' => $base_url->toString() . '/relationships/parent' . $version_query_string,
              ],
            ],
          ],
          'uid' => [
            'data' => [
              'id' => $author->uuid(),
              'meta' => [
                'drupal_internal__target_id' => (int) $author->id(),
              ],
              'type' => 'user--user',
            ],
            'links' => [
              'related' => [
                'href' => $base_url->toString() . '/uid' . $version_query_string,
              ],
              'self' => [
                'href' => $base_url->toString() . '/relationships/uid' . $version_query_string,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument(): array {
    return [
      'data' => [
        'type' => static::$resourceTypeName,
        'attributes' => [
          'drupal_internal__id' => 'autumn_campaign',
          'label' => 'Autumn campaign',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getModifiedEntityForPostTesting() {
    $modified = parent::getModifiedEntityForPostTesting();
    // Even though the field type of the workspace ID is 'string', it acts as a
    // machine name through a custom constraint, so we need to ensure that we
    // generate a proper random value for it.
    // @see \Drupal\workspaces\Entity\Workspace::baseFieldDefinitions()
    $modified['data']['attributes']['id'] = $this->randomMachineName();
    return $modified;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPatchDocument(): array {
    $patch_document = parent::getPatchDocument();
    unset($patch_document['data']['attributes']['drupal_internal__id']);
    return $patch_document;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability(): CacheableMetadata {
    // @see \Drupal\workspaces\WorkspaceAccessControlHandler::checkAccess()
    return parent::getExpectedUnauthorizedAccessCacheability()
      ->addCacheTags(['workspace:campaign'])
      // The "view|edit|delete own workspace" permissions add the 'user' cache
      // context.
      ->addCacheContexts(['user']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method): string {
    switch ($method) {
      case 'GET':
        return "The 'view own workspace' permission is required.";

      case 'POST':
        return "The following permissions are required: 'administer workspaces' OR 'create workspace'.";

      case 'PATCH':
        return "The 'edit own workspace' permission is required.";

      case 'DELETE':
        return "The 'delete own workspace' permission is required.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getSparseFieldSets(): array {
    // Workspace's resource type name ('workspace') comes after the 'uid' field,
    // which breaks nested sparse fieldset tests.
    return array_diff_key(parent::getSparseFieldSets(), array_flip([
      'nested_empty_fieldset',
      'nested_fieldset_with_owner_fieldset',
    ]));
  }

}
