<?php

/**
 * @file
 * Contains \Drupal\views\Entity\View.
 */

namespace Drupal\views\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Views;
use Drupal\views\ViewEntityInterface;

/**
 * Defines a View configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "view",
 *   label = @Translation("View", context = "View entity type"),
 *   handlers = {
 *     "access" = "Drupal\views\ViewAccessControlHandler"
 *   },
 *   admin_permission = "administer views",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "module",
 *     "description",
 *     "tag",
 *     "base_table",
 *     "base_field",
 *     "core",
 *     "display",
 *   }
 * )
 */
class View extends ConfigEntityBase implements ViewEntityInterface {

  /**
   * The name of the base table this view will use.
   *
   * @var string
   */
  protected $base_table = 'node';

  /**
   * The unique ID of the view.
   *
   * @var string
   */
  protected $id = NULL;

  /**
   * The label of the view.
   */
  protected $label;

  /**
   * The description of the view, which is used only in the interface.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The "tags" of a view.
   *
   * The tags are stored as a single string, though it is used as multiple tags
   * for example in the views overview.
   *
   * @var string
   */
  protected $tag = '';

  /**
   * The core version the view was created for.
   *
   * @var string
   */
  protected $core = \Drupal::CORE_COMPATIBILITY;

  /**
   * Stores all display handlers of this view.
   *
   * An array containing Drupal\views\Plugin\views\display\DisplayPluginBase
   * objects.
   *
   * @var array
   */
  protected $display = array();

  /**
   * The name of the base field to use.
   *
   * @var string
   */
  protected $base_field = 'nid';

  /**
   * Stores a reference to the executable version of this view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $executable;

  /**
   * The module implementing this view.
   *
   * @var string
   */
  protected $module = 'views';

