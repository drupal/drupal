<?php

namespace Drupal\media;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for media source plugins.
 *
 * Media sources provide the critical link between media items in Drupal and the
 * actual media itself, which typically exists independently of Drupal. Each
 * media source works with a certain kind of media. For example, local files and
 * YouTube videos can both be catalogued in a similar way as media items, but
 * they need very different handling to actually display them.
 *
 * Each media type needs exactly one source. A single source can be used on many
 * media types.
 *
 * Examples of possible sources are:
 * - File: handles local files,
 * - Image: handles local images,
 * - oEmbed: handles resources that are exposed through the oEmbed standard,
 * - YouTube: handles YouTube videos,
 * - SoundCloud: handles SoundCloud audio,
 * - Instagram: handles Instagram posts,
 * - Twitter: handles tweets,
 * - ...
 *
 * Their responsibilities are:
 * - Defining how media is represented (stored). Media sources are not
 *   responsible for actually storing the media. They only define how it is
 *   represented on a media item (usually using some kind of a field).
 * - Providing thumbnails. Media sources that are responsible for remote
 *   media will generally fetch the image from a third-party API and make
 *   it available for the local usage. Media sources that represent local
 *   media (such as images) will usually use some locally provided image.
 *   Media sources should fall back to a pre-defined default thumbnail if
 *   everything else fails.
 * - Validating a media item before it is saved. The entity constraint system
 *   will be used to ensure the valid structure of the media item.
 *   For example, media sources that represent remote media might check the
 *   URL or other identifier, while sources that represent local files might
 *   check the MIME type of the file.
 * - Providing a default name for a media item. This will save users from
 *   manually entering the name when it can be reliably set automatically.
 *   Media sources for local files will generally use the filename, while media
 *   sources for remote resources might obtain a title attribute through a
 *   third-party API. The name can always be overridden by the user.
 * - Providing metadata specific to the given media type. For example, remote
 *   media sources generally get information available through a
 *   third-party API and make it available to Drupal, while local media sources
 *   can expose things such as EXIF or ID3.
 * - Mapping metadata to the media item. Metadata that a media source exposes
 *   can automatically be mapped to the fields on the media item. Media
 *   sources will be able to define how this is done.
 *
 * @see \Drupal\media\Annotation\MediaSource
 * @see \Drupal\media\MediaSourceBase
 * @see \Drupal\media\MediaSourceManager
 * @see \Drupal\media\MediaTypeInterface
 * @see \Drupal\media\MediaSourceEntityConstraintsInterface
 * @see \Drupal\media\MediaSourceFieldConstraintsInterface
 * @see plugin_api
 */
interface MediaSourceInterface extends PluginInspectionInterface, ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Default empty value for metadata fields.
   */
  const METADATA_FIELD_EMPTY = '_none';

  /**
   * Gets a list of metadata attributes provided by this plugin.
   *
   * Most media sources have associated metadata, describing attributes
   * such as:
   * - dimensions
   * - duration
   * - encoding
   * - date
   * - location
   * - permalink
   * - licensing information
   * - ...
   *
   * This method should list all metadata attributes that a media source MAY
   * offer. In other words: it is possible that a particular media item does
   * not contain a certain attribute. For example: an oEmbed media source can
   * contain both video and images. Images don't have a duration, but
   * videos do.
   *
   * (The term 'attributes' was chosen because it cannot be confused
   * with 'fields' and 'properties', both of which are concepts in Drupal's
   * Entity Field API.)
   *
   * @return array
   *   Associative array with:
   *   - keys: metadata attribute names
   *   - values: human-readable labels for those attribute names
   */
  public function getMetadataAttributes();

  /**
   * Gets the value for a metadata attribute for a given media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   * @param string $attribute_name
   *   Name of the attribute to fetch.
   *
   * @return mixed|null
   *   Metadata attribute value or NULL if unavailable.
   */
  public function getMetadata(MediaInterface $media, $attribute_name);

  /**
   * Get the source field definition for a media type.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   A media type.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The source field definition, or NULL if it doesn't exist or has not been
   *   configured yet.
   */
  public function getSourceFieldDefinition(MediaTypeInterface $type);

  /**
   * Creates the source field definition for a type.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type.
   *
   * @return \Drupal\field\FieldConfigInterface
   *   The unsaved field definition. The field storage definition, if new,
   *   should also be unsaved.
   */
  public function createSourceField(MediaTypeInterface $type);

  /**
   * Prepares the media type fields for this source in the view display.
   *
   * This method should normally call
   * \Drupal\Core\Entity\Display\EntityDisplayInterface::setComponent() or
   * \Drupal\Core\Entity\Display\EntityDisplayInterface::removeComponent() to
   * configure the media type fields in the view display.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type which is using this source.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display which should be prepared.
   *
   * @see \Drupal\Core\Entity\Display\EntityDisplayInterface::setComponent()
   * @see \Drupal\Core\Entity\Display\EntityDisplayInterface::removeComponent()
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display);

  /**
   * Prepares the media type fields for this source in the form display.
   *
   * This method should normally call
   * \Drupal\Core\Entity\Display\EntityDisplayInterface::setComponent() or
   * \Drupal\Core\Entity\Display\EntityDisplayInterface::removeComponent() to
   * configure the media type fields in the form display.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type which is using this source.
   * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display
   *   The display which should be prepared.
   *
   * @see \Drupal\Core\Entity\Display\EntityDisplayInterface::setComponent()
   * @see \Drupal\Core\Entity\Display\EntityDisplayInterface::removeComponent()
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display);

  /**
   * Get the primary value stored in the source field.
   *
   * @param MediaInterface $media
   *   A media item.
   *
   * @return mixed
   *   The source value, or NULL if the media item's source field is empty.
   *
   * @throws \RuntimeException
   *   If the source field for the media source is not defined.
   */
  public function getSourceFieldValue(MediaInterface $media);

}
