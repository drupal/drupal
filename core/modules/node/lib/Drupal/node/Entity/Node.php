<?php

/**
 * @file
 * Definition of Drupal\node\Entity\Node.
 */

namespace Drupal\node\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the node entity class.
 *
 * @ContentEntityType(
 *   id = "node",
 *   label = @Translation("Content"),
 *   bundle_label = @Translation("Content type"),
 *   controllers = {
 *     "view_builder" = "Drupal\node\NodeViewBuilder",
 *     "access" = "Drupal\node\NodeAccessController",
 *     "form" = {
 *       "default" = "Drupal\node\NodeFormController",
 *       "delete" = "Drupal\node\Form\NodeDeleteForm",
 *       "edit" = "Drupal\node\NodeFormController"
 *     },
 *     "list" = "Drupal\node\NodeListController",
 *     "translation" = "Drupal\node\NodeTranslationController"
 *   },
 *   base_table = "node",
 *   data_table = "node_field_data",
 *   revision_table = "node_revision",
 *   revision_data_table = "node_field_revision",
 *   uri_callback = "node_uri",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "nid",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "node_type",
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "node.view",
 *     "delete-form" = "node.delete_confirm",
 *     "edit-form" = "node.page_edit",
 *     "version-history" = "node.revision_overview",
 *     "admin-form" = "node.type_edit"
 *   }
 * )
 */
class Node extends ContentEntityBase implements NodeInterface {

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('nid')->value;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::getRevisionId().
   */
  public function getRevisionId() {
    return $this->get('vid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageControllerInterface $storage_controller, \stdClass $record) {
    parent::preSaveRevision($storage_controller, $record);

    if ($this->newRevision) {
      // When inserting either a new node or a new node revision, $node->log
      // must be set because {node_field_revision}.log is a text column and
      // therefore cannot have a default value. However, it might not be set at
      // this point (for example, if the user submitting a node form does not
      // have permission to create revisions), so we ensure that it is at least
      // an empty string in that case.
      // @todo Make the {node_field_revision}.log column nullable so that we
      //   can remove this check.
      if (!isset($record->log)) {
        $record->log = '';
      }
    }
    elseif (isset($this->original) && (!isset($record->log) || $record->log === '')) {
      // If we are updating an existing node without adding a new revision, we
      // need to make sure $entity->log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->log = $this->original->log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    // Update the node access table for this node, but only if it is the
    // default revision. There's no need to delete existing records if the node
    // is new.
    if ($this->isDefaultRevision()) {
      \Drupal::entityManager()->getAccessController('node')->writeGrants($this, $update);
    }

    // Reindex the node when it is updated. The node is automatically indexed
    // when it is added, simply by being added to the node table.
    if ($update) {
      node_reindex_node_search($this->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::preDelete($storage_controller, $entities);

    // Assure that all nodes deleted are removed from the search index.
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      foreach ($entities as $entity) {
        search_reindex($entity->nid->value, 'node_search');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $nodes) {
    parent::postDelete($storage_controller, $nodes);
    \Drupal::service('node.grant_storage')->deleteNodeRecords(array_keys($nodes));
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'create') {
      return parent::access($operation, $account);
    }

    return \Drupal::entityManager()
      ->getAccessController($this->entityTypeId)
      ->access($this, $operation, $this->prepareLangcode(), $account);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareLangcode() {
    $langcode = $this->language()->id;
    // If the Language module is enabled, try to use the language from content
    // negotiation.
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      // Load languages the node exists in.
      $node_translations = $this->getTranslationLanguages();
      // Load the language from content negotiation.
      $content_negotiation_langcode = \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT)->id;
      // If there is a translation available, use it.
      if (isset($node_translations[$content_negotiation_langcode])) {
        $langcode = $content_negotiation_langcode;
      }
    }
    return $langcode;
  }


  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
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
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPromoted() {
    return (bool) $this->get('promote')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPromoted($promoted) {
    $this->set('promote', $promoted ? NODE_PROMOTED : NODE_NOT_PROMOTED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSticky() {
    return (bool) $this->get('sticky')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSticky($sticky) {
    $this->set('sticky', $sticky ? NODE_STICKY : NODE_NOT_STICKY);
    return $this;
  }
  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
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
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['nid'] = FieldDefinition::create('integer')
      ->setLabel(t('Node ID'))
      ->setDescription(t('The node ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The node UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = FieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The node revision ID.'))
      ->setReadOnly(TRUE);

    $fields['type'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The node type.'))
      ->setSetting('target_type', 'node_type')
      ->setReadOnly(TRUE);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The node language code.'));

    $fields['title'] = FieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of this node, always treated as non-markup plain text.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the node author.'))
      ->setSettings(array(
        'target_type' => 'user',
        'default_value' => 0,
      ));

    $fields['status'] = FieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the node is published.'));

    $fields['created'] = FieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the node was created.'));

    $fields['changed'] = FieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the node was last edited.'));

    $fields['promote'] = FieldDefinition::create('boolean')
      ->setLabel(t('Promote'))
      ->setDescription(t('A boolean indicating whether the node should be displayed on the front page.'));

    $fields['sticky'] = FieldDefinition::create('boolean')
      ->setLabel(t('Sticky'))
      ->setDescription(t('A boolean indicating whether the node should be displayed at the top of lists in which it appears.'));

    $fields['revision_timestamp'] = FieldDefinition::create('timestamp')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE);

    $fields['revision_uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSettings(array('target_type' => 'user'))
      ->setQueryable(FALSE);

    $fields['log'] = FieldDefinition::create('string')
      ->setLabel(t('Log'))
      ->setDescription(t('The log entry explaining the changes in this revision.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $node_type = node_type_load($bundle);
    $fields = array();
    if (isset($node_type->title_label)) {
      $fields['title'] = clone $base_field_definitions['title'];
      $fields['title']->setLabel($node_type->title_label);
    }
    return $fields;
  }

}
