<?php

/**
 * @file
 * Contains \Drupal\block\Entity\Block.
 */

namespace Drupal\block\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\block\BlockPluginCollection;
use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a Block configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "block",
 *   label = @Translation("Block"),
 *   handlers = {
 *     "access" = "Drupal\block\BlockAccessControlHandler",
 *     "view_builder" = "Drupal\block\BlockViewBuilder",
 *     "list_builder" = "Drupal\block\BlockListBuilder",
 *     "form" = {
 *       "default" = "Drupal\block\BlockForm",
 *       "delete" = "Drupal\block\Form\BlockDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer blocks",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/block/manage/{block}/delete",
 *     "edit-form" = "/admin/structure/block/manage/{block}"
 *   },
 *   config_export = {
 *     "id",
 *     "theme",
 *     "region",
 *     "weight",
 *     "provider",
 *     "plugin",
 *     "settings",
 *     "visibility",
 *   },
 *   lookup_keys = {
 *     "theme"
 *   }
 * )
 */
class Block extends ConfigEntityBase implements BlockInterface, EntityWithPluginCollectionInterface {

  /**
   * The ID of the block.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * The region this block is placed in.
   *
   * @var string
   */
  protected $region = self::BLOCK_REGION_NONE;

  /**
   * The block weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The visibility settings for this block.
   *
   * @var array
   */
  protected $visibility = [];

  /**
   * The plugin collection that holds the block plugin for this entity.
   *
   * @var \Drupal\block\BlockPluginCollection
   */
  protected $pluginCollection;

  /**
   * The available contexts for this block and its visibility conditions.
   *
   * @var array
   */
  protected $contexts = [];

  /**
   * The visibility collection.
   *
   * @var \Drupal\Core\Condition\ConditionPluginCollection
   */
  protected $visibilityCollection;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * The theme that includes the block plugin for this entity.
   *
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * Encapsulates the creation of the block's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The block's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new BlockPluginCollection(\Drupal::service('plugin.manager.block'), $this->plugin, $this->get('settings'), $this->id());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'settings' => $this->getPluginCollection(),
      'visibility' => $this->getVisibilityConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->theme;
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
  public function label() {
    $settings = $this->get('settings');
    if ($settings['label']) {
      return $settings['label'];
    }
    else {
      $definition = $this->getPlugin()->getPluginDefinition();
      return $definition['admin_label'];
    }
  }

  /**
   * Sorts active blocks by weight; sorts inactive blocks by name.
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    // Separate enabled from disabled.
    $status = (int) $b->status() - (int) $a->status();
    if ($status !== 0) {
      return $status;
    }
    // Sort by weight, unless disabled.
    if ($a->getRegion() != static::BLOCK_REGION_NONE) {
      $weight = $a->getWeight() - $b->getWeight();
      if ($weight) {
        return $weight;
      }
    }
    // Sort by label.
    return strcmp($a->label(), $b->label());
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('theme', $this->theme);
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Entity::postSave() calls Entity::invalidateTagsOnSave(), which only
    // handles the regular cases. The Block entity has one special case: a
    // newly created block may *also* appear on any page in the current theme,
    // so we must invalidate the associated block's cache tag (which includes
    // the theme cache tag).
    if (!$update) {
      Cache::invalidateTags($this->getCacheTagsToInvalidate());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility() {
    return $this->getVisibilityConditions()->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setVisibilityConfig($instance_id, array $configuration) {
    $conditions = $this->getVisibilityConditions();
    if (!$conditions->has($instance_id)) {
      $configuration['id'] = $instance_id;
      $conditions->addInstanceId($instance_id, $configuration);
    }
    else {
      $conditions->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibilityConditions() {
    if (!isset($this->visibilityCollection)) {
      $this->visibilityCollection = new ConditionPluginCollection($this->conditionPluginManager(), $this->get('visibility'));
    }
    return $this->visibilityCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibilityCondition($instance_id) {
    return $this->getVisibilityConditions()->get($instance_id);
  }

  /**
   * Gets the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  protected function conditionPluginManager() {
    $this->conditionPluginManager;
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegion($region) {
    $this->region = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicateBlock($new_id = NULL, $new_theme = NULL) {
    $duplicate = parent::createDuplicate();
    if (!empty($new_id)) {
      $duplicate->id = $new_id;
    }
    if (!empty($new_theme)) {
      $duplicate->theme = $new_theme;
    }
    return $duplicate;
  }

}
