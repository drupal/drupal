<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\Core\Entity\Comment.
 */

namespace Drupal\comment\Plugin\Core\Entity;

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
 *       "default" = "Drupal\comment\CommentFormController"
 *     },
 *     "translation" = "Drupal\comment\CommentTranslationController"
 *   },
 *   base_table = "comment",
 *   uri_callback = "comment_uri",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   route_base_path = "admin/structure/types/manage/{bundle}/comment",
 *   bundle_prefix = "comment_node_",
 *   entity_keys = {
 *     "id" = "cid",
 *     "bundle" = "node_type",
 *     "label" = "subject",
 *     "uuid" = "uuid"
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
   * @todo Rename to 'id'.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $cid;

  /**
   * The comment UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uuid;

  /**
   * The parent comment ID if this is a reply to a comment.
   *
   * @todo: Rename to 'parent_id'.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $pid;

  /**
   * The ID of the node to which the comment is attached.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $nid;

  /**
   * The comment language code.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $langcode;

  /**
   * The comment title.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $subject;

  /**
   * The comment author ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uid;

  /**
   * The comment author's name.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $name;

  /**
   * The comment author's e-mail address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $mail;

  /**
   * The comment author's home page address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $homepage;

  /**
   * The comment author's hostname.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $hostname;

  /**
   * The time that the comment was created.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $created;

  /**
   * The time that the comment was last edited.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $changed;

  /**
   * A boolean field indicating whether the comment is published.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $status;

  /**
   * The alphadecimal representation of the comment's place in a thread.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $thread;

  /**
   * The comment node type.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $node_type;

  /**
   * The comment 'new' marker for the current user.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $new;

  /**
   * Initialize the object. Invoked upon construction and wake up.
   */
  protected function init() {
    parent::init();
    // We unset all defined properties, so magic getters apply.
    unset($this->cid);
    unset($this->uuid);
    unset($this->pid);
    unset($this->nid);
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
    unset($this->node_type);
    unset($this->new);
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
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    if (empty($values['node_type']) && !empty($values['nid'])) {
      $node = node_load(is_object($values['nid']) ? $values['nid']->value : $values['nid']);
      $values['node_type'] = 'comment_node_' . $node->type;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    global $user;

    if (!isset($this->status->value)) {
      $this->status->value = user_access('skip comment approval') ? COMMENT_PUBLISHED : COMMENT_NOT_PUBLISHED;
    }
    // Make sure we have a proper bundle name.
    if (!isset($this->node_type->value)) {
      $this->node_type->value = 'comment_node_' . $this->nid->entity->type;
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
        } while (!lock()->acquire("comment:{$this->nid->target_id}:$thread"));
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
    $this->releaseThreadLock();
    // Update the {node_comment_statistics} table prior to executing the hook.
    $storage_controller->updateNodeStatistics($this->nid->target_id);
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
    $child_cids = $storage_controller->getChildCids($entities);
    entity_delete_multiple('comment', $child_cids);

    foreach ($entities as $id => $entity) {
      $storage_controller->updateNodeStatistics($entity->nid->target_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function permalink() {

    $url['path'] = 'node/' . $this->nid->value;
    $url['options'] = array('fragment' => 'comment-' . $this->id());

    return $url;
  }
}
