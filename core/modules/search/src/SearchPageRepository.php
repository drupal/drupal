<?php

namespace Drupal\search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Provides a repository for Search Page config entities.
 */
class SearchPageRepository implements SearchPageRepositoryInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The search page storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new SearchPageRepository.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
    $this->storage = $entity_manager->getStorage('search_page');
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveSearchPages() {
    $ids = $this->getQuery()
      ->condition('status', TRUE)
      ->execute();
    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchActive() {
    return (bool) $this->getQuery()
      ->condition('status', TRUE)
      ->range(0, 1)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexableSearchPages() {
    return array_filter($this->getActiveSearchPages(), function (SearchPageInterface $search) {
      return $search->isIndexable();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSearchPage() {
    // Find all active search pages (without loading them).
    $search_pages = $this->getQuery()
      ->condition('status', TRUE)
      ->execute();

    // If the default page is active, return it.
    $default = $this->configFactory->get('search.settings')->get('default_page');
    if (isset($search_pages[$default])) {
      return $default;
    }

    // Otherwise, use the first active search page.
    return is_array($search_pages) ? reset($search_pages) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function clearDefaultSearchPage() {
    $this->configFactory->getEditable('search.settings')->clear('default_page')->save();
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultSearchPage(SearchPageInterface $search_page) {
    $this->configFactory->getEditable('search.settings')->set('default_page', $search_page->id())->save();
    $search_page->enable()->save();
  }

  /**
   * {@inheritdoc}
   */
  public function sortSearchPages($search_pages) {
    $entity_type = $this->storage->getEntityType();
    uasort($search_pages, [$entity_type->getClass(), 'sort']);
    return $search_pages;
  }

  /**
   * Returns an entity query instance.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query instance.
   */
  protected function getQuery() {
    return $this->storage->getQuery();
  }

}
