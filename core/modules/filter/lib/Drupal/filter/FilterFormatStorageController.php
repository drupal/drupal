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
    $entity->cache = _filter_format_is_cacheable($entity);
    $filter_info = filter_get_filters();
    foreach ($filter_info as $name => $filter) {
      // Merge the actual filter definition into the filter default definition.
      $defaults = array(
        'module' => $filter['module'],
        // The filter ID has to be temporarily injected into the properties, in
        // order to sort all filters below.
        // @todo Rethink filter sorting to remove dependency on filter IDs.
        'name' => $name,
        // Unless explicitly enabled, all filters are disabled by default.
        'status' => 0,
        // If no explicit weight was defined for a filter, assign either the
        // default weight defined in hook_filter_info() or the default of 0 by
        // filter_get_filters().
        'weight' => $filter['weight'],
        'settings' => $filter['default settings'],
      );
      // All available filters are saved for each format, in order to retain all
      // filter properties regardless of whether a filter is currently enabled
      // or not, since some filters require extensive configuration.
      // @todo Do not save disabled filters whose properties are identical to
      //   all default properties.
      if (isset($entity->filters[$name])) {
        $entity->filters[$name] = array_merge($defaults, $entity->filters[$name]);
      }
      else {
        $entity->filters[$name] = $defaults;
      }
      // The module definition from hook_filter_info() always takes precedence
      // and needs to be updated in case it changes.
      $entity->filters[$name]['module'] = $filter['module'];
    }

    // Sort all filters.
    uasort($entity->filters, 'Drupal\filter\Plugin\Core\Entity\FilterFormat::sortFilters');
    // Remove the 'name' property from all filters that was added above.
    foreach ($entity->filters as &$filter) {
      unset($filter['name']);
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
