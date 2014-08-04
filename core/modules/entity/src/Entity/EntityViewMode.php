<?php

/**
 * @file
 * Contains \Drupal\entity\Entity\EntityViewMode.
 */

namespace Drupal\entity\Entity;

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
 * @see \Drupal\Core\Entity\EntityManagerInterface::getAllViewModes()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getViewModes()
 * @see hook_entity_view_mode_info_alter()
 *
 * @ConfigEntityType(
 *   id = "view_mode",
 *   label = @Translation("View mode"),
 *   controllers = {
 *     "list_builder" = "Drupal\entity\EntityDisplayModeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity\Form\EntityDisplayModeAddForm",
 *       "edit" = "Drupal\entity\Form\EntityDisplayModeEditForm",
 *       "delete" = "Drupal\entity\Form\EntityDisplayModeDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer display modes",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "entity.view_mode.delete_form",
 *     "edit-form" = "entity.view_mode.edit_form"
 *   }
 * )
 */
class EntityViewMode extends EntityDisplayModeBase implements EntityViewModeInterface {

}
