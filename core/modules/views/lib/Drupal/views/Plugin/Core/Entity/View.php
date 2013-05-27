<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Core\Entity\View.
 */

namespace Drupal\views\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\views\Views;
use Drupal\views_ui\ViewUI;
use Drupal\views\ViewStorageInterface;
use Drupal\views\ViewExecutable;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a View configuration entity class.
 *
 * @EntityType(
 *   id = "view",
 *   label = @Translation("View"),
 *   module = "views",
 *   controllers = {
 *     "storage" = "Drupal\views\ViewStorageController"
 *   },
 *   config_prefix = "views.view",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
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
  protected $core = DRUPAL_CORE_COMPATIBILITY;

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
   * The UUID for this entity.
   *
   * @var string
   */
  public $uuid = NULL;

  /**
   * Stores a reference to the executable version of this view.
   *
   * @var Drupal\views\ViewExecutable
   */
  protected $executable;

  /**
   * The module implementing this view.
   *
   * @var string
   */
  protected $module = 'views';

  /**
   * Overrides Drupal\Core\Entity\EntityInterface::get().
   */
  public function get($property_name, $langcode = NULL) {
    // Ensure that an executable View is available.
    if ($property_name == 'executable' && !isset($this->{$property_name})) {
      $this->set('executable', Views::executableFactory()->get($this));
    }

    return parent::get($property_name, $langcode);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityInterface::uri().
   */
  public function uri() {
    return array(
      'path' => 'admin/structure/views/view/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
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
  public function label($langcode = NULL) {
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
      'display_title' => $title,
      'position' => count($this->display),
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
   * Creates a new display and a display handler instance for it.
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
   * @return string|\Drupal\views\Plugin\views\display\DisplayPluginBase
   *   A new display plugin instance if executable is set, the new display ID
   *   otherwise.
   */
  public function newDisplay($plugin_id = 'page', $title = NULL, $id = NULL) {
    $id = $this->addDisplay($plugin_id, $title, $id);

    // We can't use get() here as it will create an ViewExecutable instance if
    // there is not already one.
    if (isset($this->executable)) {
      $executable = $this->get('executable');
      $executable->initDisplay();
      $executable->displayHandlers->addInstanceID($id);
      return $executable->displayHandlers->get($id);
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
   * Gets a list of displays included in the view.
   *
   * @return array
   *   An array of display types that this view includes.
   */
  function getDisplaysList() {
    $manager = Views::pluginManager('display');
    $displays = array();
    foreach ($this->display as $display) {
      $definition = $manager->getDefinition($display['display_plugin']);
      if (!empty($definition['admin'])) {
        $displays[$definition['admin']] = TRUE;
      }
    }

    ksort($displays);
    return array_keys($displays);
  }

  /**
   * Gets a list of paths assigned to the view.
   *
   * @return array
   *   An array of paths for this view.
   */
  public function getPaths() {
    $all_paths = array();
    if (empty($this->display)) {
      $all_paths[] = t('Edit this view to add a display.');
    }
    else {
      foreach ($this->display as $display) {
        if (!empty($display['display_options']['path'])) {
          $path = $display['display_options']['path'];
          if ($this->status() && strpos($path, '%') === FALSE) {
            $all_paths[] = l('/' . $path, $path);
          }
          else {
            $all_paths[] = check_plain('/' . $path);
          }
        }
      }
    }

    return array_unique($all_paths);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::getExportProperties();
   */
  public function getExportProperties() {
    $names = array(
      'base_field',
      'base_table',
      'core',
      'description',
      'status',
      'display',
      'label',
      'module',
      'id',
      'tag',
      'uuid',
      'langcode',
    );
    $properties = array();
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

}
