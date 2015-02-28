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
 *   label = @Translation("Text Editor"),
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
   *
   * @see getFilterFormat()
   */
  protected $format;

  /**
   * The name (plugin ID) of the text editor.
   *
   * @var string
   */
  protected $editor;

  /**
   * The structured array of text editor plugin-specific settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * The structured array of image upload settings.
   *
   * @var array
   */
  protected $image_upload = array();

  /**
   * The filter format this text editor is associated with.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filterFormat;

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorPluginManager;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $plugin = $this->editorPluginManager()->createInstance($this->editor);
    $this->settings += $plugin->getDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getFilterFormat()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Create a dependency on the associated FilterFormat.
    $this->addDependency('config', $this->getFilterFormat()->getConfigDependencyName());
    // @todo use EntityWithPluginCollectionInterface so configuration between
    //   config entity and dependency on provider is managed automatically.
    $definition = $this->editorPluginManager()->createInstance($this->editor)->getPluginDefinition();
    $this->addDependency('module', $definition['provider']);
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAssociatedFilterFormat() {
    return $this->format !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterFormat() {
    if (!$this->filterFormat) {
      $this->filterFormat = \Drupal::entityManager()->getStorage('filter_format')->load($this->format);
    }
    return $this->filterFormat;
  }

  /**
   * Returns the editor plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected function editorPluginManager() {
    if (!$this->editorPluginManager) {
      $this->editorPluginManager = \Drupal::service('plugin.manager.editor');
    }

    return $this->editorPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditor() {
    return $this->editor;
  }

  /**
   * {@inheritdoc}
   */
  public function setEditor($editor) {
    $this->editor = $editor;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageUploadSettings() {
    return $this->image_upload;
  }

  /**
   * {@inheritdoc}
   */
  public function setImageUploadSettings(array $image_upload_settings) {
    $this->image_upload = $image_upload_settings;
    return $this;
  }

}
