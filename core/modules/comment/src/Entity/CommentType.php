<?php

namespace Drupal\comment\Entity;

use Drupal\comment\CommentTypeForm;
use Drupal\comment\CommentTypeListBuilder;
use Drupal\comment\Form\CommentTypeDeleteForm;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\comment\CommentTypeInterface;
use Drupal\user\Entity\EntityPermissionsRouteProvider;

/**
 * Defines the comment type entity.
 */
#[ConfigEntityType(
  id: 'comment_type',
  label: new TranslatableMarkup('Comment type'),
  label_singular: new TranslatableMarkup('comment type'),
  label_plural: new TranslatableMarkup('comment types'),
  config_prefix: 'type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'form' => [
      'default' => CommentTypeForm::class,
      'add' => CommentTypeForm::class,
      'edit' => CommentTypeForm::class,
      'delete' => CommentTypeDeleteForm::class,
    ],
    'route_provider' => [
      'permissions' => EntityPermissionsRouteProvider::class,
    ],
    'list_builder' => CommentTypeListBuilder::class,
  ],
  links: [
    'delete-form' => '/admin/structure/comment/manage/{comment_type}/delete',
    'edit-form' => '/admin/structure/comment/manage/{comment_type}',
    'add-form' => '/admin/structure/comment/types/add',
    'entity-permissions-form' => '/admin/structure/comment/manage/{comment_type}/permissions',
    'collection' => '/admin/structure/comment',
  ],
  admin_permission: 'administer comment types',
  bundle_of: 'comment',
  label_count: [
    'singular' => '@count comment type',
    'plural' => '@count comment types',
  ],
  config_export: [
    'id',
    'label',
    'target_entity_type_id',
    'description',
  ],
)]
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
