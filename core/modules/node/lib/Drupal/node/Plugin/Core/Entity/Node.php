<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\Core\Entity\Node.
 */

namespace Drupal\node\Plugin\Core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the node entity class.
 *
 * @Plugin(
 *   id = "node",
 *   label = @Translation("Node"),
 *   module = "node",
 *   controller_class = "Drupal\node\NodeStorageController",
 *   render_controller_class = "Drupal\node\NodeRenderController",
 *   form_controller_class = {
 *     "default" = "Drupal\node\NodeFormController"
 *   },
 *   translation_controller_class = "Drupal\node\NodeTranslationController",
 *   base_table = "node",
 *   revision_table = "node_revision",
 *   uri_callback = "node_uri",
 *   fieldable = TRUE,
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
 *   view_modes = {
 *     "full" = {
 *       "label" = "Full content",
 *       "custom_settings" = FALSE
 *     },
 *     "teaser" = {
 *       "label" = "Teaser",
 *       "custom_settings" = TRUE
 *     },
 *     "rss" = {
 *       "label" = "RSS",
 *       "custom_settings" = FALSE
 *     }
 *   }
 * )
 */
class Node extends Entity implements ContentEntityInterface {

  /**
   * The node ID.
   *
   * @var integer
   */
  public $nid;

  /**
   * The node revision ID.
   *
   * @var integer
   */
  public $vid;

  /**
   * Indicates whether this is the default node revision.
   *
   * The default revision of a node is the one loaded when no specific revision
   * has been specified. Only default revisions are saved to the node table.
   *
   * @var boolean
   */
  public $isDefaultRevision = TRUE;

  /**
   * The node UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The node content type (bundle).
   *
   * @var string
   */
  public $type;

  /**
   * The node language code.
   *
   * @var string
   */
  public $langcode = LANGUAGE_NOT_SPECIFIED;

  /**
   * The node title.
   *
   * @var string
   */
  public $title;

  /**
   * The node owner's user ID.
   *
   * @var integer
   */
  public $uid;

  /**
   * The node published status indicator.
   *
   * Unpublished nodes are only visible to their authors and to administrators.
   * The value is either NODE_PUBLISHED or NODE_NOT_PUBLISHED.
   *
   * @var integer
   */
  public $status;

  /**
   * The node creation timestamp.
   *
   * @var integer
   */
  public $created;

  /**
   * The node modification timestamp.
   *
   * @var integer
   */
  public $changed;

  /**
   * The node comment status indicator.
   *
   * COMMENT_NODE_HIDDEN => no comments
   * COMMENT_NODE_CLOSED => comments are read-only
   * COMMENT_NODE_OPEN => open (read/write)
   *
   * @var integer
   */
  public $comment;

  /**
   * The node promotion status.
   *
   * Promoted nodes should be displayed on the front page of the site. The value
   * is either NODE_PROMOTED or NODE_NOT_PROMOTED.
   *
   * @var integer
   */
  public $promote;

  /**
   * The node sticky status.
   *
   * Sticky nodes should be displayed at the top of lists in which they appear.
   * The value is either NODE_STICKY or NODE_NOT_STICKY.
   *
   * @var integer
   */
  public $sticky;

  /**
   * The node translation set ID.
   *
   * Translations sets are based on the ID of the node containing the source
   * text for the translation set.
   *
   * @var integer
   */
  public $tnid;

  /**
   * The node translation status.
   *
   * If the translation page needs to be updated, the value is 1; otherwise 0.
   *
   * @var integer
   */
  public $translate;

  /**
   * The node revision creation timestamp.
   *
   * @var integer
   */
  public $revision_timestamp;

  /**
   * The node revision author's user ID.
   *
   * @var integer
   */
  public $revision_uid;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->nid;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::bundle().
   */
  public function bundle() {
    return $this->type;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    $duplicate->vid = NULL;
    return $duplicate;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::getRevisionId().
   */
  public function getRevisionId() {
    return $this->vid;
  }
}
