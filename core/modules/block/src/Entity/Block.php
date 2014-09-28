<?php

/**
 * @file
 * Contains \Drupal\block\Entity\Block.
 */

namespace Drupal\block\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\block\BlockPluginBag;
use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginBagsInterface;
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
 *     "delete-form" = "entity.block.delete_form",
 *     "edit-form" = "entity.block.edit_form"
 *   }
 * )
 */
class Block extends ConfigEntityBase implements BlockInterface, EntityWithPluginBagsInterface {

  /**
   * The ID of the block.
   *
   * @var string
   */
  public $id;

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
  public $weight;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin bag that holds the block plugin for this entity.
   *
   * @var \Drupal\block\BlockPluginBag
   */
  protected $pluginBag;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginBag()->get($this->plugin);
  }

  /**
   * Encapsulates the creation of the block's PluginBag.
   *
   * @return \Drupal\Component\Plugin\PluginBag
   *   The block's plugin bag.
   */
  protected function getPluginBag() {
    if (!$this->pluginBag) {
      $this->pluginBag = new BlockPluginBag(\Drupal::service('plugin.manager.block'), $this->plugin, $this->get('settings'), $this->id());
    }
    return $this->pluginBag;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginBags() {
    return array('settings' => $this->getPluginBag());
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::label();
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
    $status = $b->get('status') - $a->get('status');
    if ($status) {
      return $status;
    }
    // Sort by weight, unless disabled.
    if ($a->get('region') != static::BLOCK_REGION_NONE) {
      $weight = $a->get('weight') - $b->get('weight');
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
   *
   * Block configuration entities are a special case: one block entity stores
   * the placement of one block in one theme. Instead of using an entity type-
   * specific list cache tag like most entities, use the cache tag of the theme
   * this block is placed in instead.
   */
  public function getListCacheTags() {
    return array('theme:' . $this->theme);
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility() {
    return $this->getPlugin()->getVisibilityConditions()->getConfiguration();
  }

}
