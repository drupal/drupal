<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\NodeType;

/**
 * JSON:API integration test for "ContentLanguageSettings" config entity type.
 *
 * @group jsonapi
 */
class ContentLanguageSettingsTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'language_content_settings';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'language_content_settings--language_content_settings';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\language\ContentLanguageSettingsInterface
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
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/language_content_settings/language_content_settings/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'language_content_settings--language_content_settings',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'default_langcode' => 'site_default',
          'dependencies' => [
            'config' => [
              'node.type.camelids',
            ],
          ],
          'langcode' => 'en',
          'language_alterable' => FALSE,
          'status' => TRUE,
          'target_bundle' => 'camelids',
          'target_entity_type_id' => 'node',
          'drupal_internal__id' => 'node.camelids',
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
  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    return Cache::mergeContexts(parent::getExpectedCacheContexts(), ['languages:language_interface']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity($key) {
    NodeType::create([
      'name' => 'Llamaids',
      'type' => 'llamaids',
    ])->save();

    $entity = ContentLanguageSettings::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'llamaids',
    ]);
    $entity->setDefaultLangcode('site_default');
    $entity->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getExpectedCollectionCacheability(AccountInterface $account, array $collection, array $sparse_fieldset = NULL, $filtered = FALSE) {
    $cacheability = parent::getExpectedCollectionCacheability($account, $collection, $sparse_fieldset, $filtered);
    if (static::entityAccess(reset($collection), 'view', $account)->isAllowed()) {
      $cacheability->addCacheContexts(['languages:language_interface']);
    }
    return $cacheability;
  }

}
