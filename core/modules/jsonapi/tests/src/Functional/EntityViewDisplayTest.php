<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;

/**
 * JSON:API integration test for the "EntityViewDisplay" config entity type.
 *
 * @group jsonapi
 */
class EntityViewDisplayTest extends ResourceTestBase {

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
  protected static $entityTypeId = 'entity_view_display';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'entity_view_display--entity_view_display';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer node display']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ]);
    $camelids->save();

    // Create a view display.
    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'camelids',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $view_display->save();

    return $view_display;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/entity_view_display/entity_view_display/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'entity_view_display--entity_view_display',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'bundle' => 'camelids',
          'content' => [
            'links' => [
              'region' => 'content',
              'weight' => 100,
              'settings' => [],
              'third_party_settings' => [],
            ],
          ],
          'dependencies' => [
            'config' => [
              'node.type.camelids',
            ],
            'module' => [
              'user',
            ],
          ],
          'hidden' => [],
          'langcode' => 'en',
          'mode' => 'default',
          'status' => TRUE,
          'targetEntityType' => 'node',
          'drupal_internal__id' => 'node.camelids.default',
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
    return "The 'administer node display' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity($key) {
    NodeType::create([
      'name' => 'Pachyderms',
      'type' => 'pachyderms',
    ])->save();

    $entity = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'pachyderms',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $entity->save();

    return $entity;
  }

}
