<?php

/**
 * @file
 * Contains \Drupal\block\Entity\Block.
 */

namespace Drupal\block\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\block\BlockPluginBag;
use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\EntityWithPluginBagInterface;

/**
 * Defines a Block configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "block",
 *   label = @Translation("Block"),
 *   controllers = {
 *     "access" = "Drupal\block\BlockAccessController",
 *     "view_builder" = "Drupal\block\BlockViewBuilder",
 *     "list" = "Drupal\block\BlockListController",
 *     "form" = {
 *       "default" = "Drupal\block\BlockFormController",
 *       "delete" = "Drupal\block\Form\BlockDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer blocks",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "block.admin_block_delete",
 *     "edit-form" = "block.admin_edit"
 *   }
 * )
 */
class Block extends ConfigEntityBase implements BlockInterface, EntityWithPluginBagInterface {

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
  protected $pluginConfigKey = 'settings';

  /**
   * The visibility settings.
   *
   * @var array
   */
  protected $visibility;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginBag()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginBag() {
    if (!$this->pluginBag) {
      $this->pluginBag = new BlockPluginBag(\Drupal::service('plugin.manager.block'), $this->plugin, $this->get('settings'), $this->id());
    }
    return $this->pluginBag;
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
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::getExportProperties();
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $names = array(
      'theme',
      'region',
      'weight',
      'plugin',
      'settings',
      'visibility',
    );
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * Sorts active blocks by weight; sorts inactive blocks by name.
   */
  public static function sort($a, $b) {
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

}
