<?php

/**
 * @file
 * Contains \Drupal\entity\Plugin\Core\Entity\EntityViewMode.
 */

namespace Drupal\entity\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\entity\EntityDisplayModeBase;
use Drupal\entity\EntityViewModeInterface;

/**
 * Defines the view mode configuration entity class.
 *
 * View modes let entities be displayed differently depending on the context.
 * For instance, a node can be displayed differently on its own page ('full'
 * mode), on the home page or taxonomy listings ('teaser' mode), or in an RSS
 * feed ('rss' mode). Modules taking part in the display of the entity (notably
 * the Field API) can adjust their behavior depending on the requested view
 * mode. An additional 'default' view mode is available for all entity types.
 * This view mode is not intended for actual entity display, but holds default
 * display settings. For each available view mode, administrators can configure
 * whether it should use its own set of field display settings, or just
 * replicate the settings of the 'default' view mode, thus reducing the amount
 * of display configurations to keep track of.
 *
 * @see entity_get_view_modes()
 * @see hook_entity_view_mode_info_alter()
 *
 * @EntityType(
 *   id = "view_mode",
 *   label = @Translation("View mode"),
 *   module = "entity",
 *   controllers = {
 *     "list" = "Drupal\entity\EntityDisplayModeListController",
 *     "access" = "Drupal\entity\EntityDisplayModeAccessController",
 *     "form" = {
 *       "add" = "Drupal\entity\Form\EntityDisplayModeAddForm",
 *       "edit" = "Drupal\entity\Form\EntityDisplayModeEditForm",
 *       "delete" = "Drupal\entity\Form\EntityDisplayModeDeleteForm"
 *     },
 *     "storage" = "Drupal\entity\EntityDisplayModeStorageController"
 *   },
 *   config_prefix = "entity.view_mode",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class EntityViewMode extends EntityDisplayModeBase implements EntityViewModeInterface {

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
      'path' => 'admin/structure/display-modes/view/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

}
