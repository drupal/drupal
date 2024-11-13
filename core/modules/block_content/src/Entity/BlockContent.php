<?php

namespace Drupal\block_content\Entity;

use Drupal\block_content\BlockContentAccessControlHandler;
use Drupal\block_content\BlockContentForm;
use Drupal\block_content\BlockContentListBuilder;
use Drupal\block_content\BlockContentStorageSchema;
use Drupal\block_content\BlockContentTranslationHandler;
use Drupal\block_content\BlockContentViewBuilder;
use Drupal\block_content\BlockContentViewsData;
use Drupal\block_content\Form\BlockContentDeleteForm;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider;
use Drupal\Core\Entity\Form\RevisionRevertForm;
use Drupal\Core\Entity\Form\RevisionDeleteForm;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\block_content\Access\RefinableDependentAccessTrait;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\block_content\BlockContentInterface;

/**
 * Defines the content block entity class.
 *
 * Note that render caching of block_content entities is disabled because they
 * are always rendered as blocks, and blocks already have their own render
 * caching.
 * See https://www.drupal.org/node/2284917#comment-9132521 for more information.
 */
#[ContentEntityType(
  id: 'block_content',
  label: new TranslatableMarkup('Content block'),
  label_collection: new TranslatableMarkup('Content blocks'),
  label_singular: new TranslatableMarkup('content block'),
  label_plural: new TranslatableMarkup('content blocks'),
  render_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'revision' => 'revision_id',
    'bundle' => 'type',
    'label' => 'info',
    'langcode' => 'langcode',
    'uuid' => 'uuid',
    'published' => 'status',
  ],
  handlers: [
    'storage' => SqlContentEntityStorage::class,
    'storage_schema' => BlockContentStorageSchema::class,
    'access' => BlockContentAccessControlHandler::class,
    'list_builder' => BlockContentListBuilder::class,
    'view_builder' => BlockContentViewBuilder::class,
    'views_data' => BlockContentViewsData::class,
    'form' => [
      'add' => BlockContentForm::class,
      'edit' => BlockContentForm::class,
      'delete' => BlockContentDeleteForm::class,
      'default' => BlockContentForm::class,
      'revision-delete' => RevisionDeleteForm::class,
      'revision-revert' => RevisionRevertForm::class,
    ],
    'route_provider' => ['revision' => RevisionHtmlRouteProvider::class],
    'translation' => BlockContentTranslationHandler::class,
  ],
  links: [
    'canonical' => '/admin/content/block/{block_content}',
    'delete-form' => '/admin/content/block/{block_content}/delete',
    'edit-form' => '/admin/content/block/{block_content}',
    'collection' => '/admin/content/block',
    'create' => '/block',
    'revision-delete-form' => '/admin/content/block/{block_content}/revision/{block_content_revision}/delete',
    'revision-revert-form' => '/admin/content/block/{block_content}/revision/{block_content_revision}/revert',
    'version-history' => '/admin/content/block/{block_content}/revisions',
  ],
  admin_permission: 'administer block content',
  collection_permission: 'access block library',
  bundle_entity_type: 'block_content_type',
  bundle_label: new TranslatableMarkup('Block type'),
  base_table: 'block_content',
  data_table: 'block_content_field_data',
  revision_table: 'block_content_revision',
  revision_data_table: 'block_content_field_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  label_count: [
    'singular' => '@count content block',
    'plural' => '@count content blocks',
  ],
  field_ui_base_route: 'entity.block_content_type.edit_form',
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log',
  ],
)]
class BlockContent extends EditorialContentEntityBase implements BlockContentInterface {

  use RefinableDependentAccessTrait;

  /**
   * The theme the block is being created in.
   *
   * When creating a new content block from the block library, the user is
   * redirected to the configure form for that block in the given theme. The
   * theme is stored against the block when the content block add form is shown.
   *
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    $duplicate->revision_id->value = NULL;
    $duplicate->id->value = NULL;
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function setTheme($theme) {
    $this->theme = $theme;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->theme;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if ($this->isReusable() || (isset($this->original) && $this->original->isReusable())) {
      static::invalidateBlockPluginCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    /** @var \Drupal\block_content\BlockContentInterface $block */
    foreach ($entities as $block) {
      foreach ($block->getInstances() as $instance) {
        $instance->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    /** @var \Drupal\block_content\BlockContentInterface $block */
    foreach ($entities as $block) {
      if ($block->isReusable()) {
        // If any deleted blocks are reusable clear the block cache.
        static::invalidateBlockPluginCache();
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances() {
    return \Drupal::entityTypeManager()->getStorage('block')->loadByProperties(['plugin' => 'block_content:' . $this->uuid()]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && empty($record->revision_log_message)) {
      // If we are updating an existing block_content without adding a new
      // revision and the user did not supply a revision log, keep the existing
      // one.
      $record->revision_log = $this->original->getRevisionLogMessage();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id']->setLabel(t('Content block ID'))
      ->setDescription(t('The content block ID.'));

    $fields['uuid']->setDescription(t('The content block UUID.'));

    $fields['revision_id']->setDescription(t('The revision ID.'));

    $fields['langcode']->setDescription(t('The content block language code.'));

    $fields['type']->setLabel(t('Block type'))
      ->setDescription(t('The block type.'));

    $fields['revision_log']->setDescription(t('The log entry explaining the changes in this revision.'));

    $fields['info'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Block description'))
      ->setDescription(t('A brief description of your block.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the content block was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['reusable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Reusable'))
      ->setDescription(t('A boolean indicating whether this block is reusable.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setDefaultValue(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setInfo($info) {
    $this->set('info', $info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isReusable() {
    return (bool) $this->get('reusable')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setReusable() {
    return $this->set('reusable', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function setNonReusable() {
    return $this->set('reusable', FALSE);
  }

  /**
   * Invalidates the block plugin cache after changes and deletions.
   */
  protected static function invalidateBlockPluginCache() {
    // Invalidate the block cache to update content block-based derivatives.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

}
