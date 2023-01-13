<?php

namespace Drupal\media\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a media source plugin annotation object.
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
 *
 * @Annotation
 */
class MediaSource extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the media source.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the media source.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The field types that can be used as a source field for this media source.
   *
   * @var string[]
   */
  public $allowed_field_types = [];

  /**
   * The classes used to define media source-specific forms.
   *
   * An array of form class names, keyed by ID. The ID represents the operation
   * the form is used for.
   *
   * @var string[]
   */
  public $forms = [];

  /**
   * A filename for the default thumbnail.
   *
   * The thumbnails are placed in the directory defined by the config setting
   * 'media.settings.icon_base_uri'. When using custom icons, make sure the
   * module provides a hook_install() implementation to copy the custom icons
   * to this directory. The media_install() function provides a clear example
   * of how to do this.
   *
   * @var string
   *
   * @see media_install()
   */
  public $default_thumbnail_filename = 'generic.png';

  /**
   * The metadata attribute name to provide the thumbnail URI.
   *
   * @var string
   */
  public $thumbnail_uri_metadata_attribute = 'thumbnail_uri';

  /**
   * The metadata attribute name to provide the thumbnail width.
   *
   * @var string
   */
  public $thumbnail_width_metadata_attribute = 'thumbnail_width';

  /**
   * The metadata attribute name to provide the thumbnail height.
   *
   * @var string
   */
  public $thumbnail_height_metadata_attribute = 'thumbnail_height';

  /**
   * (optional) The metadata attribute name to provide the thumbnail alt.
   *
   * "Thumbnail" will be used if the attribute name is not provided.
   *
   * @var string|null
   */
  public $thumbnail_alt_metadata_attribute;

  /**
   * (optional) The metadata attribute name to provide the thumbnail title.
   *
   * The name of the media item will be used if the attribute name is not
   * provided.
   *
   * @var string|null
   */
  public $thumbnail_title_metadata_attribute;

  /**
   * The metadata attribute name to provide the default name.
   *
   * @var string
   */
  public $default_name_metadata_attribute = 'default_name';

}
