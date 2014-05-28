<?php

/**
 * @file
 * Contains \Drupal\entity\Entity\EntityFormMode.
 */

namespace Drupal\entity\Entity;

use Drupal\entity\EntityDisplayModeBase;
use Drupal\entity\EntityFormModeInterface;

/**
 * Defines the form mode configuration entity class.
 *
 * Form modes allow entity forms to be displayed differently depending on the
 * context. For instance, the user entity form can be displayed with a set of
 * fields on the 'profile' page (user edit page) and with a different set of
 * fields (or settings) on the user registration page. Modules taking part in
 * the display of the entity form (notably the Field API) can adjust their
 * behavior depending on the requested form mode. An additional 'default' form
 * mode is available for all entity types. For each available form mode,
 * administrators can configure whether it should use its own set of field
 * display settings, or just replicate the settings of the 'default' form mode,
 * thus reducing the amount of form display configurations to keep track of.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface::getAllFormModes()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getFormModes()
 * @see hook_entity_form_mode_info_alter()
 *
 * @ConfigEntityType(
 *   id = "form_mode",
 *   label = @Translation("Form mode"),
 *   controllers = {
 *     "list_builder" = "Drupal\entity\EntityFormModeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity\Form\EntityFormModeAddForm",
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
 *     "delete-form" = "entity.form_mode_delete",
 *     "edit-form" = "entity.form_mode_edit"
 *   }
 * )
 */
class EntityFormMode extends EntityDisplayModeBase implements EntityFormModeInterface {

}
