<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Rest;

use Drupal\Core\Theme\Entity\DesignToken;
use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;

/**
 * Resource test base for the design_token entity.
 */
abstract class DesignTokenResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'design_token';

  /**
   * @var \Drupal\Core\Theme\Entity\DesignTokenInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer design tokens']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity = DesignToken::create([
      'id' => 'my_token',
      'path' => 'my-token',
      'type' => 'fontWeight',
      'scopes' => [
        ':root' => 500,
      ],
    ]);
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'id' => 'my_token',
      'path' => 'my-token',
      'type' => 'fontWeight',
      'scopes' => [
        ':root' => 500,
      ],
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
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

}
