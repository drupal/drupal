<?php

/**
 * @file
 * Definition of Drupal\comment\Entity\Comment.
 */

namespace Drupal\comment\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Language\Language;

/**
 * Defines the comment entity class.
 *
 * @EntityType(
 *   id = "comment",
 *   label = @Translation("Comment"),
 *   bundle_label = @Translation("Content type"),
 *   module = "comment",
 *   controllers = {
 *     "storage" = "Drupal\comment\CommentStorageController",
 *     "access" = "Drupal\comment\CommentAccessController",
 *     "render" = "Drupal\comment\CommentRenderController",
 *     "form" = {
 *       "default" = "Drupal\comment\CommentFormController",
 *       "delete" = "Drupal\comment\Form\DeleteForm"
 *     },
 *     "translation" = "Drupal\comment\CommentTranslationController"
 *   },
 *   base_table = "comment",
 *   uri_callback = "comment_uri",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   render_cache = FALSE,
 *   route_base_path = "admin/structure/comments/manage/{bundle}",
 *   entity_keys = {
 *     "id" = "cid",
 *     "bundle" = "field_id",
 *     "label" = "subject",
 *     "uuid" = "uuid"
 *   },
 *   bundle_keys = {
 *     "bundle" = "field_id"
 *   },
 *   links = {
 *     "canonical" = "/comment/{comment}",
 *     "edit-form" = "/comment/{comment}/edit"
 *   }
 * )
 */
class Comment extends EntityNG implements CommentInterface {

