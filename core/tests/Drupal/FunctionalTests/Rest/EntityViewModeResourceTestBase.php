<?php

namespace Drupal\FunctionalTests\Rest;

use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\Core\Entity\Entity\EntityViewMode;

abstract class EntityViewModeResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo: Remove 'field_ui' when https://www.drupal.org/node/2867266.
   */
  public static $modules = ['user', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_view_mode';

  /**
   * @var \Drupal\Core\Entity\EntityViewModeInterface
   */
  protected $entity;

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
    $entity_view_mode = EntityViewMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'targetEntityType' => 'user',
    ]);
    $entity_view_mode->save();
    return $entity_view_mode;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'cache' => TRUE,
      'dependencies' => [
        'module' => [
          'user',
        ],
      ],
      'id' => 'user.test',
      'label' => 'Test',
      'langcode' => 'en',
      'status' => TRUE,
      'targetEntityType' => 'user',
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

}
