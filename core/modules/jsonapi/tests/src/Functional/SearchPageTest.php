<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\search\Entity\SearchPage;

/**
 * JSON:API integration test for the "SearchPage" config entity type.
 *
 * @group jsonapi
 */
class SearchPageTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'search_page';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'search_page--search_page';

  /**
   * {@inheritdoc}
   *
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
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/search_page/search_page/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'search_page--search_page',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'configuration' => [
            'rankings' => [],
          ],
          'dependencies' => [
            'module' => [
              'node',
            ],
          ],
          'label' => 'Search of magnetic activity of the Sun',
          'langcode' => 'en',
          'path' => 'sun',
          'plugin' => 'node_search',
          'status' => TRUE,
          'weight' => 0,
          'drupal_internal__id' => 'hinode_search',
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
  protected function getExpectedUnauthorizedAccessCacheability() {
    // @see \Drupal\search\SearchPageAccessControlHandler::checkAccess()
    return parent::getExpectedUnauthorizedAccessCacheability()
      ->addCacheTags(['config:search.page.hinode_search']);
  }

}
