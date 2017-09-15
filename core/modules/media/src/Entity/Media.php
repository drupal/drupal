<?php

namespace Drupal\media\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceEntityConstraintsInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\user\UserInterface;

/**
 * Defines the media entity class.
 *
 * @todo Remove default/fallback entity form operation when #2006348 is done.
 * @see https://www.drupal.org/node/2006348.
 *
 * @ContentEntityType(
 *   id = "media",
 *   label = @Translation("Media"),
 *   label_singular = @Translation("media item"),
 *   label_plural = @Translation("media items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count media item",
 *     plural = "@count media items"
 *   ),
 *   bundle_label = @Translation("Media type"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\media\MediaAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\media\MediaForm",
 *       "add" = "Drupal\media\MediaForm",
 *       "edit" = "Drupal\media\MediaForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\media\MediaViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "media",
 *   data_table = "media_field_data",
 *   revision_table = "media_revision",
 *   revision_data_table = "media_field_revision",
 *   translatable = TRUE,
 *   show_revision_ui = TRUE,
 *   entity_keys = {
 *     "id" = "mid",
 *     "revision" = "vid",
 *     "bundle" = "bundle",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   bundle_entity_type = "media_type",
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer media",
 *   field_ui_base_route = "entity.media_type.edit_form",
 *   common_reference_target = TRUE,
 *   links = {
 *     "add-page" = "/media/add",
 *     "add-form" = "/media/add/{media_type}",
 *     "canonical" = "/media/{media}",
 *     "delete-form" = "/media/{media}/delete",
 *     "edit-form" = "/media/{media}/edit",
 *     "revision" = "/media/{media}/revisions/{media_revision}/view",
 *   }
 * )
 */
class Media extends EditorialContentEntityBase implements MediaInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $name = $this->get('name');

    if ($name->isEmpty()) {
      $media_source = $this->getSource();
      return $media_source->getMetadata($this, $media_source->getPluginDefinition()['default_name_metadata_attribute']);
    }
    else {
      return $name->value;
    }
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
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    return $this->set('uid', $account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    return $this->set('uid', $uid);
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
    $file_storage = \Drupal::service('entity_type.manager')->getStorage('file');
    $thumbnail_uri = $this->getThumbnailUri($from_queue);
    $existing = $file_storage->getQuery()
      ->condition('uri', $thumbnail_uri)
      ->execute();

    if ($existing) {
      $this->thumbnail->target_id = reset($existing);
    }
    else {
      /** @var \Drupal\file\FileInterface $file */
      $file = $file_storage->create(['uri' => $thumbnail_uri]);
      if ($owner = $this->getOwner()) {
        $file->setOwner($owner);
      }
      $file->setPermanent();
      $file->save();
      $this->thumbnail->target_id = $file->id();
    }

    // Set the thumbnail alt.
    $media_source = $this->getSource();
    $plugin_definition = $media_source->getPluginDefinition();
    if (!empty($plugin_definition['thumbnail_alt_metadata_attribute'])) {
      $this->thumbnail->alt = $media_source->getMetadata($this, $plugin_definition['thumbnail_alt_metadata_attribute']);
    }
    else {
      $this->thumbnail->alt = $this->t('Thumbnail', [], ['langcode' => $this->langcode->value]);
    }

    // Set the thumbnail title.
    if (!empty($plugin_definition['thumbnail_title_metadata_attribute'])) {
      $this->thumbnail->title = $media_source->getMetadata($this, $plugin_definition['thumbnail_title_metadata_attribute']);
    }
    else {
      $this->thumbnail->title = $this->label();
    }

    return $this;
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
      $default_thumbnail_filename = $this->getSource()->getPluginDefinition()['default_thumbnail_filename'];
      $thumbnail_uri = \Drupal::service('config.factory')->get('media.settings')->get('icon_base_uri') . '/' . $default_thumbnail_filename;
    }
    elseif ($thumbnails_queued && !$from_queue) {
      $thumbnail_uri = $this->get('thumbnail')->entity->getFileUri();
    }
    else {
      $thumbnail_uri = $this->getSource()->getMetadata($this, $this->getSource()->getPluginDefinition()['thumbnail_uri_metadata_attribute']);
    }

    return $thumbnail_uri;
  }

  /**
   * Determines if the source field value has changed.
   *
   * @return bool
   *   TRUE if the source field value changed, FALSE otherwise.
   *
   * @internal
   */
  protected function hasSourceFieldChanged() {
    $source_field_name = $this->getSource()->getConfiguration()['source_field'];
    $current_items = $this->get($source_field_name);
    return isset($this->original) && !$current_items->equals($this->original->get($source_field_name));
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

    $media_source = $this->getSource();
    foreach ($this->translations as $langcode => $data) {
      if ($this->hasTranslation($langcode)) {
        $translation = $this->getTranslation($langcode);
        // Try to set fields provided by the media source and mapped in
        // media type config.
        foreach ($translation->bundle->entity->getFieldMap() as $metadata_attribute_name => $entity_field_name) {
          // Only save value in entity field if empty. Do not overwrite existing
          // data.
          if ($translation->hasField($entity_field_name) && ($translation->get($entity_field_name)->isEmpty() || $translation->hasSourceFieldChanged())) {
            $translation->set($entity_field_name, $media_source->getMetadata($translation, $metadata_attribute_name));
          }
        }

        // Try to set a default name for this media item if no name is provided.
        if ($translation->get('name')->isEmpty()) {
          $translation->setName($translation->getName());
        }

        // Set thumbnail.
        if ($translation->shouldUpdateThumbnail()) {
          $translation->updateThumbnail();
        }
      }
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

    $is_new_revision = $this->isNewRevision();
    if (!$is_new_revision && isset($this->original) && empty($record->revision_log_message)) {
      // If we are updating an existing media item without adding a
      // new revision, we need to make sure $entity->revision_log_message is
      // reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log_message = $this->original->revision_log_message->value;
    }

    if ($is_new_revision) {
      $record->revision_created = self::getRequestTime();
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
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

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

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the author.'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getCurrentUserId')
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE)
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
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int[]
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function getRequestTime() {
    return \Drupal::time()->getRequestTime();
  }

}
