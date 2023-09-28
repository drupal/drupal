<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Url;

/**
 * JSON:API integration test for the "EntityFormMode" config entity type.
 *
 * @group jsonapi
 * @group #slow
 */
class EntityFormModeTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo: Remove 'field_ui' when https://www.drupal.org/node/2867266.
   */
  protected static $modules = ['user', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_form_mode';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'entity_form_mode--entity_form_mode';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Entity\EntityFormModeInterface
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
    $this->grantPermissionsToTestedRole(['administer display modes']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity_form_mode = EntityFormMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'description' => '',
      'targetEntityType' => 'user',
    ]);
    $entity_form_mode->save();
    return $entity_form_mode;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/entity_form_mode/entity_form_mode/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'entity_form_mode--entity_form_mode',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'cache' => TRUE,
          'dependencies' => [
            'module' => [
              'user',
            ],
          ],
          'description' => '',
          'label' => 'Test',
          'langcode' => 'en',
          'status' => TRUE,
          'targetEntityType' => 'user',
          'drupal_internal__id' => 'user.test',
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

}
