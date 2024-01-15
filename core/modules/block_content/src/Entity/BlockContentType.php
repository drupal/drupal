<?php

namespace Drupal\block_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\block_content\BlockContentTypeInterface;

/**
 * Defines the block type entity.
 *
 * @ConfigEntityType(
 *   id = "block_content_type",
 *   label = @Translation("Block type"),
 *   label_collection = @Translation("Block types"),
 *   label_singular = @Translation("block type"),
 *   label_plural = @Translation("block types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count block type",
 *     plural = "@count block types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\block_content\BlockTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\block_content\BlockContentTypeForm",
 *       "add" = "Drupal\block_content\BlockContentTypeForm",
 *       "edit" = "Drupal\block_content\BlockContentTypeForm",
 *       "delete" = "Drupal\block_content\Form\BlockContentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "permissions" = "Drupal\user\Entity\EntityPermissionsRouteProvider",
 *     },
 *     "list_builder" = "Drupal\block_content\BlockContentTypeListBuilder"
 *   },
 *   admin_permission = "administer block types",
 *   config_prefix = "type",
 *   bundle_of = "block_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/block-content/manage/{block_content_type}/delete",
 *     "edit-form" = "/admin/structure/block-content/manage/{block_content_type}",
 *     "entity-permissions-form" = "/admin/structure/block-content/manage/{block_content_type}/permissions",
 *     "collection" = "/admin/structure/block-content",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "revision",
 *     "description",
 *   }
 * )
 */
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
