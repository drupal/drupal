<?php

/**
 * @file
 * Hooks for the admin theme.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Register routes to apply admin’s content edit form layout.
 *
 * Leverage this hook to achieve a consistent user interface layout on
 * administrative edit forms, similar to the node edit forms. Any module
 * providing a custom entity type or form mode may wish to implement this
 * hook for their form routes. Note that not every content entity form route
 * should enable the admin edit form layout, for example the delete entity form
 * does not need it.
 *
 * @return array
 *   An array of route names.
 *
 * @see Helper::isContentForm()
 * @see hook_admin_content_form_routes_alter()
 */
function hook_admin_content_form_routes(): array {
  return [
    // Layout a custom node form.
    'entity.node.my_custom_form',

    // Layout a custom entity type edit form.
    'entity.my_type.edit_form',
  ];
}

/**
 * Alter the registered routes to enable or disable admin’s edit form layout.
 *
 * @param array $routes
 *   The list of routes.
 *
 * @see Helper::isContentForm()
 * @see hook_admin_content_form_routes()
 */
function hook_admin_content_form_routes_alter(array &$routes): void {
  // Example: disable admin edit form layout customizations for an entity type.
  $routes = array_diff($routes, ['entity.my_type.edit_form']);
}

/**
 * Register form IDs that should not use the content form.
 *
 * @return string[]
 *   The list of form IDs.
 */
function hook_admin_content_form_ignore_form_ids(): array {
  return [
    'media_library_add_form_',
    'views_form_media_library_widget_',
    'views_exposed_form',
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
