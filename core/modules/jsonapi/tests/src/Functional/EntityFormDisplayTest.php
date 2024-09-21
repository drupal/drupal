<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;

/**
 * JSON:API integration test for the "EntityFormDisplay" config entity type.
 *
 * @group jsonapi
 */
class EntityFormDisplayTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_form_display';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'entity_form_display--entity_form_display';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer node form display']);
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

    // Create a form display.
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'camelids',
      'mode' => 'default',
    ]);
    $form_display->save();

    return $form_display;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/entity_form_display/entity_form_display/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'entity_form_display--entity_form_display',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'bundle' => 'camelids',
          'content' => [
            'created' => [
              'type' => 'datetime_timestamp',
              'weight' => 10,
              'region' => 'content',
              'settings' => [],
              'third_party_settings' => [],
            ],
            'promote' => [
              'type' => 'boolean_checkbox',
              'settings' => [
                'display_label' => TRUE,
              ],
              'weight' => 15,
              'region' => 'content',
              'third_party_settings' => [],
            ],
            'status' => [
              'type' => 'boolean_checkbox',
              'weight' => 120,
              'region' => 'content',
              'settings' => [
                'display_label' => TRUE,
              ],
              'third_party_settings' => [],
            ],
            'sticky' => [
              'type' => 'boolean_checkbox',
              'settings' => [
                'display_label' => TRUE,
              ],
              'weight' => 16,
              'region' => 'content',
              'third_party_settings' => [],
            ],
            'title' => [
              'type' => 'string_textfield',
              'weight' => -5,
              'region' => 'content',
              'settings' => [
                'size' => 60,
                'placeholder' => '',
              ],
              'third_party_settings' => [],
            ],
            'uid' => [
              'type' => 'entity_reference_autocomplete',
              'weight' => 5,
              'settings' => [
                'match_operator' => 'CONTAINS',
                'match_limit' => 10,
                'size' => 60,
                'placeholder' => '',
              ],
              'region' => 'content',
              'third_party_settings' => [],
            ],
          ],
          'dependencies' => [
            'config' => [
              'node.type.camelids',
            ],
          ],
          'hidden' => [],
          'langcode' => 'en',
          'mode' => 'default',
          'status' => NULL,
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method): string {
    return "The 'administer node form display' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity($key) {
    NodeType::create([
      'name' => 'Llamaids',
      'type' => 'llamaids',
    ])->save();

    $entity = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'llamaids',
      'mode' => 'default',
    ]);
    $entity->save();

    return $entity;
  }

}
