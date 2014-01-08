<?php

/**
 * @file
 * Contains \Drupal\search\Entity\SearchPage.
 */

namespace Drupal\search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginBag;
use Drupal\search\SearchPageInterface;

/**
 * Defines a configured search page.
 *
 * @EntityType(
 *   id = "search_page",
 *   label = @Translation("Search page"),
 *   controllers = {
 *     "access" = "Drupal\search\SearchPageAccessController",
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "list" = "Drupal\search\SearchPageListController",
 *     "form" = {
 *       "add" = "Drupal\search\Form\SearchPageAddForm",
 *       "edit" = "Drupal\search\Form\SearchPageEditForm",
 *       "search" = "Drupal\search\Form\SearchPageForm",
 *       "delete" = "Drupal\search\Form\SearchPageDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer search",
 *   links = {
 *     "edit-form" = "search.edit"
 *   },
 *   config_prefix = "search.page",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   }
 * )
 */
class SearchPage extends ConfigEntityBase implements SearchPageInterface {

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
   * The UUID of the search page entity.
   *
   * @var string
   */
  public $uuid;

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
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->pluginBag = new SearchPluginBag($this->searchPluginManager(), array($this->plugin), $this->configuration, $this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->pluginBag->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id) {
    $this->plugin = $plugin_id;
    $this->pluginBag->addInstanceID($plugin_id);
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
  public function getExportProperties() {
    $properties = parent::getExportProperties();
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
  public function postCreate(EntityStorageControllerInterface $storage_controller) {
    parent::postCreate($storage_controller);

    // @todo Use self::applyDefaultValue() once https://drupal.org/node/2004756
    //   is in.
    if (!isset($this->weight)) {
      $this->weight = $this->isDefaultSearch() ? -10 : 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    $plugin = $this->getPlugin();
    // If this plugin has any configuration, ensure that it is set.
    if ($plugin instanceof ConfigurablePluginInterface) {
      $this->set('configuration', $plugin->getConfiguration());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    $this->state()->set('menu_rebuild_needed', TRUE);
    // @todo The above call should be sufficient, but it is not until
    //   https://drupal.org/node/2167323 is fixed.
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    $search_page_repository = \Drupal::service('search.search_page_repository');
    if (!$search_page_repository->isSearchActive()) {
      $search_page_repository->clearDefaultSearchPage();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function sort($a, $b) {
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
   * Wraps the state storage.
   *
   * @return \Drupal\Core\KeyValueStore\StateInterface
   *   An object for state storage.
   */
  protected function state() {
    return \Drupal::state();
  }

  /**
   * Wraps the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactory
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
