<?php

/**
 * @file
 * Contains \Drupal\comment\Entity\CommentType.
 */

namespace Drupal\comment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\comment\CommentTypeInterface;

/**
 * Defines the comment type entity.
 *
 * @ConfigEntityType(
 *   id = "comment_type",
 *   label = @Translation("Comment type"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\comment\CommentTypeForm",
 *       "add" = "Drupal\comment\CommentTypeForm",
 *       "edit" = "Drupal\comment\CommentTypeForm",
 *       "delete" = "Drupal\comment\Form\CommentTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\comment\CommentTypeListBuilder"
 *   },
 *   admin_permission = "administer comment types",
 *   config_prefix = "type",
 *   bundle_of = "comment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/comment/manage/{comment_type}/delete",
 *     "edit-form" = "/admin/structure/comment/manage/{comment_type}",
 *     "add-form" = "/admin/structure/comment/types/add"
 *   }
 * )
 */
class CommentType extends ConfigEntityBundleBase implements CommentTypeInterface {

  /**
   * The comment type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The comment type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the comment type.
   *
   * @var string
   */
  protected $description;

  /**
   * The target entity type.
   *
   * @var string
   */
  protected $target_entity_type_id;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->target_entity_type_id;
  }

}
