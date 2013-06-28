<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Core\Entity\Action.
 */

namespace Drupal\system\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\system\ActionConfigEntityInterface;
use Drupal\Core\Action\ActionBag;
use Drupal\Core\Action\ConfigurableActionInterface;

/**
 * Defines the configured action entity.
 *
 * @EntityType(
 *   id = "action",
 *   label = @Translation("Action"),
 *   module = "system",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "access" = "Drupal\action\ActionAccessController"
 *   },
 *   config_prefix = "action.action",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Action extends ConfigEntityBase implements ActionConfigEntityInterface {

  /**
   * The name (plugin ID) of the action.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the action.
   *
   * @var string
   */
  public $label;

  /**
   * The UUID of the action.
   *
   * @var string
   */
  public $uuid;

  /**
   * The action type.
   *
   * @var string
   */
  protected $type;

  /**
   * The configuration of the action.
   *
   * @var array
   */
  protected $configuration = array();

  /**
   * The plugin ID of the action.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin bag that stores action plugins.
   *
   * @var \Drupal\Core\Action\ActionBag
   */
  protected $pluginBag;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->pluginBag = new ActionBag(\Drupal::service('plugin.manager.action'), array($this->plugin), $this->configuration);
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
  public function getPluginDefinition() {
    return $this->getPlugin()->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $entities) {
    return $this->getPlugin()->executeMultiple($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    return $this->getPlugin() instanceof ConfigurableActionInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
      'path' => 'admin/config/system/actions/configure/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function sort($a, $b) {
    $a_type = $a->getType();
    $b_type = $b->getType();
    if ($a_type != $b_type) {
      return strnatcasecmp($a_type, $b_type);
    }
    return parent::sort($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $names = array(
      'type',
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
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    $plugin = $this->getPlugin();
    // If this plugin has any configuration, ensure that it is set.
    if ($plugin instanceof ConfigurableActionInterface) {
      $this->set('configuration', $plugin->getConfiguration());
    }
  }

}
