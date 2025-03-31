<?php

namespace Drupal\system\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\system\ActionConfigEntityInterface;
use Drupal\Core\Action\ActionPluginCollection;

/**
 * Defines the configured action entity.
 */
#[ConfigEntityType(
  id: 'action',
  label: new TranslatableMarkup('Action'),
  label_collection: new TranslatableMarkup('Actions'),
  label_singular: new TranslatableMarkup('action'),
  label_plural: new TranslatableMarkup('actions'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  admin_permission: 'administer actions',
  label_count: [
    'singular' => '@count action',
    'plural' => '@count actions',
  ],
  config_export: [
    'id',
    'label',
    'type',
    'plugin',
    'configuration',
  ],
)]
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
   * @var string|null
   */
  protected $type = NULL;

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
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    // When no label is specified for this action config entity, default to the
    // label of the used action plugin.
    if (!array_key_exists('label', $values) && array_key_exists('plugin', $values)) {
      try {
        $action_plugin_manager = \Drupal::service('plugin.manager.action');
        assert($action_plugin_manager instanceof PluginManagerInterface);
        $action_plugin_definition = $action_plugin_manager->getDefinition($values['plugin']);
        // @see \Drupal\Core\Annotation\Action::$label
        assert(array_key_exists('label', $action_plugin_definition));
        $values['label'] = $action_plugin_definition['label'];
      }
      catch (PluginNotFoundException) {
      }
    }
    return parent::create($values);
  }

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
   * Helper callback for uasort() to compare configuration entities by weight and label.
   */
  public static function compare(ConfigEntityInterface $a, ConfigEntityInterface $b, \Collator $collator): int {
    /** @var \Drupal\system\ActionConfigEntityInterface $a */
    /** @var \Drupal\system\ActionConfigEntityInterface $b */
    $a_type = $a->getType();
    $b_type = $b->getType();
    if ($a_type != $b_type) {
      return $collator->compare($a_type, $b_type);
    }
    return parent::compare($a, $b, $collator);
  }

  /**
   * Helper callback for uasort() to sort configuration entities.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   * \Drupal\Core\Config\Entity\ConfigEntityBase::sortEntities() instead.
   *
   * @see https://www.drupal.org/project/drupal/issues/2265487
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    @trigger_error(__CLASS__ . '::' . __FUNCTION__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use ' . __CLASS__ . '::sortEntities() instead. See https://www.drupal.org/project/drupal/issues/2265487', E_USER_DEPRECATED);
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
