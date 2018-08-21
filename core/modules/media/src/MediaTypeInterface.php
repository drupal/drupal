<?php

namespace Drupal\media;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a media type entity.
 *
 * Media types are bundles for media items. They are used to group media with
 * the same semantics. Media types are not about where media comes from. They
 * are about the semantics that media has in the context of a given Drupal site.
 *
 * Media sources, on the other hand, are aware where media comes from and know
 * how to represent and handle it in Drupal's context. They are aware of the low
 * level details, while the media types don't care about them at all. That said,
 * media types can not exist without media sources.
 *
 * Consider the following examples:
 * - oEmbed media source which can represent any oEmbed resource. Media types
 *   that could be used with this source are "Videos", "Charts", "Music", etc.
 *   All of them are retrieved using the same protocol, but they represent very
 *   different things.
 * - Media sources that represent files could be used with media types like
 *   "Invoices", "Subtitles", "Meeting notes", etc. They are all files stored on
 *   some kind of storage, but their meaning and uses in a Drupal site are
 *   different.
 *
 * @see \Drupal\media\MediaSourceInterface
 */
interface MediaTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * Returns whether thumbnail downloads are queued.
   *
   * When using remote media sources, the thumbnail generation could be a slow
   * process. Using a queue allows for this process to be handled in the
   * background.
   *
   * @return bool
   *   TRUE if thumbnails are queued for download later, FALSE if they should be
   *   downloaded now.
   */
  public function thumbnailDownloadsAreQueued();

  /**
   * Sets a flag to indicate that thumbnails should be downloaded via a queue.
   *
   * @param bool $queue_thumbnail_downloads
   *   The queue downloads flag.
   *
   * @return $this
   */
  public function setQueueThumbnailDownloadsStatus($queue_thumbnail_downloads);

  /**
   * Returns the media source plugin.
   *
   * @return \Drupal\media\MediaSourceInterface
   *   The media source.
   */
  public function getSource();

  /**
   * Sets whether new revisions should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if media items of this type should create new revisions by default.
   *
   * @return $this
   */
  public function setNewRevision($new_revision);

  /**
   * Returns the metadata field map.
   *
   * Field mapping allows site builders to map media item-related metadata to
   * entity fields. This information will be used when saving a given media item
   * and if metadata values will be available they are going to be automatically
   * copied to the corresponding entity fields.
   *
   * @return array
   *   Field mapping array provided by media source with metadata attribute
   *   names as keys and entity field names as values.
   */
  public function getFieldMap();

  /**
   * Sets the metadata field map.
   *
   * @param array $map
   *   Field mapping array with metadata attribute names as keys and entity
   *   field names as values.
   *
   * @return $this
   */
  public function setFieldMap(array $map);

}
