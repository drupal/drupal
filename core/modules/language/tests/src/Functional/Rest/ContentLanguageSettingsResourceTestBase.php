<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional\Rest;

use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;

abstract class ContentLanguageSettingsResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'language_content_settings';

  /**
   * @var \Drupal\language\ContentLanguageSettingsInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer languages']);
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

    $entity = ContentLanguageSettings::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'camelids',
    ]);
    $entity->setDefaultLangcode('site_default')
      ->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'default_langcode' => 'site_default',
      'dependencies' => [
        'config' => [
          'node.type.camelids',
        ],
      ],
      'id' => 'node.camelids',
      'langcode' => 'en',
      'language_alterable' => FALSE,
      'status' => TRUE,
      'target_bundle' => 'camelids',
      'target_entity_type_id' => 'node',
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
      'languages:language_interface',
      'user.permissions',
    ];
  }

}
