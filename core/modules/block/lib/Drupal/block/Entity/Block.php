<?php

/**
 * @file
 * Contains \Drupal\block\Entity\Block.
 */

namespace Drupal\block\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\block\BlockPluginBag;
use Drupal\block\BlockInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a Block configuration entity class.
 *
 * @EntityType(
 *   id = "block",
 *   label = @Translation("Block"),
 *   module = "block",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "access" = "Drupal\block\BlockAccessController",
 *     "view_builder" = "Drupal\block\BlockViewBuilder",
 *     "list" = "Drupal\block\BlockListController",
 *     "form" = {
 *       "default" = "Drupal\block\BlockFormController",
 *       "delete" = "Drupal\block\Form\BlockDeleteForm"
 *     }
 *   },
 *   config_prefix = "block.block",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "admin/structure/block/manage/{block}"
 *   }
 * )
 */
class Block extends ConfigEntityBase implements BlockInterface {

  /**
   * The ID of the block.
   *
   * @var string
   */
  public $id;

  /**
   * The block UUID.
   *
   * @var string
   */
  public $uuid;

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
   * The visibility settings.
   *
   * @var array
   */
  protected $visibility;

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::__construct();
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->pluginBag = new BlockPluginBag(\Drupal::service('plugin.manager.block'), array($this->plugin), $this->get('settings'), $this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->pluginBag->get($this->plugin);
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::label();
   */
  public function label($langcode = NULL) {
    $settings = $this->get('settings');
    return $settings['label'];
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
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    $this->set('settings', $this->getPlugin()->getConfiguration());
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
