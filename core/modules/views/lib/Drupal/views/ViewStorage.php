<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorage.
 */

namespace Drupal\views;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\views_ui\ViewUI;

/**
 * Defines a ViewStorage configuration entity class.
 */
class ViewStorage extends ConfigEntityBase implements ViewStorageInterface {

  /**
   * The name of the base table this view will use.
   *
   * @var string
   */
  public $base_table = 'node';

  /**
   * The name of the view.
   *
   * @var string
   */
  public $name = '';

  /**
   * The description of the view, which is used only in the interface.
   *
   * @var string
   */
  public $description = '';

  /**
   * The "tags" of a view.
   *
   * The tags are stored as a single string, though it is used as multiple tags
   * for example in the views overview.
   *
   * @var string
   */
  public $tag = '';

  /**
   * The human readable name of the view.
   *
   * @var string
   */
  public $human_name = '';

  /**
   * The core version the view was created for.
   *
   * @var int
   */
  public $core = DRUPAL_CORE_COMPATIBILITY;

  /**
   * The views API version this view was created by.
   *
   * @var string
   */
  public $api_version = VIEWS_API_VERSION;

  /**
   * Stores all display handlers of this view.
   *
   * An array containing Drupal\views\Plugin\views\display\DisplayPluginBase
   * objects.
   *
   * @var array
   */
  public $display;

  /**
   * The name of the base field to use.
   *
   * @var string
   */
  public $base_field = 'nid';

  /**
   * Returns whether the view's status is disabled or not.
   *
   * This value is used for exported view, to provide some default views which
   * aren't enabled.
   *
   * @var bool
   */
  public $disabled = FALSE;

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
  public $module = 'views';

  /**
   * Stores the executable version of this view.
   *
   * @param Drupal\views\ViewExecutable $executable
   *   The executable version of this view.
   */
  public function setExecutable(ViewExecutable $executable) {
    $this->executable = $executable;
  }

  /**
   * Retrieves the executable version of this view.
   *
   * @param bool $reset
   *   Get a new Drupal\views\ViewExecutable instance.
   * @param bool $ui
   *   If this should return Drupal\views_ui\ViewUI instead.
   *
   * @return Drupal\views\ViewExecutable
   *   The executable version of this view.
   */
  public function getExecutable($reset = FALSE, $ui = FALSE) {
    if (!isset($this->executable) || $reset) {
     // @todo Remove this approach and use proper dependency injection.
      if ($ui) {
        $executable = new ViewUI($this);
      }
      else {
        $executable = new ViewExecutable($this);
      }
      $this->setExecutable($executable);
    }
    return $this->executable;
  }

  /**
   * Initializes the display.
   *
   * @todo Inspect calls to this and attempt to clean up.
   * @see Drupal\views\ViewExecutable::initDisplay()
   */
  public function initDisplay() {
    $this->getExecutable()->initDisplay();
  }

  /**
   * Returns the name of the module implementing this view.
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityInterface::uri().
   */
  public function uri() {
    $info = $this->entityInfo();
    return array(
      'path' => $info['list path'],
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::enable().
   */
  public function enable() {
    $this->disabled = FALSE;
    $this->save();
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::disable().
   */
  public function disable() {
    $this->disabled = TRUE;
    $this->save();
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::isEnabled().
   */
  public function isEnabled() {
    return !$this->disabled;
  }

  /**
   * Return the human readable name for a view.
   *
   * When a certain view doesn't have a human readable name return the machine readable name.
   */
  public function getHumanName() {
    if (!empty($this->human_name)) {
      $human_name = $this->human_name;
    }
    else {
      $human_name = $this->name;
    }
    return $human_name;
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

    $plugin = views_get_plugin_definition('display', $plugin_id);
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
      'position' => NULL,
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
   * Creates a new display and a display handler for it.
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
   * @return Drupal\views\Plugin\views\display\DisplayPluginBase
   *   A reference to the new handler object.
   */
  public function &newDisplay($plugin_id = 'page', $title = NULL, $id = NULL) {
    $id = $this->addDisplay($plugin_id, $title, $id);
    return $this->getExecutable()->newDisplay($id);
  }

  /**
   * Gets a list of displays included in the view.
   *
   * @return array
   *   An array of display types that this view includes.
   */
  function getDisplaysList() {
    $manager = drupal_container()->get('plugin.manager.views.display');
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
          if ($this->isEnabled() && strpos($path, '%') === FALSE) {
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

}
