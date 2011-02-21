<?php
// $Id: entity.inc,v 1.1 2011/01/05 20:34:46 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide a node context. A node context is a node wrapped in a
 * context object that can be utilized by anything that accepts contexts.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Entity"),
  'description' => t('Entity object.'),
  'context' => 'ctools_context_create_entity',
  'edit form' => 'ctools_context_entity_settings_form',
  'defaults' => array('id' => ''),
  'convert list' => 'ctools_context_entity_convert_list',
  'convert' => 'ctools_context_entity_convert',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the ID of an entity for this context.'),
  ),
  'get child' => 'ctools_context_entity_get_child',
  'get children' => 'ctools_context_entity_get_children',
);

function ctools_context_entity_get_child($plugin, $parent, $child) {
  $plugins = ctools_context_entity_get_children($plugin, $parent);
  return $plugins[$parent . ':' . $child];
}

function ctools_context_entity_get_children($plugin, $parent) {
  $entities = entity_get_info();
  $plugins = array();
  foreach ($entities as $entity_type => $entity) {
    $plugin['title'] = $entity['label'];
    $plugin['keyword'] = $entity_type;
    $plugin['context name'] = $entity_type;
    $plugin['name'] = $parent . ':' . $entity_type;
    $plugin['description'] = t('Creates @entity context from an entity ID.', array('@entity' => $entity_type));
    $plugins[$parent . ':' . $entity_type] = $plugin;
  }

  return $plugins;
}

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function ctools_context_create_entity($empty, $data = NULL, $conf = FALSE, $plugin) {
  $entity_type = $plugin['keyword'];
  $entity = entity_get_info($entity_type);
  $context = new ctools_context(array('entity:' . $entity_type, 'entity', $entity_type));
  $context->plugin = $plugin['name'];
  $context->keyword = $entity_type;

  if ($empty) {
    return $context;
  }

  if (is_array($data) && isset($data['id'])) {
    $id = $data['id'];
  }
  elseif (is_object($data)) {
    $ids = entity_extract_ids($entity_type, $data);
    $id = $ids[0];
  }
  elseif (is_numeric($data)) {
    $id = $data;
    $data = entity_load($entity_type, array($id));
    $data = $data[$id];
  }

  if (is_array($data)) {
    $data = entity_load($entity_type, array($id));
    $data = $data[$id];
    //$language = field_language($entity_type, $data);
  }

  if (!empty($data)) {
    $context->data     = $data;
    if (isset($entity['entity keys']['label'])) {
      $context->title    = $data->{$entity['entity keys']['label']};
    }
    $context->argument = $id;

    if ($entity['entity keys']['bundle']) {
      $context->restrictions['type'] = array($data->{$entity['entity keys']['bundle']});
    }
    return $context;
  }
}

function ctools_context_entity_settings_form($form, &$form_state) {
  $conf = &$form_state['conf'];

  $form['entity'] = array(
    '#title' => t('Enter the title or ID of a @entity entity', array('@entity' => $conf['keyword'])),
    '#type' => 'textfield',
    '#maxlength' => 512,
    '#autocomplete_path' => 'ctools/autocomplete/' . $conf['keyword'],
    '#weight' => -10,
  );

  if (!empty($conf['id'])) {
    $info = entity_load($conf['keyword'], array($conf['id']));
    $info = $info[$conf['id']];
    if ($info) {
      $entity = entity_get_info($conf['keyword']);
      $uri = entity_uri($conf['keyword'], $info);
      if (is_array($uri) && $entity['entity keys']['label']) {
        $link = l(t("'%title' [%type id %id]", array('%title' => $info->{$entity['entity keys']['label']}, '%type' => $conf['keyword'], '%id' => $conf['id'])), $uri['path'], array('attributes' => array('target' => '_blank', 'title' => t('Open in new window')), 'html' => TRUE));
      }
      elseif (is_array($uri)) {
        $link = l(t("[%type id %id]", array('%type' => $conf['keyword'], '%id' => $conf['id'])), $uri['path'], array('attributes' => array('target' => '_blank', 'title' => t('Open in new window')), 'html' => TRUE));
      }
      elseif ($entity['entity keys']['label']) {
        $link = l(t("'%title' [%type id %id]", array('%title' => $info->{$entity['entity keys']['label']}, '%type' => $conf['keyword'], '%id' => $conf['id'])), file_create_url($uri), array('attributes' => array('target' => '_blank', 'title' => t('Open in new window')), 'html' => TRUE));
      }
      else {
        $link = t("[%type id %id]", array('%type' => $conf['keyword'], '%id' => $conf['id']));
      }
      $form['entity']['#description'] = t('Currently set to !link', array('!link' => $link));
    }
  }

  $form['id'] = array(
    '#type' => 'value',
    '#value' => $conf['id'],
  );

  $form['entity_type'] = array(
    '#type' => 'value',
    '#value' => $conf['keyword'],
  );

  $form['set_identifier'] = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#title' => t('Reset identifier to entity label'),
    '#description' => t('If checked, the identifier will be reset to the entity label of the selected entity.'),
  );

  return $form;
}

