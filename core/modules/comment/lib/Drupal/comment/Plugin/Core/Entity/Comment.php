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
   * The plain data values of the contained properties.
   *
   * Define default values.
   *
   * @var array
   */
  protected $values = array(
    'langcode' => array(Language::LANGCODE_DEFAULT => array(0 => array('value' => Language::LANGCODE_NOT_SPECIFIED))),
    'name' => array(Language::LANGCODE_DEFAULT => array(0 => array('value' => ''))),
    'uid' => array(Language::LANGCODE_DEFAULT => array(0 => array('target_id' => 0))),
  );

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
}
