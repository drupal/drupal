<?php
// $Id: node_form_taxonomy.inc,v 1.4 2010/10/11 22:18:23 sdboyer Exp $

if (module_exists('taxonomy')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'icon' => 'icon_node_form.png',
    'title' => t('Node form categories'),
    'description' => t('Taxonomy categories for the node.'),
    'required context' => new ctools_context_required(t('Form'), 'node_form'),
    'category' => t('Form'),
  );
}

function ctools_node_form_taxonomy_content_type_render($subtype, $conf, $panel_args, &$context) {
  $block = new stdClass();
  $block->module = t('node_form');

      $block->title = t('Categories');
      $block->delta = 'url-path-options';

      if (isset($context->form)) {
        if (!empty($context->form['form_id']) && !empty($context->form['taxonomy'])) {
          // remove the fieldset
          unset($context->form['taxonomy']['#type']);
          $block->content = drupal_render($context->form['taxonomy']);
        }
      }
      else {
        $block->content = t('Categories.');
      }
  return $block;
}

function ctools_node_form_taxonomy_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" node form select taxonomy', array('@s' => $context->identifier));
}

function ctools_node_form_taxonomy_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
