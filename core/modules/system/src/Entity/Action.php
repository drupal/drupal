<?php

namespace Drupal\system\Entity;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\system\ActionConfigEntityInterface;
use Drupal\Core\Action\ActionPluginCollection;

/**
 * Defines the configured action entity.
 *
 * @ConfigEntityType(
 *   id = "action",
 *   label = @Translation("Action"),
 *   label_collection = @Translation("Actions"),
 *   label_singular = @Translation("action"),
 *   label_plural = @Translation("actions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count action",
 *     plural = "@count actions",
 *   ),
 *   admin_permission = "administer actions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "plugin",
 *     "configuration",
 *   }
 * )
 */
class Action extends ConfigEntityBase implements ActionConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * The name (plugin ID) of the action.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the action.
   *
   * @var string
   */
  protected $label;

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
  protected $configuration = [];

  /**
   * The plugin ID of the action.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that stores action plugins.
   *
   * @var \Drupal\Core\Action\ActionPluginCollection
   */
  protected $pluginCollection;

  /**
   * Encapsulates the creation of the action's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The action's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new ActionPluginCollection(\Drupal::service('plugin.manager.action'), $this->plugin, $this->configuration);
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['configuration' => $this->getPluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id) {
    $this->plugin = $plugin_id;
    $this->getPluginCollection()->addInstanceId($plugin_id);
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
    return $this->getPlugin() instanceof ConfigurableInterface;
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
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    /** @var \Drupal\system\ActionConfigEntityInterface $a */
    /** @var \Drupal\system\ActionConfigEntityInterface $b */
    $a_type = $a->getType();
    $b_type = $b->getType();
    if ($a_type != $b_type) {
      return strnatcasecmp($a_type, $b_type);
    }
    return parent::sort($a, $b);
  }

}
