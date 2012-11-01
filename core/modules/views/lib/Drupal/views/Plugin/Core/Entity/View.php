<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Core\Entity\View.
 */

namespace Drupal\views\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\views_ui\ViewUI;
use Drupal\views\ViewStorageInterface;
use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a View configuration entity class.
 *
 * @Plugin(
 *   id = "view",
 *   label = @Translation("View"),
 *   module = "views",
 *   controller_class = "Drupal\views\ViewStorageController",
 *   list_controller_class = "Drupal\views_ui\ViewListController",
 *   form_controller_class = {
 *     "edit" = "Drupal\views_ui\ViewEditFormController",
 *     "add" = "Drupal\views_ui\ViewAddFormController",
 *     "preview" = "Drupal\views_ui\ViewPreviewFormController"
 *   },
 *   config_prefix = "views.view",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "human_name",
 *     "uuid" = "uuid"
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
  protected $core = DRUPAL_CORE_COMPATIBILITY;

  /**
   * The views API version this view was created by.
   *
   * @var string
   */
  protected $api_version = VIEWS_API_VERSION;

  /**
   * Stores all display handlers of this view.
   *
   * An array containing Drupal\views\Plugin\views\display\DisplayPluginBase
   * objects.
   *
   * @var array
   */
  protected $display;

  /**
   * The name of the base field to use.
   *
   * @var string
   */
  protected $base_field = 'nid';

  /**
   * Returns whether the view's status is disabled or not.
   *
   * This value is used for exported view, to provide some default views which
   * aren't enabled.
   *
   * @var bool
   */
  protected $disabled = FALSE;

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
      $this->set('executable', new ViewExecutable($this));
    }

    return parent::get($property_name, $langcode);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityInterface::uri().
   */
  public function uri() {
    return array(
      'path' => 'admin/structure/views/view/' . $this->id(),
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('name');
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
    if (!$human_name = $this->get('human_name')) {
      $human_name = $this->get('name');
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
    return $this->get('executable')->newDisplay($id);
  }

  /**
   * Retrieves a specific display's configuration by reference.
   *
   * @param string $display_id
   *   The display ID to retrieve, e.g., 'default', 'page_1', 'block_2'.
   *
   * @return array
   *   A reference to the specified display configuration.
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
