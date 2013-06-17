<?php

/**
 * @file
 * Contains \Drupal\block\Plugin\Core\Entity\Block.
 */

namespace Drupal\block\Plugin\Core\Entity;

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
 *     "storage" = "Drupal\block\BlockStorageController",
 *     "access" = "Drupal\block\BlockAccessController",
 *     "render" = "Drupal\block\BlockRenderController",
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
 *     "uuid" = "uuid",
 *     "status" = "status"
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
  protected $region = BLOCK_REGION_NONE;

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

    $this->pluginBag = new BlockPluginBag(\Drupal::service('plugin.manager.block'), array($this->plugin), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->pluginBag->get($this->plugin);
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::uri();
   */
  public function uri() {
    return array(
      'path' => 'admin/structure/block/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }
  /**
   * Overrides \Drupal\Core\Entity\Entity::label();
   */
  public function label($langcode = NULL) {
    $settings = $this->get('settings');
    return $settings['label'];
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::get();
   */
  public function get($property_name, $langcode = NULL) {
    // The theme is stored in the entity ID.
    $value = parent::get($property_name, $langcode);
    if ($property_name == 'theme' && !$value) {
      list($value) = explode('.', $this->id());
    }
    return $value;
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::getExportProperties();
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $names = array(
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
    $this->set('settings', $this->getPlugin()->getConfig());
  }

}
