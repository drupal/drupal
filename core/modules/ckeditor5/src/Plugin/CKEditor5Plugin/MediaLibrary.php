<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\EditorInterface;
use Drupal\media_library\MediaLibraryState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 Media Library plugin.
 *
 * Provides media library support and options for the CKEditor 5 build.
 *
 * @internal
 *   Plugin classes are internal.
 */
class MediaLibrary extends CKEditor5PluginDefault implements ContainerFactoryPluginInterface {

  /**
   * The media type entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $mediaTypeStorage;

  /**
   * MediaLibrary constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaTypeStorage = $entity_type_manager->getStorage('media_type');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $media_type_ids = $this->mediaTypeStorage->getQuery()->execute();

    if ($editor->hasAssociatedFilterFormat()) {
      $media_embed_filter = $editor->getFilterFormat()->filters()->get('media_embed');
      // Optionally limit the allowed media types based on the MediaEmbed
      // setting. If the setting is empty, do not limit the options.
      if (!empty($media_embed_filter->settings['allowed_media_types'])) {
        $media_type_ids = array_intersect_key($media_type_ids, $media_embed_filter->settings['allowed_media_types']);
      }
    }
    if (in_array('image', $media_type_ids, TRUE)) {
      // Move image to first position.
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
      ['filter_format_id' => $editor->getFilterFormat()->id()],
    );

    $library_url = Url::fromRoute('media_library.ui')
      ->setOption('query', $state->all())
      ->toString(TRUE)
      ->getGeneratedUrl();

    $dynamic_plugin_config = $static_plugin_config;
    $dynamic_plugin_config['drupalMedia']['libraryURL'] = $library_url;
    return $dynamic_plugin_config;
  }

}