  /**
   * The comment ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $cid;

  /**
   * The comment UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $uuid;

  /**
   * The parent comment ID if this is a reply to another comment.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $pid;

  /**
   * The entity ID for the entity to which this comment is attached.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $entity_id;

  /**
   * The entity type of the entity to which this comment is attached.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $entity_type;

  /**
   * The field to which this comment is attached.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $field_id;

  /**
   * The comment language code.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $langcode;

  /**
   * The comment title.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $subject;

  /**
   * The comment author ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $uid;

  /**
   * The comment author's name.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $name;

  /**
   * The comment author's e-mail address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $mail;

  /**
   * The comment author's home page address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $homepage;

  /**
   * The comment author's hostname.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $hostname;

  /**
   * The time that the comment was created.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $created;

  /**
   * The time that the comment was last edited.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $changed;

  /**
   * A boolean field indicating whether the comment is published.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $status;

  /**
   * The alphadecimal representation of the comment's place in a thread.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $thread;

  /**
   * Initialize the object. Invoked upon construction and wake up.
   */
  protected function init() {
    parent::init();
    // We unset all defined properties, so magic getters apply.
    unset($this->cid);
    unset($this->uuid);
    unset($this->pid);
    unset($this->entity_id);
    unset($this->field_id);
    unset($this->subject);
    unset($this->uid);
    unset($this->name);
    unset($this->mail);
    unset($this->homepage);
    unset($this->hostname);
    unset($this->created);
    unset($this->changed);
    unset($this->status);
    unset($this->thread);
    unset($this->entity_type);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('cid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    global $user;

    if (!isset($this->status->value)) {
      $this->status->value = user_access('skip comment approval') ? COMMENT_PUBLISHED : COMMENT_NOT_PUBLISHED;
    }
    if ($this->isNew()) {
      // Add the comment to database. This next section builds the thread field.
      // Also see the documentation for comment_view().
      if (!empty($this->thread->value)) {
        // Allow calling code to set thread itself.
        $thread = $this->thread->value;
      }
      else {
        if ($this->threadLock) {
          // As preSave() is protected, this can only happen when this class
          // is extended in a faulty manner.
          throw new \LogicException('preSave is called again without calling postSave() or releaseThreadLock()');
        }
        if ($this->pid->target_id == 0) {
          // This is a comment with no parent comment (depth 0): we start
          // by retrieving the maximum thread level.
          $max = $storage_controller->getMaxThread($this);
          // Strip the "/" from the end of the thread.
          $max = rtrim($max, '/');
          // We need to get the value at the correct depth.
          $parts = explode('.', $max);
          $n = comment_alphadecimal_to_int($parts[0]);
          $prefix = '';
        }
        else {
          // This is a comment with a parent comment, so increase the part of
          // the thread value at the proper depth.

          // Get the parent comment:
          $parent = $this->pid->entity;
          // Strip the "/" from the end of the parent thread.
          $parent->thread->value = (string) rtrim((string) $parent->thread->value, '/');
          $prefix = $parent->thread->value . '.';
          // Get the max value in *this* thread.
          $max = $storage_controller->getMaxThreadPerThread($this);

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
            $parent_depth = count(explode('.', $parent->thread->value));
            $n = comment_alphadecimal_to_int($parts[$parent_depth]);
          }
        }
        // Finally, build the thread field for this new comment. To avoid
        // race conditions, get a lock on the thread. If aother process already
        // has the lock, just move to the next integer.
        do {
          $thread = $prefix . comment_int_to_alphadecimal(++$n) . '/';
        } while (!lock()->acquire("comment:{$this->entity_id->value}:$thread"));
        $this->threadLock = $thread;
      }
      if (empty($this->created->value)) {
        $this->created->value = REQUEST_TIME;
      }
      if (empty($this->changed->value)) {
        $this->changed->value = $this->created->value;
      }
      // We test the value with '===' because we need to modify anonymous
      // users as well.
      if ($this->uid->target_id === $user->id() && $user->isAuthenticated()) {
        $this->name->value = $user->getUsername();
      }
      // Add the values which aren't passed into the function.
      $this->thread->value = $thread;
      $this->hostname->value = \Drupal::request()->getClientIP();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    $this->releaseThreadLock();
    // Update the {comment_entity_statistics} table prior to executing the hook.
    $storage_controller->updateEntityStatistics($this);
    if ($this->status->value == COMMENT_PUBLISHED) {
      module_invoke_all('comment_publish', $this);
    }
  }

  /**
   * Release the lock acquired for the thread in preSave().
   */
  protected function releaseThreadLock() {
    if ($this->threadLock) {
      lock()->release($this->threadLock);
      $this->threadLock = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    $child_cids = $storage_controller->getChildCids($entities);
    entity_delete_multiple('comment', $child_cids);

    foreach ($entities as $id => $entity) {
      $storage_controller->updateEntityStatistics($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function permalink() {
    $entity = entity_load($this->get('entity_type')->value, $this->get('entity_id')->value);
    $uri = $entity->uri();
    $url['path'] = $uri['path'];
    $url['options'] = array('fragment' => 'comment-' . $this->id());

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['cid'] = array(
      'label' => t('ID'),
      'description' => t('The comment ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The comment UUID.'),
      'type' => 'uuid_field',
    );
    $properties['pid'] = array(
      'label' => t('Parent ID'),
      'description' => t('The parent comment ID if this is a reply to a comment.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'comment'),
    );
    $properties['entity_id'] = array(
      'label' => t('Entity ID'),
      'description' => t('The ID of the entity of which this comment is a reply.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'node'),
      'required' => TRUE,
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The comment language code.'),
      'type' => 'language_field',
    );
    $properties['subject'] = array(
      'label' => t('Subject'),
      'description' => t('The comment title or subject.'),
      'type' => 'string_field',
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the comment author.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
        'default_value' => 0,
      ),
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t("The comment author's name."),
      'type' => 'string_field',
      'settings' => array('default_value' => ''),
    );
    $properties['mail'] = array(
      'label' => t('e-mail'),
      'description' => t("The comment author's e-mail address."),
      'type' => 'string_field',
    );
    $properties['homepage'] = array(
      'label' => t('Homepage'),
      'description' => t("The comment author's home page address."),
      'type' => 'string_field',
    );
    $properties['hostname'] = array(
      'label' => t('Hostname'),
      'description' => t("The comment author's hostname."),
      'type' => 'string_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the comment was created.'),
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the comment was last edited.'),
      'type' => 'integer_field',
    );
    $properties['status'] = array(
      'label' => t('Publishing status'),
      'description' => t('A boolean indicating whether the comment is published.'),
      'type' => 'boolean_field',
    );
    $properties['thread'] = array(
      'label' => t('Thread place'),
      'description' => t("The alphadecimal representation of the comment's place in a thread, consisting of a base 36 string prefixed by an integer indicating its length."),
      'type' => 'string_field',
    );
    $properties['entity_type'] = array(
      'label' => t('Entity type'),
      'description' => t("The entity type to which this comment is attached."),
      'type' => 'string_field',
    );
    $properties['field_id'] = array(
      'label' => t('Field ID'),
      'description' => t("The comment field id."),
      'type' => 'string_field',
    );
    $properties['field_name'] = array(
      'label' => t('Comment field name'),
      'description' => t("The field name through which this comment was added."),
      'type' => 'string_field',
      'computed' => TRUE,
      'class' => '\Drupal\comment\CommentFieldName',
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->changed->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    if (empty($values['field_id']) && !empty($values['field_name']) && !empty($values['entity_type'])) {
      $values['field_id'] = $values['entity_type'] . '__' . $values['field_name'];
    }
  }

}
