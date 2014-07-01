<?php

/**
 * @file
 * Contains \Drupal\comment\Entity\Comment.
 */

namespace Drupal\comment\Entity;

use Drupal\Component\Utility\Number;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\UserInterface;

/**
 * Defines the comment entity class.
 *
 * @ContentEntityType(
 *   id = "comment",
 *   label = @Translation("Comment"),
 *   bundle_label = @Translation("Content type"),
 *   controllers = {
 *     "storage" = "Drupal\comment\CommentStorage",
 *     "access" = "Drupal\comment\CommentAccessController",
 *     "view_builder" = "Drupal\comment\CommentViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\comment\CommentForm",
 *       "delete" = "Drupal\comment\Form\DeleteForm"
 *     },
 *     "translation" = "Drupal\comment\CommentTranslationHandler"
 *   },
 *   base_table = "comment",
 *   uri_callback = "comment_uri",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "cid",
 *     "bundle" = "comment_type",
 *     "label" = "subject",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "comment.permalink",
 *     "delete-form" = "comment.confirm_delete",
 *     "edit-form" = "comment.edit_page",
 *     "admin-form" = "comment.type_edit"
 *   },
 *   bundle_entity_type = "comment_type"
 * )
 */
class Comment extends ContentEntityBase implements CommentInterface {

  /**
   * The thread for which a lock was acquired.
   */
  protected $threadLock = '';

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (is_null($this->get('status')->value)) {
      $published = \Drupal::currentUser()->hasPermission('skip comment approval') ? CommentInterface::PUBLISHED : CommentInterface::NOT_PUBLISHED;
      $this->setPublished($published);
    }
    if ($this->isNew()) {
      // Add the comment to database. This next section builds the thread field.
      // Also see the documentation for comment_view().
      $thread = $this->getThread();
      if (empty($thread)) {
        if ($this->threadLock) {
          // As preSave() is protected, this can only happen when this class
          // is extended in a faulty manner.
          throw new \LogicException('preSave is called again without calling postSave() or releaseThreadLock()');
        }
        if (!$this->hasParentComment()) {
          // This is a comment with no parent comment (depth 0): we start
          // by retrieving the maximum thread level.
          $max = $storage->getMaxThread($this);
          // Strip the "/" from the end of the thread.
          $max = rtrim($max, '/');
          // We need to get the value at the correct depth.
          $parts = explode('.', $max);
          $n = Number::alphadecimalToInt($parts[0]);
          $prefix = '';
        }
        else {
          // This is a comment with a parent comment, so increase the part of
          // the thread value at the proper depth.

          // Get the parent comment:
          $parent = $this->getParentComment();
          // Strip the "/" from the end of the parent thread.
          $parent->setThread((string) rtrim((string) $parent->getThread(), '/'));
          $prefix = $parent->getThread() . '.';
          // Get the max value in *this* thread.
          $max = $storage->getMaxThreadPerThread($this);

          if ($max == '') {
            // First child of this parent. As the other two cases do an
            // increment of the thread number before creating the thread
            // string set this to -1 so it requires an increment too.
            $n = -1;
          }
          else {
            // Strip the "/" at the end of the thread.
            $max = rtrim($max, '/');
            // Get the value at the correct depth.
            $parts = explode('.', $max);
            $parent_depth = count(explode('.', $parent->getThread()));
            $n = Number::alphadecimalToInt($parts[$parent_depth]);
          }
        }
        // Finally, build the thread field for this new comment. To avoid
        // race conditions, get a lock on the thread. If another process already
        // has the lock, just move to the next integer.
        do {
          $thread = $prefix . Number::intToAlphadecimal(++$n) . '/';
          $lock_name = "comment:{$this->getCommentedEntityId()}:$thread";
        } while (!\Drupal::lock()->acquire($lock_name));
        $this->threadLock = $lock_name;
      }
      // We test the value with '===' because we need to modify anonymous
      // users as well.
      if ($this->getOwnerId() === \Drupal::currentUser()->id() && \Drupal::currentUser()->isAuthenticated()) {
        $this->setAuthorName(\Drupal::currentUser()->getUsername());
      }
      // Add the values which aren't passed into the function.
      $this->setThread($thread);
      $this->setHostname(\Drupal::request()->getClientIP());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $this->releaseThreadLock();
    // Update the {comment_entity_statistics} table prior to executing the hook.
    $storage->updateEntityStatistics($this);
    if ($this->isPublished()) {
      \Drupal::moduleHandler()->invokeAll('comment_publish', array($this));
    }
  }

