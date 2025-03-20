<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Rest;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;

/**
 * Resource test base for the entity_form_display entity.
 */
abstract class EntityFormDisplayResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_form_display';

  /**
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $entity;

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
  protected function getExpectedNormalizedEntity() {
    return [
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
      'id' => 'node.camelids.default',
      'langcode' => 'en',
      'mode' => 'default',
      'status' => NULL,
      'targetEntityType' => 'node',
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
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
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer node form display' permission is required.";
  }

}
