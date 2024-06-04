<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Rest;

use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;
use Drupal\Core\Entity\Entity\EntityFormMode;

abstract class EntityFormModeResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Remove 'field_ui' when https://www.drupal.org/node/2867266.
   */
  protected static $modules = ['user', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_form_mode';

  /**
   * @var \Drupal\Core\Entity\EntityFormModeInterface
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
      'description' => '',
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
    return [];
  }

}
