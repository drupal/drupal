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
    if (strpos($plugin_name, 'entity:') === FALSE) {
      $other_resources[$plugin_name] = $definition['label'];
    }
    else {
      $entity_resources[$plugin_name] = $definition['label'];
    }
  }
  asort($entity_resources);
  asort($other_resources);
  $enabled_resources = config('rest')->get('resources') ?: array();

  $form['entity_resources'] = array(
    '#type' => 'checkboxes',
    '#options' => $entity_resources,
    '#default_value' => $enabled_resources,
    '#title' => t('Entity resource types that should be exposed as web services:'),
  );
  if (!empty($other_resources)) {
    $form['other_resources'] = array(
      '#type' => 'checkboxes',
      '#options' => $other_resources,
      '#default_value' => $enabled_resources,
      '#title' => t('Other available resource types that should be exposed as web services:'),
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

  $config = config('rest');
  $config->set('resources', $resources);
  $config->save();

  // Rebuild routing cache.
  drupal_container()->get('router.builder')->rebuild();
}
