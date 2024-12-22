<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\jsonapi\JsonApiSpec;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;

/**
 * JSON:API integration test for the "NodeType" config entity type.
 *
 * @group jsonapi
 */
class NodeTypeTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'node_type';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'node_type--node_type';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    $this->grantPermissionsToTestedRole(['administer content types', 'access content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
      'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
    ]);

    $camelids->save();

    return $camelids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $self_url = Url::fromUri('base:/jsonapi/node_type/node_type/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => JsonApiSpec::SUPPORTED_SPECIFICATION_PERMALINK],
          ],
        ],
        'version' => JsonApiSpec::SUPPORTED_SPECIFICATION_VERSION,
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'node_type--node_type',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [],
          'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
          'display_submitted' => TRUE,
          'help' => NULL,
          'langcode' => 'en',
          'name' => 'Camelids',
          'new_revision' => TRUE,
          'preview_mode' => 1,
          'status' => TRUE,
          'drupal_internal__type' => 'camelids',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument(): array {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method): string {
    return "The 'access content' permission is required.";
  }

}
