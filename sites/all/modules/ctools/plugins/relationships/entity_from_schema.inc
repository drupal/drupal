<?php
// $Id: entity_from_schema.inc,v 1.1 2011/01/05 20:34:46 merlinofchaos Exp $

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
  'context' => 'ctools_entity_from_schema_context',
  'get child' => 'ctools_entity_from_schema_get_child',
  'get children' => 'ctools_entity_from_schema_get_children',
);

function ctools_entity_from_schema_get_child($plugin, $parent, $child) {
  $plugins = ctools_entity_from_schema_get_children($plugin, $parent);
  return $plugins[$parent . ':' . $child];
}

function ctools_entity_from_schema_get_children($plugin, $parent) {
  $entities = entity_get_info();
  $plugins = array();

  foreach (module_implements('entity_info') as $module) {
    module_load_install($module);
    $schemas = module_invoke($module, 'schema');
    foreach ($schemas as $table => $schema) {
      foreach ($entities as $from_entity => $from_entity_info) {
        if ($table == $from_entity_info['base table'] && isset($schema['foreign keys'])) {
          foreach ($schema['foreign keys'] as $relationship => $info) {
            foreach ($entities as $to_entity => $to_entity_info) {
              if ($info['table'] == $to_entity_info['base table'] && in_array($to_entity_info['entity keys']['id'], $info['columns'])) {
                $this_col = ctools_entity_from_schema_columns_filter($info['columns'], $to_entity_info['entity keys']['id']);
                $plugin['title'] = t('@to_entity @relationship relationship', array('@relationship' => $relationship, '@to_entity' => $to_entity));
                $plugin['keyword'] = $to_entity;
                $plugin['context name'] = $this_col . '-' . $from_entity . '-' . $to_entity;
                $plugin['name'] = $parent . ':' . $this_col . '-' . $from_entity . '-' . $to_entity;
                $plugin['description'] = t('Builds a @relationship relationship from a @from_entity to a @to_entity', array('@relationship' => $relationship, '@from_entity' => $from_entity, '@to_entity' => $to_entity));
                $plugin['required context'] = new ctools_context_required(t(ucfirst($from_entity_info['label'])), $from_entity);
                $plugins[$parent . ':' . $this_col . '-' . $from_entity . '-' . $to_entity] = $plugin;
              }
            }
          }
        }
      }
    }
  }
  return $plugins;
}

function ctools_entity_from_schema_columns_filter($columns, $value) {
  foreach ($columns as $this_col => $that_col) {
    if ($value == $that_col) {
      return $this_col;
    }
  }
}

/**
 * Return a new context based on an existing context.
 */
function ctools_entity_from_schema_context($context, $conf) {
  $plugin = $conf['name'];
  list($plugin, $plugin_name) = explode(':', $plugin);
  list($this_col, $from_entity, $to_entity) = explode('-', $plugin_name);
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
    if (isset($entity->$this_col)) {
      $to_entity_id = $entity->$this_col;

      // Send it to ctools.
      return ctools_context_create('entity:' . $to_entity, $to_entity_id);
    }
  }
}
