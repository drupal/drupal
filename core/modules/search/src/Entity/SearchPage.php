<?php

namespace Drupal\search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginCollection;
use Drupal\search\SearchPageInterface;

/**
 * Defines a configured search page.
 *
 * @ConfigEntityType(
 *   id = "search_page",
 *   label = @Translation("Search page"),
 *   handlers = {
 *     "access" = "Drupal\search\SearchPageAccessControlHandler",
 *     "list_builder" = "Drupal\search\SearchPageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\search\Form\SearchPageAddForm",
 *       "edit" = "Drupal\search\Form\SearchPageEditForm",
 *       "search" = "Drupal\search\Form\SearchPageForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer search",
 *   links = {
 *     "edit-form" = "/admin/config/search/pages/manage/{search_page}",
 *     "delete-form" = "/admin/config/search/pages/manage/{search_page}/delete",
 *     "enable" = "/admin/config/search/pages/manage/{search_page}/enable",
 *     "disable" = "/admin/config/search/pages/manage/{search_page}/disable",
 *     "set-default" = "/admin/config/search/pages/manage/{search_page}/set-default",
 *     "collection" = "/admin/config/search/pages",
 *   },
 *   config_prefix = "page",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "path",
 *     "weight",
 *     "plugin",
 *     "configuration",
 *   }
 * )
 */
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
  protected $configuration = array();

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
    return array('configuration' => $this->getPluginCollection());
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
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    /** @var $a \Drupal\search\SearchPageInterface */
    /** @var $b \Drupal\search\SearchPageInterface */
    $a_status = (int) $a->status();
    $b_status = (int) $b->status();
    if ($a_status != $b_status) {
      return ($a_status > $b_status) ? -1 : 1;
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
