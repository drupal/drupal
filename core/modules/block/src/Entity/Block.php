<?php

namespace Drupal\block\Entity;

use Drupal\block\BlockAccessControlHandler;
use Drupal\block\BlockForm;
use Drupal\block\BlockListBuilder;
use Drupal\block\BlockViewBuilder;
use Drupal\block\Form\BlockDeleteForm;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\block\BlockPluginCollection;
use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Block configuration entity class.
 */
#[ConfigEntityType(
  id: 'block',
  label: new TranslatableMarkup('Block'),
  label_collection: new TranslatableMarkup('Blocks'),
  label_singular: new TranslatableMarkup('block'),
  label_plural: new TranslatableMarkup('blocks'),
  entity_keys: [
    'id' => 'id',
    'status' => 'status',
  ],
  handlers: [
    'access' => BlockAccessControlHandler::class,
    'view_builder' => BlockViewBuilder::class,
    'list_builder' => BlockListBuilder::class,
    'form' => [
      'default' => BlockForm::class,
      'delete' => BlockDeleteForm::class,
    ],
  ],
  links: [
    'delete-form' => '/admin/structure/block/manage/{block}/delete',
    'edit-form' => '/admin/structure/block/manage/{block}',
    'enable' => '/admin/structure/block/manage/{block}/enable',
    'disable' => '/admin/structure/block/manage/{block}/disable',
  ],
  admin_permission: 'administer blocks',
  label_count: [
    'singular' => '@count block',
    'plural' => '@count blocks',
  ],
  lookup_keys: [
    'theme',
  ],
  config_export: [
    'id',
    'theme',
    'region',
    'weight',
    'provider',
    'plugin',
    'settings',
    'visibility',
  ],
)]
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
  protected $settings = [];

  /**
   * The region this block is placed in.
   *
   * @var string
   */
  protected $region;

  /**
   * The block weight.
   *
   * @var int
   */
  protected $weight = 0;

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

    // Sort by weight.
    $weight = $a->getWeight() - $b->getWeight();
    if ($weight) {
      return $weight;
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
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // EntityBase::postSave() calls EntityBase::invalidateTagsOnSave(), which
    // only handles the regular cases. The Block entity has one special case: a
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
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set block region'), pluralize: FALSE)]
  public function setRegion($region) {
    $this->region = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set block weight'), pluralize: FALSE)]
  public function setWeight($weight) {
    $this->weight = (int) $weight;
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

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!is_int($this->weight)) {
      @trigger_error('Saving a block with a non-integer weight is deprecated in drupal:11.1.0 and removed in drupal:12.0.0. See https://www.drupal.org/node/3462474', E_USER_DEPRECATED);
      $this->setWeight((int) $this->weight);
    }

    // Ensure the region is valid to mirror the behavior of block_rebuild().
    // This is done primarily for backwards compatibility support of
    // \Drupal\block\BlockInterface::BLOCK_REGION_NONE.
    $regions = system_region_list($this->theme);
    if (!isset($regions[$this->region]) && $this->status()) {
      $this
        ->setRegion(system_default_region($this->theme))
        ->disable();
    }
  }

  /**
   * Validates that a region exists in the active theme.
   *
   * @param null|string $region
   *   The region to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation context.
   */
  public static function validateRegion(?string $region, ExecutionContextInterface $context): void {
    if ($theme = $context->getRoot()->get('theme')->getValue()) {
      if (!array_key_exists($region, system_region_list($theme))) {
        $context->addViolation('This is not a valid region of the %theme theme.', ['%theme' => $theme]);
      }
    }
    else {
      $context->addViolation('This block does not say which theme it appears in.');
    }
  }

}
