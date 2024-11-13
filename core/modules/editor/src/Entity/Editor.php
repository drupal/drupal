<?php

namespace Drupal\editor\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\editor\EditorAccessControlHandler;
use Drupal\editor\EditorInterface;

/**
 * Defines the configured text editor entity.
 *
 * An Editor entity is created when a filter format entity (Text format) is
 * saved after selecting an editor plugin (eg: CKEditor). The ID of the
 * Editor entity will be same as the ID of the filter format entity in which
 * the editor plugin was selected.
 */
#[ConfigEntityType(
  id: 'editor',
  label: new TranslatableMarkup('Text editor'),
  label_collection: new TranslatableMarkup('Text editors'),
  label_singular: new TranslatableMarkup('text editor'),
  label_plural: new TranslatableMarkup('text editors'),
  entity_keys: [
    'id' => 'format',
  ],
  handlers: [
    'access' => EditorAccessControlHandler::class,
  ],
  label_count: [
    'singular' => '@count text editor',
    'plural' => '@count text editors',
  ],
  constraints: [
    'RequiredConfigDependencies' => [
      'filter_format',
    ],
  ],
  config_export: [
    'format',
    'editor',
    'settings',
    'image_upload',
  ],
)]
class Editor extends ConfigEntityBase implements EditorInterface {

  /**
   * Machine name of the text format for this configured text editor.
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
  protected $settings = [];

  /**
   * The structured array of image upload settings.
   *
   * @var array
   */
  protected $image_upload = [];

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

    try {
      $plugin = $this->editorPluginManager()->createInstance($this->editor);
      $this->settings += $plugin->getDefaultSettings();
    }
    catch (PluginNotFoundException) {
      // When a Text Editor plugin has gone missing, still allow the Editor
      // config entity to be constructed. The only difference is that default
      // settings are not added.
    }
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
    return $this;
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
      $this->filterFormat = \Drupal::entityTypeManager()->getStorage('filter_format')->load($this->format);
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

  /**
   * Computes all valid choices for the "image_upload.scheme" setting.
   *
   * @see editor.schema.yml
   *
   * @return string[]
   *   All valid choices.
   *
   * @internal
   */
  public static function getValidStreamWrappers(): array {
    return array_keys(\Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE));
  }

}
