<?php

namespace Drupal\block_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\block_content\BlockContentTypeInterface;

/**
 * Defines the custom block type entity.
 *
 * @ConfigEntityType(
 *   id = "block_content_type",
 *   label = @Translation("Custom block type"),
 *   label_collection = @Translation("Custom block library"),
 *   label_singular = @Translation("custom block type"),
 *   label_plural = @Translation("custom block types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count custom block type",
 *     plural = "@count custom block types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\block_content\BlockContentTypeForm",
 *       "add" = "Drupal\block_content\BlockContentTypeForm",
 *       "edit" = "Drupal\block_content\BlockContentTypeForm",
 *       "delete" = "Drupal\block_content\Form\BlockContentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "permissions" = "Drupal\user\Entity\EntityPermissionsRouteProviderWithCheck",
 *     },
 *     "list_builder" = "Drupal\block_content\BlockContentTypeListBuilder"
 *   },
 *   admin_permission = "administer blocks",
 *   config_prefix = "type",
 *   bundle_of = "block_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/block/block-content/manage/{block_content_type}/delete",
 *     "edit-form" = "/admin/structure/block/block-content/manage/{block_content_type}",
 *     "entity-permissions-form" = "/admin/structure/block/block-content/manage/{block_content_type}/permissions",
 *     "collection" = "/admin/structure/block/block-content/types",
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
   * The custom block type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The custom block type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The default revision setting for custom blocks of this type.
   *
   * @var bool
   */
  protected $revision;

  /**
   * The description of the block type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->revision;
  }

}
