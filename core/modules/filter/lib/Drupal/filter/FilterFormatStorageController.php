<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatStorageController.
 */

namespace Drupal\filter;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage controller class for Filter Format entities.
 */
class FilterFormatStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::preSave().
   */
  protected function preSave(EntityInterface $entity) {
    parent::preSave($entity);

    $entity->name = trim($entity->label());

    // @todo Do not save disabled filters whose properties are identical to
    //   all default properties.

    // Determine whether the format can be cached.
    // @todo This is a derived/computed definition, not configuration.
    $entity->cache = TRUE;
    foreach ($entity->filters() as $filter) {
      if ($filter->status && !$filter->cache) {
        $entity->cache = FALSE;
      }
    }
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    parent::postSave($entity, $update);

    // Clear the static caches of filter_formats() and others.
    filter_formats_reset();

    if ($update) {
      // Clear the filter cache whenever a text format is updated.
      cache('filter')->deleteTags(array('filter_format' => $entity->id()));
    }
    else {
      // Default configuration of modules and installation profiles is allowed
      // to specify a list of user roles to grant access to for the new format;
      // apply the defined user role permissions when a new format is inserted
      // and has a non-empty $roles property.
      // Note: user_role_change_permissions() triggers a call chain back into
      // filter_permission() and lastly filter_formats(), so its cache must be
      // reset upfront.
      if (($roles = $entity->get('roles')) && $permission = filter_permission_name($entity)) {
        foreach (user_roles() as $rid => $name) {
          $enabled = in_array($rid, $roles, TRUE);
          user_role_change_permissions($rid, array($permission => $enabled));
        }
      }
    }
  }

}
