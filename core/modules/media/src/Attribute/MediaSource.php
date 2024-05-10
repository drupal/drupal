<?php

declare(strict_types=1);

namespace Drupal\media\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a MediaSource attribute.
 *
 * Media sources are responsible for implementing all the logic for dealing
 * with a particular type of media. They provide various universal and
 * type-specific metadata about media of the type they handle.
 *
 * Plugin namespace: Plugin\media\Source
 *
 * For a working example, see \Drupal\media\Plugin\media\Source\File.
 *
 * @see \Drupal\media\MediaSourceInterface
 * @see \Drupal\media\MediaSourceBase
 * @see \Drupal\media\MediaSourceManager
 * @see hook_media_source_info_alter()
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MediaSource extends Plugin {

  /**
   * Constructs a new MediaSource attribute.
   *
   * @param string $id
   *   The attribute class ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the media source.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) A brief description of the media source.
   * @param string[] $allowed_field_types
   *   (optional) The field types that can be used as a source field for this
   *   media source.
   * @param class-string[] $forms
   *   (optional) The classes used to define media source-specific forms. An
   *   array of form class names, keyed by ID. The ID represents the operation
   *   the form is used for, for example, 'media_library_add'.
   * @param string $default_thumbnail_filename
   *   (optional) A filename for the default thumbnail.
   *   The thumbnails are placed in the directory defined by the config setting
   *   'media.settings.icon_base_uri'. When using custom icons, make sure the
   *   module provides a hook_install() implementation to copy the custom icons
   *   to this directory. The media_install() function provides a clear example
   *   of how to do this.
   * @param string $thumbnail_uri_metadata_attribute
   *   (optional) The metadata attribute name to provide the thumbnail URI.
   * @param string $thumbnail_width_metadata_attribute
   *   (optional) The metadata attribute name to provide the thumbnail width.
   * @param string $thumbnail_height_metadata_attribute
   *   (optional) The metadata attribute name to provide the thumbnail height.
   * @param string|null $thumbnail_alt_metadata_attribute
   *   (optional) The metadata attribute name to provide the thumbnail alt.
   *   "Thumbnail" will be used if the attribute name is not provided.
   * @param string|null $thumbnail_title_metadata_attribute
   *   (optional) The metadata attribute name to provide the thumbnail title.
   *   The name of the media item will be used if the attribute name is not
   *   provided.
   * @param string $default_name_metadata_attribute
   *   (optional) The metadata attribute name to provide the default name.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly array $allowed_field_types = [],
    public readonly array $forms = [],
    public readonly string $default_thumbnail_filename = 'generic.png',
    public readonly string $thumbnail_uri_metadata_attribute = 'thumbnail_uri',
    public readonly string $thumbnail_width_metadata_attribute = 'thumbnail_width',
    public readonly string $thumbnail_height_metadata_attribute = 'thumbnail_height',
    public readonly ?string $thumbnail_alt_metadata_attribute = NULL,
    public readonly ?string $thumbnail_title_metadata_attribute = NULL,
    public readonly string $default_name_metadata_attribute = 'default_name',
    public readonly ?string $deriver = NULL,
  ) {}

}
