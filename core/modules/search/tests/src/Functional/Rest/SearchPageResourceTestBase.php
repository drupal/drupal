<?php

namespace Drupal\Tests\search\Functional\Rest;

use Drupal\search\Entity\SearchPage;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class SearchPageResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'search_page';

  /**
   * @var \Drupal\search\SearchPageInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access content']);
        break;

      case 'POST':
      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['administer search']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $search_page = SearchPage::create([
      'id' => 'hinode_search',
      'plugin' => 'node_search',
      'label' => 'Search of magnetic activity of the Sun',
      'path' => 'sun',
    ]);
    $search_page->save();
    return $search_page;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'configuration' => [
        'rankings' => [],
      ],
      'dependencies' => [
        'module' => ['node'],
      ],
      'id' => 'hinode_search',
      'label' => 'Search of magnetic activity of the Sun',
      'langcode' => 'en',
      'path' => 'sun',
      'plugin' => 'node_search',
      'status' => TRUE,
      'uuid' => $this->entity->uuid(),
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The 'access content' permission is required.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedEntityAccessCacheability($is_authenticated) {
    // @see \Drupal\search\SearchPageAccessControlHandler::checkAccess()
    return parent::getExpectedUnauthorizedEntityAccessCacheability($is_authenticated)
      ->addCacheTags(['config:search.page.hinode_search']);
  }

}