  /**
   * {@inheritdoc}
   */
  public function getExecutable() {
    // Ensure that an executable View is available.
    if (!isset($this->executable)) {
      $this->executable = Views::executableFactory()->get($this);
    }

    return $this->executable;
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    unset($duplicate->executable);
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (!$label = $this->get('label')) {
      $label = $this->id();
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function addDisplay($plugin_id = 'page', $title = NULL, $id = NULL) {
    if (empty($plugin_id)) {
      return FALSE;
    }

    $plugin = Views::pluginManager('display')->getDefinition($plugin_id);

    if (empty($plugin)) {
      $plugin['title'] = t('Broken');
    }

    if (empty($id)) {
      $id = $this->generateDisplayId($plugin_id);

      // Generate a unique human-readable name by inspecting the counter at the
      // end of the previous display ID, e.g., 'page_1'.
      if ($id !== 'default') {
        preg_match("/[0-9]+/", $id, $count);
        $count = $count[0];
      }
      else {
        $count = '';
      }

      if (empty($title)) {
        // If there is no title provided, use the plugin title, and if there are
        // multiple displays, append the count.
        $title = $plugin['title'];
        if ($count > 1) {
          $title .= ' ' . $count;
        }
      }
    }

    $display_options = array(
      'display_plugin' => $plugin_id,
      'id' => $id,
      // Cast the display title to a string since it is an object.
      // @see \Drupal\Core\StringTranslation\TranslationWrapper
      'display_title' => (string) $title,
      'position' => $id === 'default' ? 0 : count($this->display),
      'display_options' => array(),
    );

    // Add the display options to the view.
    $this->display[$id] = $display_options;
    return $id;
  }

  /**
   * Generates a display ID of a certain plugin type.
   *
   * @param string $plugin_id
   *   Which plugin should be used for the new display ID.
   *
   * @return string
   */
  protected function generateDisplayId($plugin_id) {
    // 'default' is singular and is unique, so just go with 'default'
    // for it. For all others, start counting.
    if ($plugin_id == 'default') {
      return 'default';
    }
    // Initial ID.
    $id = $plugin_id . '_1';
    $count = 1;

    // Loop through IDs based upon our style plugin name until
    // we find one that is unused.
    while (!empty($this->display[$id])) {
      $id = $plugin_id . '_' . ++$count;
    }

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function &getDisplay($display_id) {
    return $this->display[$display_id];
  }

  /**
   * {@inheritdoc}
   */
  public function duplicateDisplayAsType($old_display_id, $new_display_type) {
    $executable = $this->getExecutable();
    $display = $executable->newDisplay($new_display_type);
    $new_display_id = $display->display['id'];
    $displays = $this->get('display');

    // Let the display title be generated by the addDisplay method and set the
    // right display plugin, but keep the rest from the original display.
    $display_duplicate = $displays[$old_display_id];
    unset($display_duplicate['display_title']);
    unset($display_duplicate['display_plugin']);

    $displays[$new_display_id] = NestedArray::mergeDeep($displays[$new_display_id], $display_duplicate);
    $displays[$new_display_id]['id'] = $new_display_id;

    // First set the displays.
    $this->set('display', $displays);

    // Ensure that we just copy display options, which are provided by the new
    // display plugin.
    $executable->setDisplay($new_display_id);

    $executable->display_handler->filterByDefinedOptions($displays[$new_display_id]['display_options']);
    // Update the display settings.
    $this->set('display', $displays);

    return $new_display_id;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Ensure that the view is dependant on the module that implements the view.
    $this->addDependency('module', $this->module);

    $executable = $this->getExecutable();
    $executable->initDisplay();
    $executable->initStyle();

    foreach ($executable->displayHandlers as $display) {
      // Calculate the dependencies each display has.
      $this->calculatePluginDependencies($display);
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Sort the displays.
    $display = $this->get('display');
    ksort($display);
    $this->set('display', array('default' => $display['default']) + $display);

    // @todo Check whether isSyncing is needed.
    if (!$this->isSyncing()) {
      $this->addCacheMetadata();
    }
  }

  /**
   * Fills in the cache metadata of this view.
   *
   * Cache metadata is set per view and per display, and ends up being stored in
   * the view's configuration. This allows Views to determine very efficiently:
   * - whether a view is cacheable at all
   * - what the cache key for a given view should be
   *
   * In other words: this allows us to do the (expensive) work of initializing
   * Views plugins and handlers to determine their effect on the cacheability of
   * a view at save time rather than at runtime.
   */
  protected function addCacheMetadata() {
    $executable = $this->getExecutable();

    $current_display = $executable->current_display;
    $displays = $this->get('display');
    foreach (array_keys($displays) as $display_id) {
      $display =& $this->getDisplay($display_id);
      $executable->setDisplay($display_id);

      list($display['cache_metadata']['cacheable'], $display['cache_metadata']['contexts']) = $executable->getDisplay()->calculateCacheMetadata();
      // Always include at least the 'languages:' context as there will most
      // probably be translatable strings in the view output.
      $display['cache_metadata']['contexts'] = Cache::mergeContexts($display['cache_metadata']['contexts'], ['languages:' . LanguageInterface::TYPE_INTERFACE]);
    }
    // Restore the previous active display.
    $executable->setDisplay($current_display);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // @todo Remove if views implements a view_builder controller.
    views_invalidate_cache();
    $this->invalidateCaches();

    // Rebuild the router if this is a new view, or it's status changed.
    if (!isset($this->original) || ($this->status() != $this->original->status())) {
      \Drupal::service('router.builder')->setRebuildNeeded();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    foreach ($entities as $entity) {
      $entity->mergeDefaultDisplaysOptions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // If there is no information about displays available add at least the
    // default display.
    $values += array(
      'display' => array(
        'default' => array(
          'display_plugin' => 'default',
          'id' => 'default',
          'display_title' => 'Master',
          'position' => 0,
          'display_options' => array(),
        ),
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    $this->mergeDefaultDisplaysOptions();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Call the remove() hook on the individual displays.
    /** @var \Drupal\views\ViewEntityInterface $entity */
    foreach ($entities as $entity) {
      $executable = Views::executableFactory()->get($entity);
      foreach ($entity->get('display') as $display_id => $display) {
        $executable->setDisplay($display_id);
        $executable->getDisplay()->remove();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    $tempstore = \Drupal::service('user.shared_tempstore')->get('views');
    foreach ($entities as $entity) {
      $tempstore->delete($entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mergeDefaultDisplaysOptions() {
    $displays = array();
    foreach ($this->get('display') as $key => $options) {
      $options += array(
        'display_options' => array(),
        'display_plugin' => NULL,
        'id' => NULL,
        'display_title' => '',
        'position' => NULL,
      );
      // Add the defaults for the display.
      $displays[$key] = $options;
    }
    $this->set('display', $displays);
  }

  /**
   * {@inheritdoc}
   */
  public function isInstallable() {
    $table_definition = \Drupal::service('views.views_data')->get($this->base_table);
    // Check whether the base table definition exists and contains a base table
    // definition. For example, taxonomy_views_data_alter() defines
    // node_field_data even if it doesn't exist as a base table.
    return $table_definition && isset($table_definition['table']['base']);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $keys = parent::__sleep();
    unset($keys[array_search('executable', $keys)]);
    return $keys;
  }

  /**
   * Invalidates cache tags.
   */
  public function invalidateCaches() {
    // Invalidate cache tags for cached rows.
    $tags = $this->getCacheTags();
    \Drupal::service('cache_tags.invalidator')->invalidateTags($tags);
  }

}
