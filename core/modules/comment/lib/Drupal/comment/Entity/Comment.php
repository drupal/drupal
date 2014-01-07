<?php

/**
 * @file
 * Definition of Drupal\comment\Entity\Comment.
 */

namespace Drupal\comment\Entity;

use Drupal\Component\Utility\Number;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the comment entity class.
 *
 * @EntityType(
 *   id = "comment",
 *   label = @Translation("Comment"),
 *   bundle_label = @Translation("Content type"),
 *   controllers = {
 *     "storage" = "Drupal\comment\CommentStorageController",
 *     "access" = "Drupal\comment\CommentAccessController",
 *     "view_builder" = "Drupal\comment\CommentViewBuilder",
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
 *     "canonical" = "comment.permalink",
 *     "edit-form" = "comment.edit_page",
 *     "admin-form" = "comment.bundle"
 *   }
 * )
 */
class Comment extends ContentEntityBase implements CommentInterface {

  /**
   * The thread for which a lock was acquired.
   */
  protected $threadLock = '';

  /**
   * The comment ID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $cid;

  /**
   * The comment UUID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $uuid;

  /**
   * The parent comment ID if this is a reply to another comment.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $pid;

  /**
   * The entity ID for the entity to which this comment is attached.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $entity_id;

  /**
   * The entity type of the entity to which this comment is attached.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $entity_type;

  /**
   * The field to which this comment is attached.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $field_id;

  /**
   * The comment language code.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $langcode;

  /**
   * The comment title.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $subject;

  /**
   * The comment author ID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $uid;

  /**
   * The comment author's name.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $name;

  /**
   * The comment author's e-mail address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $mail;

  /**
   * The comment author's home page address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $homepage;

  /**
   * The comment author's hostname.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $hostname;

  /**
   * The time that the comment was created.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $created;

  /**
   * The time that the comment was last edited.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $changed;

  /**
   * A boolean field indicating whether the comment is published.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $status;

  /**
   * The alphadecimal representation of the comment's place in a thread.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
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

    if (!isset($this->status->value)) {
      $this->status->value = \Drupal::currentUser()->hasPermission('skip comment approval') ? CommentInterface::PUBLISHED : CommentInterface::NOT_PUBLISHED;
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
          $n = Number::alphadecimalToInt($parts[0]);
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
            $n = Number::alphadecimalToInt($parts[$parent_depth]);
          }
        }
        // Finally, build the thread field for this new comment. To avoid
        // race conditions, get a lock on the thread. If another process already
        // has the lock, just move to the next integer.
        do {
          $thread = $prefix . Number::intToAlphadecimal(++$n) . '/';
          $lock_name = "comment:{$this->entity_id->value}:$thread";
        } while (!\Drupal::lock()->acquire($lock_name));
        $this->threadLock = $lock_name;
      }
      if (empty($this->created->value)) {
        $this->created->value = REQUEST_TIME;
      }
      if (empty($this->changed->value)) {
        $this->changed->value = $this->created->value;
      }
      // We test the value with '===' because we need to modify anonymous
      // users as well.
      if ($this->uid->target_id === \Drupal::currentUser()->id() && \Drupal::currentUser()->isAuthenticated()) {
        $this->name->value = \Drupal::currentUser()->getUsername();
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
    if ($this->status->value == CommentInterface::PUBLISHED) {
      module_invoke_all('comment_publish', $this);
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
    $fields['cid'] = FieldDefinition::create('integer')
      ->setLabel(t('Comment ID'))
      ->setDescription(t('The comment ID.'))
      ->setReadOnly(TRUE);

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
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The comment language code.'));

    $fields['subject'] = FieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The comment title or subject.'));

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the comment author.'))
      ->setSettings(array(
        'target_type' => 'user',
        'default_value' => 0,
      ));

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t("The comment author's name."))
      ->setSetting('default_value', '');

    $fields['mail'] = FieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t("The comment author's e-mail address."));

    $fields['homepage'] = FieldDefinition::create('string')
      ->setLabel(t('Homepage'))
      ->setDescription(t("The comment author's home page address."));

    $fields['hostname'] = FieldDefinition::create('string')
      ->setLabel(t('Hostname'))
      ->setDescription(t("The comment author's hostname."));

    // @todo Convert to a "created" field in https://drupal.org/node/2145103.
    $fields['created'] = FieldDefinition::create('integer')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the comment was created.'));

    // @todo Convert to a "changed" field in https://drupal.org/node/2145103.
    $fields['changed'] = FieldDefinition::create('integer')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the comment was last edited.'))
      ->setPropertyConstraints('value', array('EntityChanged' => array()));

    $fields['status'] = FieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the comment is published.'));

    $fields['thread'] = FieldDefinition::create('string')
      ->setLabel(t('Thread place'))
      ->setDescription(t("The alphadecimal representation of the comment's place in a thread, consisting of a base 36 string prefixed by an integer indicating its length."));

    $fields['entity_type'] = FieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type to which this comment is attached.'));

    // @todo Convert to aa entity_reference field in
    // https://drupal.org/node/2149859.
    $fields['field_id'] = FieldDefinition::create('string')
      ->setLabel(t('Field ID'))
      ->setDescription(t('The comment field id.'));

    $fields['field_name'] = FieldDefinition::create('string')
      ->setLabel(t('Comment field name'))
      ->setDescription(t('The field name through which this comment was added.'))
      ->setComputed(TRUE);

    $item_definition = $fields['field_name']->getItemDefinition();
    $item_definition->setClass('\Drupal\comment\CommentFieldName');
    $fields['field_name']->setItemDefinition($item_definition);

    return $fields;
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
