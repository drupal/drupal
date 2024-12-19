<?php

namespace Drupal\media\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider;
use Drupal\Core\Entity\Form\RevisionRevertForm;
use Drupal\Core\Entity\Form\RevisionDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaAccessControlHandler;
use Drupal\media\MediaForm;
use Drupal\media\MediaInterface;
use Drupal\media\MediaListBuilder;
use Drupal\media\MediaSourceEntityConstraintsInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\media\MediaStorage;
use Drupal\media\MediaViewsData;
use Drupal\media\Routing\MediaRouteProvider;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the media entity class.
 *
 * @todo Remove default/fallback entity form operation when #2006348 is done.
 * @see https://www.drupal.org/node/2006348.
 */
#[ContentEntityType(
  id: 'media',
  label: new TranslatableMarkup('Media'),
  label_singular: new TranslatableMarkup('media item'),
  label_plural: new TranslatableMarkup('media items'),
  entity_keys: [
    'id' => 'mid',
    'revision' => 'vid',
    'bundle' => 'bundle',
    'label' => 'name',
    'langcode' => 'langcode',
    'uuid' => 'uuid',
    'published' => 'status',
    'owner' => 'uid',
  ],
  handlers: [
    'storage' => MediaStorage::class,
    'view_builder' => EntityViewBuilder::class,
    'list_builder' => MediaListBuilder::class,
    'access' => MediaAccessControlHandler::class,
    'form' => [
      'default' => MediaForm::class,
      'add' => MediaForm::class,
      'edit' => MediaForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
      'revision-delete' => RevisionDeleteForm::class,
      'revision-revert' => RevisionRevertForm::class,
    ],
    'views_data' => MediaViewsData::class,
    'route_provider' => [
      'html' => MediaRouteProvider::class,
      'revision' => RevisionHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-page' => '/media/add',
    'add-form' => '/media/add/{media_type}',
    'canonical' => '/media/{media}/edit',
    'collection' => '/admin/content/media',
    'delete-form' => '/media/{media}/delete',
    'delete-multiple-form' => '/media/delete',
    'edit-form' => '/media/{media}/edit',
    'revision' => '/media/{media}/revisions/{media_revision}/view',
    'revision-delete-form' => '/media/{media}/revision/{media_revision}/delete',
    'revision-revert-form' => '/media/{media}/revision/{media_revision}/revert',
    'version-history' => '/media/{media}/revisions',
  ],
  admin_permission: 'administer media',
  permission_granularity: 'bundle',
  bundle_entity_type: 'media_type',
  bundle_label: new TranslatableMarkup('Media type'),
  base_table: 'media',
  data_table: 'media_field_data',
  revision_table: 'media_revision',
  revision_data_table: 'media_field_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  label_count: [
    'singular' => '@count media item',
    'plural' => '@count media items',
  ],
  field_ui_base_route: 'entity.media_type.edit_form',
  common_reference_target: TRUE,
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
  )]
class Media extends EditorialContentEntityBase implements MediaInterface {

  use EntityOwnerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $name = $this->getEntityKey('label');