  /**
   * Release the lock acquired for the thread in preSave().
   */
  protected function releaseThreadLock() {
    if ($this->threadLock) {
      \Drupal::lock()->release($this->threadLock);
      $this->threadLock = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    $child_cids = $storage->getChildCids($entities);
    entity_delete_multiple('comment', $child_cids);

    foreach ($entities as $id => $entity) {
      $storage->updateEntityStatistics($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    $referenced_entities = parent::referencedEntities();
    if ($this->getCommentedEntityId()) {
      $referenced_entities[] = $this->getCommentedEntity();
    }
    return $referenced_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function permalink() {
    $entity = $this->getCommentedEntity();
    $uri = $entity->urlInfo();
    $uri->setOption('fragment', 'comment-' . $this->id());
    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['cid'] = FieldDefinition::create('integer')
      ->setLabel(t('Comment ID'))
      ->setDescription(t('The comment ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The comment UUID.'))
      ->setReadOnly(TRUE);

    $fields['pid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Parent ID'))
      ->setDescription(t('The parent comment ID if this is a reply to a comment.'))
      ->setSetting('target_type', 'comment');

    $fields['entity_id'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity of which this comment is a reply.'))
      ->setRequired(TRUE);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The comment language code.'));

    $fields['subject'] = FieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The comment title or subject.'))
      ->setSetting('max_length', 64);

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the comment author.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValue(0);

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t("The comment author's name."))
      ->setSetting('max_length', 60)
      ->setDefaultValue('')
      ->addConstraint('CommentName', array());

    $fields['mail'] = FieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t("The comment author's email address."));

    $fields['homepage'] = FieldDefinition::create('uri')
      ->setLabel(t('Homepage'))
      ->setDescription(t("The comment author's home page address."))
      // URIs are not length limited by RFC 2616, but we can only store 255
      // characters in our comment DB schema.
      ->setSetting('max_length', 255);

    $fields['hostname'] = FieldDefinition::create('string')
      ->setLabel(t('Hostname'))
      ->setDescription(t("The comment author's hostname."))
      ->setSetting('max_length', 128);

    $fields['created'] = FieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the comment was created.'));

    $fields['changed'] = FieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the comment was last edited.'));

    $fields['status'] = FieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the comment is published.'))
      ->setDefaultValue(TRUE);

    $fields['thread'] = FieldDefinition::create('string')
      ->setLabel(t('Thread place'))
      ->setDescription(t("The alphadecimal representation of the comment's place in a thread, consisting of a base 36 string prefixed by an integer indicating its length."))
      ->setSetting('max_length', 255);

    $fields['entity_type'] = FieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type to which this comment is attached.'));

    $fields['comment_type'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Comment Type'))
      ->setDescription(t('The comment type.'))
      ->setSetting('target_type', 'comment_type');

    $fields['field_name'] = FieldDefinition::create('string')
      ->setLabel(t('Comment field name'))
      ->setDescription(t('The field name through which this comment was added.'))
      ->setSetting('max_length', 32);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    if ($comment_type = CommentType::load($bundle)) {
      $fields['entity_id'] = clone $base_field_definitions['entity_id'];
      $fields['entity_id']->setSetting('target_type', $comment_type->getTargetEntityTypeId());
      return $fields;
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function hasParentComment() {
    $parent = $this->get('pid')->entity;
    return !empty($parent);
  }

  /**
   * {@inheritdoc}
   */
  public function getParentComment() {
    return $this->get('pid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommentedEntity() {
    return $this->get('entity_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommentedEntityId() {
    return $this->get('entity_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommentedEntityTypeId() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldName($field_name) {
    $this->set('field_name', $field_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->get('field_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->set('subject', $subject);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorName() {
    if ($this->get('uid')->target_id) {
      return $this->get('uid')->entity->label();
    }
    return $this->get('name')->value ?: \Drupal::config('user.settings')->get('anonymous');
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorEmail() {
    $mail = $this->get('mail')->value;

    if ($this->get('uid')->target_id != 0) {
      $mail = $this->get('uid')->entity->getEmail();
    }

    return $mail;
  }

  /**
   * {@inheritdoc}
   */
  public function getHomepage() {
    return $this->get('homepage')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHomepage($homepage) {
    $this->set('homepage', $homepage);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostname() {
    return $this->get('hostname')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostname($hostname) {
    $this->set('hostname', $hostname);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    if (isset($this->get('created')->value)) {
      return $this->get('created')->value;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($created) {
    $this->set('created', $created);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return $this->get('status')->value == CommentInterface::PUBLISHED;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($status) {
    $this->set('status', $status ? CommentInterface::PUBLISHED : CommentInterface::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThread() {
    $thread = $this->get('thread');
    if (!empty($thread->value)) {
      return $thread->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setThread($thread) {
    $this->set('thread', $thread);
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
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    if (empty($values['comment_type']) && !empty($values['field_name']) && !empty($values['entity_type'])) {
      $field = FieldConfig::loadByName($values['entity_type'], $values['field_name']);
      $values['comment_type'] = $field->getSetting('comment_type');
    }
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
   * Get the comment type ID for this comment.
   *
   * @return string
   *   The ID of the comment type.
   */
  public function getTypeId() {
    return $this->bundle();
  }

}
