<?php

namespace Drupal\media\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\media\MediaTypeInterface;

/**
 * Defines the Media type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "media_type",
 *   label = @Translation("Media type"),
 *   label_collection = @Translation("Media types"),
 *   label_singular = @Translation("media type"),
 *   label_plural = @Translation("media types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count media type",
 *     plural = "@count media types"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\media\MediaTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\media\MediaTypeForm",
 *       "edit" = "Drupal\media\MediaTypeForm",
 *       "delete" = "Drupal\media\Form\MediaTypeDeleteConfirmForm"
 *     },
 *     "list_builder" = "Drupal\media\MediaTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer media types",
 *   config_prefix = "type",
 *   bundle_of = "media",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "source",
 *     "queue_thumbnail_downloads",
 *     "new_revision",
 *     "source_configuration",
 *     "field_map",
 *     "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/media/add",
 *     "edit-form" = "/admin/structure/media/manage/{media_type}",
 *     "delete-form" = "/admin/structure/media/manage/{media_type}/delete",
 *     "collection" = "/admin/structure/media",
 *   },
 * )
 */
class MediaType extends ConfigEntityBundleBase implements MediaTypeInterface, EntityWithPluginCollectionInterface {

  /**
   * The machine name of this media type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the media type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this media type.
   *
   * @var string
   */
  protected $description;

  /**
   * The media source ID.
   *
   * @var string
   */
  protected $source;

  /**
   * Whether media items should be published by default.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Whether thumbnail downloads are queued.
   *
   * @var bool
   *
   * @see \Drupal\media\MediaTypeInterface::thumbnailDownloadsAreQueued()
   */
  protected $queue_thumbnail_downloads = FALSE;

  /**
   * Default value of the 'Create new revision' checkbox of this media type.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

  /**
   * The media source configuration.
   *
   * A media source can provide a configuration form with source plugin-specific
   * configuration settings, which must at least include a source_field element
   * containing a the name of the source field for the media type. The source
   * configuration is defined by, and used to load, the source plugin. See
   * \Drupal\media\MediaTypeInterface for an explanation of media sources.
   *
   * @var array
   *
   * @see \Drupal\media\MediaTypeInterface::getSource()
   */
  protected $source_configuration = [];

  /**
   * Lazy collection for the media source.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $sourcePluginCollection;

  /**
   * The metadata field map.
   *
   * @var array
   *
   * @see \Drupal\media\MediaTypeInterface::getFieldMap()
   */
  protected $field_map = [];

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'source_configuration' => $this->sourcePluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnailDownloadsAreQueued() {
    return $this->queue_thumbnail_downloads;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueThumbnailDownloadsStatus($queue_thumbnail_downloads) {
    return $this->set('queue_thumbnail_downloads', $queue_thumbnail_downloads);
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->sourcePluginCollection()->get($this->source);
  }

  /**
   * Returns media source lazy plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection|null
   *   The tag plugin collection or NULL if the plugin ID was not set yet.
   */
  protected function sourcePluginCollection() {
    if (!$this->sourcePluginCollection && $this->source) {
      $this->sourcePluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.media.source'), $this->source, $this->source_configuration);
    }
    return $this->sourcePluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    return $this->set('new_revision', $new_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMap() {
    return $this->field_map;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldMap(array $map) {
    return $this->set('field_map', $map);
  }

}
