<?php

/**
 * @file
 * Contains \Drupal\search\Entity\SearchPage.
 */

namespace Drupal\search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Entity\EntityWithPluginBagsInterface;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginBag;
use Drupal\search\SearchPageInterface;

/**
 * Defines a configured search page.
 *
 * @ConfigEntityType(
 *   id = "search_page",
 *   label = @Translation("Search page"),
 *   controllers = {
 *     "access" = "Drupal\search\SearchPageAccessController",
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\search\SearchPageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\search\Form\SearchPageAddForm",
 *       "edit" = "Drupal\search\Form\SearchPageEditForm",
 *       "search" = "Drupal\search\Form\SearchPageForm",
 *       "delete" = "Drupal\search\Form\SearchPageDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer search",
 *   links = {
 *     "edit-form" = "search.edit",
 *     "delete-form" = "search.delete",
 *     "enable" = "search.enable",
 *     "disable" = "search.disable",
 *     "set-default" = "search.set_default"
 *   },
 *   config_prefix = "page",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "status" = "status"
 *   }
 * )
 */
class SearchPage extends ConfigEntityBase implements SearchPageInterface, EntityWithPluginBagsInterface {

  /**
   * The name (plugin ID) of the search page entity.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the search page entity.
   *
   * @var string
   */
  public $label;

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
   * The plugin bag that stores search plugins.
   *
   * @var \Drupal\search\Plugin\SearchPluginBag
   */
  protected $pluginBag;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginBag()->get($this->plugin);
  }

  /**
   * Encapsulates the creation of the search page's PluginBag.
   *
   * @return \Drupal\Component\Plugin\PluginBag
   *   The search page's plugin bag.
   */
  protected function getPluginBag() {
    if (!$this->pluginBag) {
      $this->pluginBag = new SearchPluginBag($this->searchPluginManager(), $this->plugin, $this->configuration, $this->id());
    }
    return $this->pluginBag;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginBags() {
    return array('configuration' => $this->getPluginBag());
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id) {
    $this->plugin = $plugin_id;
    $this->getPluginBag()->addInstanceID($plugin_id);
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
  public function toArray() {
    $properties = parent::toArray();
    $names = array(
      'path',
      'weight',
      'plugin',
      'configuration',
    );
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // @todo Use self::applyDefaultValue() once https://drupal.org/node/2004756
    //   is in.
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
