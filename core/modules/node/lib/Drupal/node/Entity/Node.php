<?php

/**
 * @file
 * Definition of Drupal\node\Entity\Node.
 */

namespace Drupal\node\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Defines the node entity class.
 *
 * @EntityType(
 *   id = "node",
 *   label = @Translation("Content"),
 *   bundle_label = @Translation("Content type"),
 *   module = "node",
 *   controllers = {
 *     "storage" = "Drupal\node\NodeStorageController",
 *     "render" = "Drupal\node\NodeRenderController",
 *     "access" = "Drupal\node\NodeAccessController",
 *     "form" = {
 *       "default" = "Drupal\node\NodeFormController",
 *       "delete" = "Drupal\node\Form\NodeDeleteForm",
 *       "edit" = "Drupal\node\NodeFormController"
 *     },
 *     "translation" = "Drupal\node\NodeTranslationController"
 *   },
 *   base_table = "node",
 *   data_table = "node_field_data",
 *   revision_table = "node_field_revision",
 *   uri_callback = "node_uri",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   render_cache = FALSE,
 *   entity_keys = {
 *     "id" = "nid",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   bundle_keys = {
 *     "bundle" = "type"
 *   },
 *   route_base_path = "admin/structure/types/manage/{bundle}",
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/node/{node}",
 *     "edit-form" = "/node/{node}/edit",
 *     "version-history" = "/node/{node}/revisions"
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
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    // Before saving the node, set changed and revision times.
    $this->changed->value = REQUEST_TIME;
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
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::preDelete($storage_controller, $entities);

    if (module_exists('search')) {
      foreach ($entities as $entity) {
        search_reindex($entity->nid->value, 'node');
      }
    }
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
      ->getAccessController($this->entityType)
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
      $content_negotiation_langcode = \Drupal::languageManager()->getLanguage(Language::TYPE_CONTENT)->id;
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
  public function getAuthor() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorId($uid) {
    $this->set('uid', $uid);
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
  public static function baseFieldDefinitions($entity_type) {
    $properties['nid'] = array(
      'label' => t('Node ID'),
      'description' => t('The node ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The node UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
    );
    $properties['vid'] = array(
      'label' => t('Revision ID'),
      'description' => t('The node revision ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['type'] = array(
      'label' => t('Type'),
      'description' => t('The node type.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The node language code.'),
      'type' => 'language_field',
    );
    $properties['title'] = array(
      'label' => t('Title'),
      'description' => t('The title of this node, always treated as non-markup plain text.'),
      'type' => 'string_field',
      'required' => TRUE,
      'settings' => array(
        'default_value' => '',
      ),
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 255)),
      ),
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the node author.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
        'default_value' => 0,
      ),
    );
    $properties['status'] = array(
      'label' => t('Publishing status'),
      'description' => t('A boolean indicating whether the node is published.'),
      'type' => 'boolean_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the node was created.'),
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the node was last edited.'),
      'type' => 'integer_field',
      'property_constraints' => array(
        'value' => array('EntityChanged' => array()),
      ),
    );
    $properties['promote'] = array(
      'label' => t('Promote'),
      'description' => t('A boolean indicating whether the node should be displayed on the front page.'),
      'type' => 'boolean_field',
    );
    $properties['sticky'] = array(
      'label' => t('Sticky'),
      'description' => t('A boolean indicating whether the node should be displayed at the top of lists in which it appears.'),
      'type' => 'boolean_field',
    );
    $properties['translate'] = array(
      'label' => t('Translate'),
      'description' => t('A boolean indicating whether this translation page needs to be updated.'),
      'type' => 'boolean_field',
    );
    $properties['revision_timestamp'] = array(
      'label' => t('Revision timestamp'),
      'description' => t('The time that the current revision was created.'),
      'type' => 'integer_field',
      'queryable' => FALSE,
    );
    $properties['revision_uid'] = array(
      'label' => t('Revision user ID'),
      'description' => t('The user ID of the author of the current revision.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
      'queryable' => FALSE,
    );
    $properties['log'] = array(
      'label' => t('Log'),
      'description' => t('The log entry explaining the changes in this version.'),
      'type' => 'string_field',
    );
    return $properties;
  }

}
