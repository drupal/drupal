<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Image entity media source.
 *
 * @see \Drupal\Core\Image\ImageInterface
 *
 * @MediaSource(
 *   id = "image",
 *   label = @Translation("Image"),
 *   description = @Translation("Use local images for reusable media."),
 *   allowed_field_types = {"image"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 *   thumbnail_alt_metadata_attribute = "thumbnail_alt_value"
 * )
 */
class Image extends File {

  /**
   * Key for "image width" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_WIDTH = 'width';

  /**
   * Key for "image height" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_HEIGHT = 'height';

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, ImageFactory $image_factory, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);

    $this->imageFactory = $image_factory;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('image.factory'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = parent::getMetadataAttributes();

    $attributes += [
      static::METADATA_ATTRIBUTE_WIDTH => $this->t('Width'),
      static::METADATA_ATTRIBUTE_HEIGHT => $this->t('Height'),
    ];

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    // Get the file and image data.
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($this->configuration['source_field'])->entity;
    // If the source field is not required, it may be empty.
    if (!$file) {
      return parent::getMetadata($media, $name);
    }

    $uri = $file->getFileUri();
    $image = $this->imageFactory->get($uri);
    switch ($name) {
      case static::METADATA_ATTRIBUTE_WIDTH:
        return $image->getWidth() ?: NULL;

      case static::METADATA_ATTRIBUTE_HEIGHT:
        return $image->getHeight() ?: NULL;

      case 'thumbnail_uri':
        return $uri;

      case 'thumbnail_alt_value':
        return $media->get($this->configuration['source_field'])->alt ?: parent::getMetadata($media, $name);
    }

    return parent::getMetadata($media, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = parent::createSourceField($type);

    // Reset the field to its default settings so that we don't inherit the
    // settings from the parent class' source field.
    $settings = $this->fieldTypeManager->getDefaultFieldSettings($field->getType());

    return $field->set('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    parent::prepareViewDisplay($type, $display);

    // Use the `large` image style and do not link the image to anything.
    // This will prevent the out-of-the-box configuration from outputting very
    // large raw images. If the `large` image style has been deleted, do not
    // set an image style.
    $field_name = $this->getSourceFieldDefinition($type)->getName();
    $component = $display->getComponent($field_name);
    $component['settings']['image_link'] = '';
    $component['settings']['image_style'] = '';
    if ($this->entityTypeManager->getStorage('image_style')->load('large')) {
      $component['settings']['image_style'] = 'large';
    }
    $display->setComponent($field_name, $component);
  }

}