    if (empty($name)) {
      $media_source = $this->getSource();
      return $media_source->getMetadata($this, $media_source->getPluginDefinition()['default_name_metadata_attribute']);
    }

    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    return $this->set('name', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    return $this->set('created', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->bundle->entity->getSource();
  }

  /**
   * Update the thumbnail for the media item.
   *
   * @param bool $from_queue
   *   Specifies whether the thumbnail update is triggered from the queue.
   *
   * @return \Drupal\media\MediaInterface
   *   The updated media item.
   *
   * @internal
   *
   * @todo There has been some disagreement about how to handle updates to
   *   thumbnails. We need to decide on what the API will be for this.
   *   https://www.drupal.org/node/2878119
   */
  protected function updateThumbnail($from_queue = FALSE) {
    $this->thumbnail->target_id = $this->loadThumbnail($this->getThumbnailUri($from_queue))->id();
    $this->thumbnail->width = $this->getThumbnailWidth($from_queue);
    $this->thumbnail->height = $this->getThumbnailHeight($from_queue);

    // Set the thumbnail alt.
    $media_source = $this->getSource();
    $plugin_definition = $media_source->getPluginDefinition();

    $this->thumbnail->alt = '';
    if (!empty($plugin_definition['thumbnail_alt_metadata_attribute'])) {
      $this->thumbnail->alt = $media_source->getMetadata($this, $plugin_definition['thumbnail_alt_metadata_attribute']);
    }

    return $this;
  }

  /**
   * Loads the file entity for the thumbnail.
   *
   * If the file entity does not exist, it will be created.
   *
   * @param string $thumbnail_uri
   *   (optional) The URI of the thumbnail, used to load or create the file
   *   entity. If omitted, the default thumbnail URI will be used.
   *
   * @return \Drupal\file\FileInterface
   *   The thumbnail file entity.
   */
  protected function loadThumbnail($thumbnail_uri = NULL) {
    $values = [
      'uri' => $thumbnail_uri ?: $this->getDefaultThumbnailUri(),
    ];

    $file_storage = $this->entityTypeManager()->getStorage('file');

    $existing = $file_storage->loadByProperties($values);
    if ($existing) {
      $file = reset($existing);
    }
    else {
      /** @var \Drupal\file\FileInterface $file */
      $file = $file_storage->create($values);
      if ($owner = $this->getOwner()) {
        $file->setOwner($owner);
      }
      $file->setPermanent();
      $file->save();
    }
    return $file;
  }

  /**
   * Returns the URI of the default thumbnail.
   *
   * @return string
   *   The default thumbnail URI.
   */
  protected function getDefaultThumbnailUri() {
    $default_thumbnail_filename = $this->getSource()->getPluginDefinition()['default_thumbnail_filename'];
    return \Drupal::config('media.settings')->get('icon_base_uri') . '/' . $default_thumbnail_filename;
  }

  /**
   * Updates the queued thumbnail for the media item.
   *
   * @return \Drupal\media\MediaInterface
   *   The updated media item.
   *
   * @internal
   *
   * @todo If the need arises in contrib, consider making this a public API,
   *   by adding an interface that extends MediaInterface.
   */
  public function updateQueuedThumbnail() {
    $this->updateThumbnail(TRUE);
    return $this;
  }

  /**
   * Gets the URI for the thumbnail of a media item.
   *
   * If thumbnail fetching is queued, new media items will use the default
   * thumbnail, and existing media items will use the current thumbnail, until
   * the queue is processed and the updated thumbnail has been fetched.
   * Otherwise, the new thumbnail will be fetched immediately.
   *
   * @param bool $from_queue
   *   Specifies whether the thumbnail is being fetched from the queue.
   *
   * @return string
   *   The file URI for the thumbnail of the media item.
   *
   * @internal
   */
  protected function getThumbnailUri($from_queue) {
    $thumbnails_queued = $this->bundle->entity->thumbnailDownloadsAreQueued();
    if ($thumbnails_queued && $this->isNew()) {
      return $this->getDefaultThumbnailUri();
    }
    elseif ($thumbnails_queued && !$from_queue) {
      return $this->get('thumbnail')->entity->getFileUri();
    }

    $source = $this->getSource();
    return $source->getMetadata($this, $source->getPluginDefinition()['thumbnail_uri_metadata_attribute']);
  }

  /**
   * Gets the width of the thumbnail of a media item.
   *
   * @param bool $from_queue
   *   Specifies whether the thumbnail is being fetched from the queue.
   *
   * @return int|null
   *   The width of the thumbnail of the media item or NULL if the media is new
   *   and the thumbnails are set to be downloaded in a queue.
   *
   * @internal
   */
  protected function getThumbnailWidth(bool $from_queue): ?int {
    $thumbnails_queued = $this->bundle->entity->thumbnailDownloadsAreQueued();
    if ($thumbnails_queued && $this->isNew()) {
      return NULL;
    }
    elseif ($thumbnails_queued && !$from_queue) {
      return $this->get('thumbnail')->width;
    }

    $source = $this->getSource();
    return $source->getMetadata($this, $source->getPluginDefinition()['thumbnail_width_metadata_attribute']);
  }

  /**
   * Gets the height of the thumbnail of a media item.
   *
   * @param bool $from_queue
   *   Specifies whether the thumbnail is being fetched from the queue.
   *
   * @return int|null
   *   The height of the thumbnail of the media item or NULL if the media is new
   *   and the thumbnails are set to be downloaded in a queue.
   *
   * @internal
   */
  protected function getThumbnailHeight(bool $from_queue): ?int {
    $thumbnails_queued = $this->bundle->entity->thumbnailDownloadsAreQueued();
    if ($thumbnails_queued && $this->isNew()) {
      return NULL;
    }
    elseif ($thumbnails_queued && !$from_queue) {
      return $this->get('thumbnail')->height;
    }

    $source = $this->getSource();
    return $source->getMetadata($this, $source->getPluginDefinition()['thumbnail_height_metadata_attribute']);
  }

  /**
   * Determines if the source field value has changed.
   *
   * The comparison uses MediaSourceInterface::getSourceFieldValue() to ensure
   * that the correct property from the source field is used.
   *
   * @return bool
   *   TRUE if the source field value changed, FALSE otherwise.
   *
   * @see \Drupal\media\MediaSourceInterface::getSourceFieldValue()
   *
   * @internal
   */
  protected function hasSourceFieldChanged() {
    $source = $this->getSource();
    return $this->getOriginal() && $source->getSourceFieldValue($this) !== $source->getSourceFieldValue($this->getOriginal());
  }

  /**
   * Determines if the thumbnail should be updated for a media item.
   *
   * @param bool $is_new
   *   Specifies whether the media item is new.
   *
   * @return bool
   *   TRUE if the thumbnail should be updated, FALSE otherwise.
   */
  protected function shouldUpdateThumbnail($is_new = FALSE) {
    // Update thumbnail if we don't have a thumbnail yet or when the source
    // field value changes.
    return !$this->get('thumbnail')->entity || $is_new || $this->hasSourceFieldChanged();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!$this->getOwner()) {
      $this->setOwnerId(0);
    }

    // If no thumbnail has been explicitly set, use the default thumbnail.
    if ($this->get('thumbnail')->isEmpty()) {
      $this->thumbnail->target_id = $this->loadThumbnail()->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    $is_new = !$update;
    foreach ($this->translations as $langcode => $data) {
      if ($this->hasTranslation($langcode)) {
        $translation = $this->getTranslation($langcode);
        if ($translation->bundle->entity->thumbnailDownloadsAreQueued() && $translation->shouldUpdateThumbnail($is_new)) {
          \Drupal::queue('media_entity_thumbnail')->createItem(['id' => $translation->id()]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && $this->getOriginal() && empty($record->revision_log_message)) {
      // If we are updating an existing media item without adding a
      // new revision, we need to make sure $entity->revision_log_message is
      // reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $this->setRevisionLogMessage($this->getOriginal()->getRevisionLogMessage());
    }
  }

  /**
   * Sets the media entity's field values from the source's metadata.
   *
   * Fetching the metadata could be slow (e.g., if requesting it from a remote
   * API), so this is called by \Drupal\media\MediaStorage::save() prior to it
   * beginning the database transaction, whereas static::preSave() executes
   * after the transaction has already started.
   *
   * @internal
   *   Expose this as an API in
   *   https://www.drupal.org/project/drupal/issues/2992426.
   */
  public function prepareSave() {
    // @todo If the source plugin talks to a remote API (e.g. oEmbed), this code
    // might be performing a fair number of HTTP requests. This is dangerously
    // brittle and should probably be handled by a queue, to avoid doing HTTP
    // operations during entity save. See
    // https://www.drupal.org/project/drupal/issues/2976875 for more.

    // In order for metadata to be mapped correctly, the original entity must be
    // set. However, that is only set once parent::save() is called, so work
    // around that by setting it here.
    if (!$this->getOriginal() && $id = $this->id()) {
      $this->setOriginal($this->entityTypeManager()
        ->getStorage('media')
        ->loadUnchanged($id)
      );
    }

    $media_source = $this->getSource();
    foreach ($this->translations as $langcode => $data) {
      if ($this->hasTranslation($langcode)) {
        $translation = $this->getTranslation($langcode);
        // Try to set fields provided by the media source and mapped in
        // media type config.
        foreach ($translation->bundle->entity->getFieldMap() as $metadata_attribute_name => $entity_field_name) {
          // Only save value in the entity if the field is empty or if the
          // source field changed.
          if ($translation->hasField($entity_field_name) && ($translation->get($entity_field_name)->isEmpty() || $translation->hasSourceFieldChanged())) {
            $translation->set($entity_field_name, $media_source->getMetadata($translation, $metadata_attribute_name));
          }
        }

        // Try to set a default name for this media item if no name is provided.
        if ($translation->get('name')->isEmpty()) {
          $translation->setName($translation->getName());
        }

        // Set thumbnail.
        if ($translation->shouldUpdateThumbnail($this->isNew())) {
          $translation->updateThumbnail();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $media_source = $this->getSource();

    if ($media_source instanceof MediaSourceEntityConstraintsInterface) {
      $entity_constraints = $media_source->getEntityConstraints();
      $this->getTypedData()->getDataDefinition()->setConstraints($entity_constraints);
    }

    if ($media_source instanceof MediaSourceFieldConstraintsInterface) {
      $source_field_name = $media_source->getConfiguration()['source_field'];
      $source_field_constraints = $media_source->getSourceFieldConstraints();
      $this->get($source_field_name)->getDataDefinition()->setConstraints($source_field_constraints);
    }

    return parent::validate();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['thumbnail'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Thumbnail'))
      ->setDescription(t('The thumbnail of the media item.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'image',
        'weight' => 5,
        'label' => 'hidden',
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE);

    $fields['uid']
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the author.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 100,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time the media item was created.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getRequestTime')
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the media item was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function getRequestTime() {
    return \Drupal::time()->getRequestTime();
  }

}
