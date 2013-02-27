<?php

/**
 * @file
 * Admin pages for REST module.
 */

/**
 * Form constructor for the REST admin form.
 *
 * @ingroup forms
 */
function rest_admin_form($form, &$form_state) {
  $resources = drupal_container()
    ->get('plugin.manager.rest')
    ->getDefinitions();
  $entity_resources = array();
  $other_resources = array();
  foreach ($resources as $plugin_name => $definition) {
    // Non-entity resources.
    if (strpos($plugin_name, 'entity:') === FALSE) {
      $other_resources[$plugin_name] = array(
        'name' => $definition['label'],
        'path' => '<code>/' . $definition['id'] . '/{id}</code>',
      );
    }
    // Entity resources.
    else {
      $entity_resources[$plugin_name] = array(
        'name' => $definition['label'],
        'path' => '<code>/entity/' . $definition['entity_type'] . '/{id}</code>',
      );
    }
  }
  asort($entity_resources);
  asort($other_resources);
  $config = config('rest.settings')->get('resources') ?: array();
  // Strip out the nested method configuration, we are only interested in the
  // plugin IDs of the resources.
  $enabled_resources = drupal_map_assoc(array_keys($config));

  // Render the output using table_select().
  $header = array(
    'name' => t('Name'),
    'path' => t('Path'),
  );

  $form['entity_resources_title'] = array(
    '#markup' => '<h3>' . t('Entity resource types that should be exposed as web services') . '</h3>',
  );

  $form['entity_resources'] = array(
    '#type' => 'tableselect',
    '#js_select' => TRUE,
    '#multiple' => TRUE,
    '#header' => $header,
    '#options' => $entity_resources,
    '#default_value' => $enabled_resources,
    '#empty' => t('Nothing to show'),
  );

  if (!empty($other_resources)) {
    $form['other_resources_title'] = array(
      '#markup' => '<h3>' . t('Other available resource types that should be exposed as web services') . '</h3>',
    );

    $form['other_resources'] = array(
      '#type' => 'tableselect',
      '#js_select' => TRUE,
      '#multiple' => TRUE,
      '#header' => $header,
      '#options' => $other_resources,
      '#default_value' => $enabled_resources,
      '#empty' => t('Nothing to show'),
    );
  }

  return system_config_form($form, $form_state);
}

/**
 * Form submission handler for rest_admin_form().
 */
function rest_admin_form_submit($form, &$form_state) {
  $enabled_resources = array_filter($form_state['values']['entity_resources']);
  if (!empty($form_state['values']['other_resources'])) {
    $enabled_resources += array_filter($form_state['values']['other_resources']);
  }
  $resources = array();
  $plugin_manager = drupal_container()->get('plugin.manager.rest');

  // Enable all methods and all formats for each selected resource.
  foreach ($enabled_resources as $resource) {
    $plugin = $plugin_manager->getInstance(array('id' => $resource));
    $methods = $plugin->availableMethods();
    // An empty array means all formats are allowed for a method.
    $resources[$resource] = array_fill_keys($methods, array());
  }

  $config = config('rest.settings');
  $config->set('resources', $resources);
  $config->save();

  // Rebuild routing cache.
  drupal_container()->get('router.builder')->rebuild();
}
