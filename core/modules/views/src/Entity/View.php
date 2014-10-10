<?php

/**
 * @file
 * Definition of Drupal\views\Entity\View.
 */

namespace Drupal\views\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Views;
use Drupal\views_ui\ViewUI;
use Drupal\views\ViewStorageInterface;

/**
 * Defines a View configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "view",
 *   label = @Translation("View"),
 *   handlers = {
 *     "access" = "Drupal\views\ViewAccessControlHandler"
 *   },
 *   admin_permission = "administer views",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   }
 * )
 */
class View extends ConfigEntityBase implements ViewStorageInterface {

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
  public $id = NULL;

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
   * @var int
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
   * Gets an executable instance for this view.
   *
   * @return \Drupal\views\ViewExecutable
   *   A view executable instance.
   */
  public function getExecutable() {
    // Ensure that an executable View is available.
    if (!isset($this->executable)) {
      $this->executable = Views::executableFactory()->get($this);
    }

    return $this->executable;
  }

  /**
   * Overrides Drupal\Core\Config\Entity\ConfigEntityBase::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    unset($duplicate->executable);
    return $duplicate;
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::label().
   *
   * When a certain view doesn't have a label return the ID.
   */
  public function label() {
    if (!$label = $this->get('label')) {
      $label = $this->id();
    }
    return $label;
  }

  /**
   * Adds a new display handler to the view, automatically creating an ID.
   *
   * @param string $plugin_id
   *   (optional) The plugin type from the Views plugin annotation. Defaults to
   *   'page'.
   * @param string $title
   *   (optional) The title of the display. Defaults to NULL.
   * @param string $id
   *   (optional) The ID to use, e.g., 'default', 'page_1', 'block_2'. Defaults
   *   to NULL.
   *
   * @return string|false
   *   The key to the display in $view->display, or FALSE if no plugin ID was
   *   provided.
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
      'provider' => $plugin['provider'],
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
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Ensure that the view is dependant on the module that implements the view.
    $this->addDependency('module', $this->module);
    // Ensure that the view is dependent on the module that provides the schema
    // for the base table.
    $schema = $this->drupalGetSchema($this->base_table);
    // @todo Entity base tables are no longer registered in hook_schema(). Once
    //   we automate the views data for entity types add the entity type
    //   type provider as a dependency. See https://drupal.org/node/1740492.
    if ($schema && $this->module != $schema['module']) {
      $this->addDependency('module', $schema['module']);
    }

    $handler_types = array();
    foreach (Views::getHandlerTypes() as $type) {
      $handler_types[] = $type['plural'];
    }
    foreach ($this->get('display') as $display) {
      // Add dependency for the display itself.
      if (isset($display['provider'])) {
        $this->addDependency('module', $display['provider']);
      }

      // Collect all dependencies of all handlers.
      foreach ($handler_types as $handler_type) {
        if (!empty($display['display_options'][$handler_type])) {
          foreach ($display['display_options'][$handler_type] as $handler) {
            // Add the provider as dependency.
            if (isset($handler['provider'])) {
              $this->addDependency('module', $handler['provider']);
            }
            // Add the additional dependencies from the handler configuration.
            if (!empty($handler['dependencies'])) {
              $this->addDependencies($handler['dependencies']);
          }
        }
      }
    }

      // Collect all dependencies of plugins.
      foreach (Views::getPluginTypes('plugin') as $plugin_type) {
        if (!empty($display['display_options'][$plugin_type]['options']['dependencies'])) {
          $this->addDependencies($display['display_options'][$plugin_type]['options']['dependencies']);
        }
      }
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

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
    foreach ($displays as $display_id => $display) {
      $executable->setDisplay($display_id);

      list($display['cache_metadata']['cacheable'], $display['cache_metadata']['contexts']) = $executable->getDisplay()->calculateCacheMetadata();
      // Always include at least the language context as there will be most
      // probable translatable strings in the view output.
      $display['cache_metadata']['contexts'][] = 'cache.context.language';
      $display['cache_metadata']['contexts'] = array_unique($display['cache_metadata']['contexts']);
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

    // Rebuild the router case the view got enabled.
    if (!isset($this->original) || ($this->status() != $this->original->status())) {
      \Drupal::service('router.builder_indicator')->setRebuildNeeded();
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
    /** @var \Drupal\views\ViewStorageInterface $entity */
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

    $tempstore = \Drupal::service('user.tempstore')->get('views');
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
    // Sort the displays.
    uasort($displays, function ($display1, $display2) {
      if ($display1['position'] != $display2['position']) {
        return $display1['position'] < $display2['position'] ? -1 : 1;
      }
      return 0;
    });
    $this->set('display', $displays);
  }

  /**
   * Wraps drupal_get_schema().
   */
  protected function drupalGetSchema($table = NULL, $rebuild = FALSE) {
    return drupal_get_schema($table, $rebuild);
  }

}
