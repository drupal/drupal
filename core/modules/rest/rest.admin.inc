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
  $enabled_resources = config('rest.settings')->get('resources') ?: array();

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
  $resources = array_filter($form_state['values']['entity_resources']);
  if (!empty($form_state['values']['other_resources'])) {
    $resources += array_filter($form_state['values']['other_resources']);
  }

  $config = config('rest.settings');
  $config->set('resources', $resources);
  $config->save();

  // Rebuild routing cache.
  drupal_container()->get('router.builder')->rebuild();
}
