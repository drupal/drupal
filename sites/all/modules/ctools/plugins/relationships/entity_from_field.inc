<?php
// $Id: entity_from_field.inc,v 1.1 2011/01/05 20:34:46 merlinofchaos Exp $

/**
 * @file
 * Plugin to provide an relationship handler for an entity from a field.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Entity'),
  'description' => t('Creates an entity context from a foreign key on a field.'),
  'context' => 'ctools_entity_from_field_context',
  'get child' => 'ctools_entity_from_field_get_child',
  'get children' => 'ctools_entity_from_field_get_children',
);

function ctools_entity_from_field_get_child($plugin, $parent, $child) {
  $plugins = ctools_entity_from_field_get_children($plugin, $parent);
  return $plugins[$parent . ':' . $child];
}

function ctools_entity_from_field_get_children($plugin, $parent) {
  $entities = entity_get_info();
  $plugins = array();
  $context_types = array();

  // Get the schema information for every field.
  $fields_info = field_info_instances();
  foreach ($fields_info as $entity_type => $entity_info) {
    foreach ($entity_info as $bundle => $fields) {
      foreach ($fields as $field_name => $field) {
        $field = field_info_field($field_name);
        $module = $field['module'];
        module_load_install($module);
        $schema = module_invoke($module, 'field_schema', $field);
        if (isset($schema['foreign keys'])) {
          foreach ($schema['foreign keys'] as $key => $info) {
            if (isset($info['table'])) {
              foreach ($entities as $entity => $einfo) {
                if ($einfo['base table'] == $info['table'] && isset($info['columns'][$einfo['entity keys']['id']])) {
                  $plugin['title'] = t('@field_entity from @entity on @field_name field', array('@field_entity' => $einfo['label'], '@entity' => $entities[$entity_type]['label'], '@field_name' => $field_name));
                  $plugin['keyword'] = $entity;
                  $plugin['context name'] = $field_name . '-' . $entity_type . '-' . $entity;
                  $plugin['name'] = $parent . ':' . $field_name . '-' . $entity_type . '-' . $entity;
                  $plugin['description'] = t('Creates a @entity context from @base_entity field relationship on the @base_table table.', array('@entity' => $entity, '@base_entity' => $entity_type, '@base_table' => $einfo['base table']));
                  $context_types[$parent . ':' . $field_name . '-' . $entity_type . '-' . $entity]['types'][$bundle] = $entities[$entity_type]['bundles'][$bundle]['label'];
                  $plugins[$parent . ':' . $field_name . '-' . $entity_type . '-' . $entity] = $plugin;
                }
              }
            }
          }
        }
      }
    }
  }
  foreach ($context_types as $key => $context) {
    list($parent, $plugin_name) = explode(':', $key);
    list($field_name, $from_entity) = explode('-', $plugin_name);
    $plugins[$key]['required context'] = new ctools_context_required(t(ucfirst($from_entity)), $from_entity, array('type' => array_keys($context['types'])));
  }
  return $plugins;
}

/**
 * Return a new context based on an existing context.
 */
function ctools_entity_from_field_context($context, $conf) {
  $plugin = $conf['name'];
  list($plugin, $plugin_name) = explode(':', $plugin);
  list($field_name, $from_entity, $to_entity) = explode('-', $plugin_name);
  // If unset it wants a generic, unfilled context, which is just NULL.
  $entity_info = entity_get_info($from_entity);
  if (empty($context->data) || !isset($context->data->{$entity_info['entity keys']['id']})) {
    return ctools_context_create_empty('entity:' . $to_entity, NULL);
  }

  if (isset($context->data->{$entity_info['entity keys']['id']})) {
    // Load the entity.
    $id = $context->data->{$entity_info['entity keys']['id']};
    $entity = entity_load($from_entity, array($id));
    $entity = $entity[$id];
    if (isset($entity->$field_name)) {
      $language = field_language($from_entity, $entity, $field_name);
      $to_entity_info = entity_get_info($to_entity);
      $to_entity_id = $entity->{$field_name}[$language][0][$to_entity_info['entity keys']['id']];

      // Send it to ctools.
      return ctools_context_create('entity:' . $to_entity, $to_entity_id);
    }
  }
}
