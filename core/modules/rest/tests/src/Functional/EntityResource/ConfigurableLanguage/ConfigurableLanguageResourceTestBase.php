<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ConfigurableLanguage;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

abstract class ConfigurableLanguageResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'configurable_language';

  /**
   * @var \Drupal\language\ConfigurableLanguageInterface
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
    $configurable_language = ConfigurableLanguage::create([
      'id' => 'll',
      'label' => 'Llama Language',
    ]);
    $configurable_language->save();

    return $configurable_language;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'direction' => 'ltr',
      'id' => 'll',
      'label' => 'Llama Language',
      'langcode' => 'en',
      'locked' => FALSE,
      'status' => TRUE,
      'uuid' => $this->entity->uuid(),
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return Cache::mergeContexts(parent::getExpectedCacheContexts(), ['languages:language_interface']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

}
