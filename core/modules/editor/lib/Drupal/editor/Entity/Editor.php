<?php

/**
 * @file
 * Contains \Drupal\editor\Entity\Editor.
 */

namespace Drupal\editor\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\EditorInterface;

/**
 * Defines the configured text editor entity.
 *
 * @EntityType(
 *   id = "editor",
 *   label = @Translation("Editor"),
 *   module = "editor",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController"
 *   },
 *   config_prefix = "editor.editor",
 *   entity_keys = {
 *     "id" = "format",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Editor extends ConfigEntityBase implements EditorInterface {

  /**
   * The machine name of the text format with which this configured text editor
   * is associated.
   *
   * @var string
   */
  public $format;

  /**
   * The name (plugin ID) of the text editor.
   *
   * @var string
   */
  public $editor;

  /**
   * The array of settings for the text editor.
   *
   * @var array
   */
  public $settings = array();

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->format;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::label().
   */
  public function label($langcode = NULL) {
    $format = filter_format_load($this->format);
    return $format->name;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::__construct()
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $manager = \Drupal::service('plugin.manager.editor');
    $plugin = $manager->createInstance($this->editor);

    // Initialize settings, merging module-provided defaults.
    $default_settings = $plugin->getDefaultSettings();
    $default_settings += module_invoke_all('editor_default_settings', $this->editor);
    drupal_alter('editor_default_settings', $default_settings, $this->editor);
    $this->settings += $default_settings;
  }

}