/**
 * Validate a node.
 */
function ctools_context_entity_settings_form_validate($form, &$form_state) {
  // Validate the autocomplete
  if (empty($form_state['values']['id']) && empty($form_state['values']['entity'])) {
    form_error($form['entity'], t('You must select an entity.'));
    return;
  }

  if (empty($form_state['values']['entity'])) {
    return;
  }

  $id          = $form_state['values']['entity'];
  $preg_matches = array();
  $match        = preg_match('/\[id: (\d+)\]/', $id, $preg_matches);
  if (!$match) {
    $match = preg_match('/^id: (\d+)/', $id, $preg_matches);
  }

  if ($match) {
    $id = $preg_matches[1];
  }
  if (is_numeric($id)) {
    $entity = entity_load($form_state['values']['entity_type'], array($id));
    $entity = $entity[$id];
  }
  else {
    $entity_info = entity_get_info($form_state['values']['entity_type']);
    $field = $entity_info['entity keys']['label'];
    $entity = entity_load($form_state['values']['entity_type'], FALSE, array($field => $id));
  }

  // Do not allow unpublished nodes to be selected by unprivileged users
  // || (empty($node->status) && !(user_access('administer nodes'))) need a new sanity check at some point.
  if (!$entity) {
    form_error($form['entity'], t('Invalid entity selected.'));
  }
  else {
    $entity_id = entity_extract_ids($form_state['values']['entity_type'], $entity);
    form_set_value($form['id'], $entity_id[0], $form_state);
  }
}

function ctools_context_entity_settings_form_submit($form, &$form_state) {
  if ($form_state['values']['set_identifier']) {
    $entity_info = entity_get_info($form_state['values']['entity_type']);
    $entity = entity_load($form_state['values']['entity_type'], array($form_state['values']['id']));
    $entity = $entity[$form_state['values']['id']];
    $form_state['values']['identifier'] = $entity->{$entity_info['entity keys']['label']};
  }

  // This will either be the value set previously or a value set by the
  // validator.
  $form_state['conf']['id'] = $form_state['values']['id'];
}

/**
 * Provide a list of ways that this context can be converted to a string.
 */
function ctools_context_entity_convert_list($plugin) {
  $list = array();

  $entity = entity_get_info($plugin['context name']);
  if (isset($entity['token type'])) {
    $token = $entity['token type'];
  }
  else {
    $token = $plugin['context name'];
  }
  $tokens = token_info();
  if (isset($tokens['tokens'][$token])) {
    foreach ($tokens['tokens'][$token] as $id => $info) {
      if (!isset($list[$id])) {
        $list[$id] = $info['name'];
      }
    }
  }
  return $list;
}

/**
 * Convert a context into a string.
 */
function ctools_context_entity_convert($context, $type) {
  $entity_type = $context->type[2];
  $entity = entity_get_info($entity_type);
  if (isset($entity['token type'])) {
    $token = $entity['token type'];
  }
  else {
    $token = $entity_type;
  }
  $tokens = token_info();
  if (isset($tokens['tokens'][$token][$type])) {
    $values = token_generate($token, array($type => $type), array($token => $context->data));
    if (isset($values[$type])) {
      return $values[$type];
    }
  }
}
