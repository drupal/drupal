<?php

namespace Drupal\block_content\Entity;

use Drupal\block_content\BlockContentTypeForm;
use Drupal\block_content\BlockContentTypeListBuilder;
use Drupal\block_content\BlockTypeAccessControlHandler;
use Drupal\block_content\Form\BlockContentTypeDeleteForm;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\block_content\BlockContentTypeInterface;
use Drupal\user\Entity\EntityPermissionsRouteProvider;

/**
 * Defines the block type entity.
 */
#[ConfigEntityType(
  id: 'block_content_type',
  label: new TranslatableMarkup('Block type'),
  label_collection: new TranslatableMarkup('Block types'),
  label_singular: new TranslatableMarkup('block type'),
  label_plural: new TranslatableMarkup('block types'),
  config_prefix: 'type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'access' => BlockTypeAccessControlHandler::class,
    'form' => [
      'default' => BlockContentTypeForm::class,
      'add' => BlockContentTypeForm::class,
      'edit' => BlockContentTypeForm::class,
      'delete' => BlockContentTypeDeleteForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
      'permissions' => EntityPermissionsRouteProvider::class,
    ],
    'list_builder' => BlockContentTypeListBuilder::class,
  ],
  links: [
    'delete-form' => '/admin/structure/block-content/manage/{block_content_type}/delete',
    'edit-form' => '/admin/structure/block-content/manage/{block_content_type}',
    'entity-permissions-form' => '/admin/structure/block-content/manage/{block_content_type}/permissions',
    'collection' => '/admin/structure/block-content',
  ],
  admin_permission: 'administer block types',
  bundle_of: 'block_content',
  label_count: [
    'singular' => '@count block type',
    'plural' => '@count block types',
  ],
  config_export: [
    'id',
    'label',
    'revision',
    'description',
  ],
)]
class BlockContentType extends ConfigEntityBundleBase implements BlockContentTypeInterface {

  /**
   * The block type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The block type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The default revision setting for content blocks of this type.
   *
   * @var bool
   */
  protected $revision = FALSE;

  /**
   * The description of the block type.
   *
   * @var string|null
   */
  protected $description = NULL;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->revision;
  }

}
