<?php

/**
 * @file
 * Hooks provided by the Configuration Translation module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Introduce dynamic translation tabs for translation of configuration.
 *
 * This hook augments MODULE.config_translation.yml as well as
 * THEME.config_translation.yml files to collect dynamic translation mapper
 * information. If your information is static, just provide such a YAML file
 * with your module containing the mapping.
 *
 * Note that while themes can provide THEME.config_translation.yml files this
 * hook is not invoked for themes.
 *
 * @param array $info
 *   An associative array of configuration mapper information. Use an entity
 *   name for the key (for entity mapping) or a unique string for configuration
 *   name list mapping. The values of the associative array are arrays
 *   themselves in the same structure as the *.config_translation.yml files.
 *
 * @see hook_config_translation_info_alter()
 * @see \Drupal\config_translation\ConfigMapperManagerInterface
 * @see \Drupal\config_translation\Routing\RouteSubscriber::routes()
 */
function hook_config_translation_info(&$info) {
  $entity_manager = \Drupal::entityManager();
  $route_provider = \Drupal::service('router.route_provider');

  // If field UI is not enabled, the base routes of the type
  // "entity.field_config.{$entity_type}_field_edit_form" are not defined.
  if (\Drupal::moduleHandler()->moduleExists('field_ui')) {
    // Add fields entity mappers to all fieldable entity types defined.
    foreach ($entity_manager->getDefinitions() as $entity_type_id => $entity_type) {
      $base_route = NULL;
      try {
        $base_route = $route_provider->getRouteByName('entity.field_config.' . $entity_type_id . '_field_edit_form');
      }
      catch (RouteNotFoundException $e) {
        // Ignore non-existent routes.
      }

      // Make sure entity type has field UI enabled and has a base route.
      if ($entity_type->get('field_ui_base_route') && !empty($base_route)) {
        $info[$entity_type_id . '_fields'] = array(
          'base_route_name' => 'entity.field_config.' . $entity_type_id . '_field_edit_form',
          'entity_type' => 'field_config',
          'title' => t('Title'),
          'class' => '\Drupal\config_translation\ConfigFieldMapper',
          'base_entity_type' => $entity_type_id,
          'weight' => 10,
        );
      }
    }
  }
}

/**
 * Alter existing translation tabs for translation of configuration.
 *
 * This hook is useful to extend existing configuration mappers with new
 * configuration names, for example when altering existing forms with new
 * settings stored elsewhere. This allows the translation experience to also
 * reflect the compound form element in one screen.
 *
 * @param array $info
 *   An associative array of discovered configuration mappers. Use an entity
 *   name for the key (for entity mapping) or a unique string for configuration
 *   name list mapping. The values of the associative array are arrays
 *   themselves in the same structure as the *.config_translation.yml files.
 *
 * @see hook_translation_info()
 * @see \Drupal\config_translation\ConfigMapperManagerInterface
 */
function hook_config_translation_info_alter(&$info) {
  // Add additional site settings to the site information screen, so it shows
  // up on the translation screen. (Form alter in the elements whose values are
  // stored in this config file using regular form altering on the original
  // configuration form.)
  $info['system.site_information_settings']['names'][] = 'example.site.setting';
}

/**
 * @} End of "addtogroup hooks".
 */
