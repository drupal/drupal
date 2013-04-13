<?php

/**
 * @file
 * Contains \Drupal\block\Plugin\Core\Entity\Block.
 */

namespace Drupal\block\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Defines a Block configuration entity class.
 *
 * @EntityType(
 *   id = "block",
 *   label = @Translation("Block"),
 *   module = "block",
 *   controller_class = "Drupal\block\BlockStorageController",
 *   access_controller_class = "Drupal\block\BlockAccessController",
 *   render_controller_class = "Drupal\block\BlockRenderController",
 *   list_controller_class = "Drupal\block\BlockListController",
 *   form_controller_class = {
 *     "default" = "Drupal\block\BlockFormController"
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
class Block extends ConfigEntityBase {

  /**
   * The ID of the block.
   *
   * @var string
   */
  public $id;

  /**
   * The block label.
   *
   * @var string
   */
  public $label;

  /**
   * Whether the block label is displayed to end users.
   *
   * When this is set to BLOCK_LABEL_VISIBLE (the default value), the label is
   * rendered as header in the block markup. Otherwise, the label is passed
   * to the block template as a separate $label_hidden variable.
   *
   * @var string
   */
  public $label_display = BLOCK_LABEL_VISIBLE;

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
   * The plugin instance.
   *
   * @var \Drupal\block\BlockInterface
   */
  protected $instance;

  /**
   * The region this block is placed in.
   *
   * @var string
   */
  protected $region = BLOCK_REGION_NONE;

  /**
   * Settings to control the block visibility.
   *
   * @var array
   */
  protected $visibility = array();

  /**
   * The weight of the block.
   *
   * @var int
   */
  protected $weight;

  /**
   * The module owning this plugin.
   *
   * @var string
   */
  protected $module;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\block\BlockInterface
   *   The plugin instance for this block.
   */
  public function getPlugin() {
    if (!$this->instance) {
      // Throw an exception if no plugin string was provided.
      if (!$this->plugin) {
        throw new PluginException(format_string("The block '@block' did not specify a plugin.", array('@block' => $this->id())));
      }

      // Create a plugin instance and store its configuration as settings.
      try {
        $this->instance = drupal_container()->get('plugin.manager.block')->createInstance($this->plugin, $this->settings, $this);
        $this->settings += $this->instance->getConfig();
      }
      catch (PluginException $e) {
        // Ignore blocks belonging to disabled modules, but re-throw valid
        // exceptions when the module is enabled and the plugin is misconfigured.
        if (empty($this->module) || module_exists($this->module)) {
          throw $e;
        }
      }
    }
    return $this->instance;
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
    $names = array(
      'id',
      'label',
      'label_display',
      'uuid',
      'region',
      'weight',
      'module',
      'status',
      'visibility',
      'plugin',
      'settings',
      'langcode',
    );
    $properties = array();
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

}
