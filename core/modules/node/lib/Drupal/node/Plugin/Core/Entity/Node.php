<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\Core\Entity\Node.
 */

namespace Drupal\node\Plugin\Core\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
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
 *       "default" = "Drupal\node\NodeFormController"
 *     },
 *     "translation" = "Drupal\node\NodeTranslationController"
 *   },
 *   base_table = "node",
 *   data_table = "node_field_data",
 *   revision_table = "node_field_revision",
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
 *   bundle_keys = {
 *     "bundle" = "type"
 *   },
 *   route_base_path = "admin/structure/types/manage/{bundle}",
 *   permission_granularity = "bundle"
 * )
 */
class Node extends EntityNG implements NodeInterface {

  /**
   * The node ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $nid;

  /**
   * The node revision ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $vid;

  /**
   * The node UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uuid;

  /**
   * The node content type (bundle).
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $type;

  /**
   * The node language code.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $langcode;

  /**
   * The node title.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $title;

  /**
   * The node owner's user ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uid;

  /**
   * The node published status indicator.
   *
   * Unpublished nodes are only visible to their authors and to administrators.
   * The value is either NODE_PUBLISHED or NODE_NOT_PUBLISHED.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $status;

  /**
   * The node creation timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $created;

  /**
   * The node modification timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $changed;

  /**
   * The node comment status indicator.
   *
   * COMMENT_NODE_HIDDEN => no comments
   * COMMENT_NODE_CLOSED => comments are read-only
   * COMMENT_NODE_OPEN => open (read/write)
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $comment;

  /**
   * The node promotion status.
   *
   * Promoted nodes should be displayed on the front page of the site. The value
   * is either NODE_PROMOTED or NODE_NOT_PROMOTED.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $promote;

  /**
   * The node sticky status.
   *
   * Sticky nodes should be displayed at the top of lists in which they appear.
   * The value is either NODE_STICKY or NODE_NOT_STICKY.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $sticky;

  /**
   * The node translation set ID.
   *
   * Translations sets are based on the ID of the node containing the source
   * text for the translation set.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $tnid;

  /**
   * The node translation status.
   *
   * If the translation page needs to be updated, the value is 1; otherwise 0.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $translate;

  /**
   * The node revision creation timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $revision_timestamp;

  /**
   * The node revision author's user ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $revision_uid;

  /**
   * The node revision log message.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $log;

  /**
   * Overrides \Drupal\Core\Entity\EntityNG::init().
   */
  protected function init() {
    parent::init();
    // We unset all defined properties, so magic getters apply.
    unset($this->nid);
    unset($this->vid);
    unset($this->uuid);
    unset($this->type);
    unset($this->title);
    unset($this->uid);
    unset($this->status);
    unset($this->created);
    unset($this->changed);
    unset($this->comment);
    unset($this->promote);
    unset($this->sticky);
    unset($this->tnid);
    unset($this->translate);
    unset($this->revision_timestamp);
    unset($this->revision_uid);
    unset($this->log);
  }

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

}
