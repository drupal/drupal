<?php

/**
 * @file
 * Contains \Drupal\editor\Entity\Editor.
 */

namespace Drupal\editor\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\editor\EditorInterface;

/**
 * Defines the configured text editor entity.
 *
 * @ConfigEntityType(
 *   id = "editor",
 *   label = @Translation("Editor"),
 *   entity_keys = {
 *     "id" = "format"
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
   * The array of text editor plugin-specific settings for the text editor.
   *
   * @var array
   */
  public $settings = array();

  /**
   * The array of image upload settings for the text editor.
   *
   * @var array
   */
  public $image_upload = array();

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->format;
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
    $default_settings += \Drupal::moduleHandler()->invokeAll('editor_default_settings', array($this->editor));
    \Drupal::moduleHandler()->alter('editor_default_settings', $default_settings, $this->editor);
    $this->settings += $default_settings;
  }

}
