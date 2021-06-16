<?php

namespace Drupal\media_library\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "drupalmedialibrary" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalmedialibrary",
 *   label = @Translation("Embed media from the Media Library"),
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class DrupalMediaLibrary extends CKEditorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The media type entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $mediaTypeStorage;

  /**
   * Constructs a new DrupalMediaLibrary plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleExtensionList $extension_list_module, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleExtensionList = $extension_list_module;
    $this->mediaTypeStorage = $entity_type_manager->getStorage('media_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.module'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [
      'drupalmedia',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'editor/drupal.editor.dialog',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->moduleExtensionList->getPath('media_library') . '/js/plugins/drupalmedialibrary/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    // If the editor has not been saved yet, we may not be able to create a
    // coherent MediaLibraryState object, which is needed in order to generate
    // the required configuration. But, if we're creating a new editor, we don't
    // need to do that anyway, so just return an empty array.
    if ($editor->isNew()) {
      return [];
    }

    $media_type_ids = $this->mediaTypeStorage->getQuery()->execute();
    if ($editor->hasAssociatedFilterFormat()) {
      if ($media_embed_filter = $editor->getFilterFormat()->filters()->get('media_embed')) {
        // Optionally limit the allowed media types based on the MediaEmbed
        // setting. If the setting is empty, do not limit the options.
        if (!empty($media_embed_filter->settings['allowed_media_types'])) {
          $media_type_ids = array_intersect_key($media_type_ids, $media_embed_filter->settings['allowed_media_types']);
        }
      }
    }

    if (in_array('image', $media_type_ids, TRUE)) {
      // Due to a bug where the active item styling and the focus styling
      // create the visual appearance of two active items, we'll move
      // the 'image' media type to first position, so that the focused item and
      // the active item are the same.
      // This workaround can be removed once this issue is fixed:
      // @see https://www.drupal.org/project/drupal/issues/3073799
      array_unshift($media_type_ids, 'image');
      $media_type_ids = array_unique($media_type_ids);
    }

    $state = MediaLibraryState::create(
      'media_library.opener.editor',
      $media_type_ids,
      reset($media_type_ids),
      1,
      ['filter_format_id' => $editor->getFilterFormat()->id()]
    );

    return [
      'DrupalMediaLibrary_url' => Url::fromRoute('media_library.ui')
        ->setOption('query', $state->all())
        ->toString(TRUE)
        ->getGeneratedUrl(),
      'DrupalMediaLibrary_dialogOptions' => MediaLibraryUiBuilder::dialogOptions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'DrupalMediaLibrary' => [
        'label' => $this->t('Insert from Media Library'),
        'image' => $this->moduleExtensionList->getPath('media_library') . '/js/plugins/drupalmedialibrary/icons/drupalmedialibrary.png',
      ],
    ];
  }

}
