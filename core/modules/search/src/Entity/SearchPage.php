<?php

namespace Drupal\search\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\search\Form\SearchPageAddForm;
use Drupal\search\Form\SearchPageEditForm;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginCollection;
use Drupal\search\SearchPageAccessControlHandler;
use Drupal\search\SearchPageInterface;
use Drupal\search\SearchPageListBuilder;

/**
 * Defines a configured search page.
 */
#[ConfigEntityType(
  id: 'search_page',
  label: new TranslatableMarkup('Search page'),
  label_collection: new TranslatableMarkup('Search pages'),
  label_singular: new TranslatableMarkup('search page'),
  label_plural: new TranslatableMarkup('search pages'),
  config_prefix: 'page',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'weight' => 'weight',
    'status' => 'status',
  ],
  handlers: [
    'access' => SearchPageAccessControlHandler::class,
    'list_builder' => SearchPageListBuilder::class,
    'form' => [
      'add' => SearchPageAddForm::class,
      'edit' => SearchPageEditForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'edit-form' => '/admin/config/search/pages/manage/{search_page}',
    'delete-form' => '/admin/config/search/pages/manage/{search_page}/delete',
    'enable' => '/admin/config/search/pages/manage/{search_page}/enable',
    'disable' => '/admin/config/search/pages/manage/{search_page}/disable',
    'set-default' => '/admin/config/search/pages/manage/{search_page}/set-default',
    'collection' => '/admin/config/search/pages',
  ],
  admin_permission: 'administer search',
  label_count: [
    'singular' => '@count search page',
    'plural' => '@count search pages',
  ],
  config_export: [
    'id',
    'label',
    'path',
    'weight',
    'plugin',
    'configuration',
  ],
)]
class SearchPage extends ConfigEntityBase implements SearchPageInterface, EntityWithPluginCollectionInterface {

  /**
   * The name (plugin ID) of the search page entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the search page entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The configuration of the search page entity.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The search plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The path this search page will appear upon.
   *
   * This value is appended to 'search/' when building the path.
   *
   * @var string
   */
  protected $path;

  /**
   * The weight of the search page.
   *
   * @var int
   */
  protected $weight;

  /**
   * The plugin collection that stores search plugins.
   *
   * @var \Drupal\search\Plugin\SearchPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * Encapsulates the creation of the search page's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The search page's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new SearchPluginCollection($this->searchPluginManager(), $this->plugin, $this->configuration, $this->id());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['configuration' => $this->getPluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id) {
    $this->plugin = $plugin_id;
    $this->getPluginCollection()->addInstanceID($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function isIndexable() {
    return $this->status() && $this->getPlugin() instanceof SearchIndexingInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultSearch() {
    return $this->searchPageRepository()->getDefaultSearchPage() == $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // @todo Use self::applyDefaultValue() once
    //   https://www.drupal.org/node/2004756 is in.
    if (!isset($this->weight)) {
      $this->weight = $this->isDefaultSearch() ? -10 : 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    $this->routeBuilder()->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    $search_page_repository = \Drupal::service('search.search_page_repository');
    if (!$search_page_repository->isSearchActive()) {
      $search_page_repository->clearDefaultSearchPage();
    }
  }

  /**
   * Helper callback for uasort() to sort search page entities by status, weight and label.
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    /** @var \Drupal\search\SearchPageInterface $a */
    /** @var \Drupal\search\SearchPageInterface $b */
    $a_status = (int) $a->status();
    $b_status = (int) $b->status();
    if ($a_status != $b_status) {
      return $b_status <=> $a_status;
    }
    return parent::sort($a, $b);
  }

  /**
   * Wraps the route builder.
   *
   * @return \Drupal\Core\Routing\RouteBuilderInterface
   *   An object for state storage.
   */
  protected function routeBuilder() {
    return \Drupal::service('router.builder');
  }

  /**
   * Wraps the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   A config factory object.
   */
  protected function configFactory() {
    return \Drupal::service('config.factory');
  }

  /**
   * Wraps the search page repository.
   *
   * @return \Drupal\search\SearchPageRepositoryInterface
   *   A search page repository object.
   */
  protected function searchPageRepository() {
    return \Drupal::service('search.search_page_repository');
  }

  /**
   * Wraps the search plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   A search plugin manager object.
   */
  protected function searchPluginManager() {
    return \Drupal::service('plugin.manager.search');
  }

}
