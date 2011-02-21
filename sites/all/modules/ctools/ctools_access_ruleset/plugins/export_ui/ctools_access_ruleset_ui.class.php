<?php
// $Id: ctools_access_ruleset_ui.class.php,v 1.2 2010/10/11 22:18:23 sdboyer Exp $

class ctools_access_ruleset_ui extends ctools_export_ui {

  function edit_form_context(&$form, &$form_state) {
    ctools_include('context-admin');
    ctools_context_admin_includes();
    ctools_add_css('ruleset');

    $form['right'] = array(
      '#prefix' => '<div class="ctools-right-container">',
      '#suffix' => '</div>',
    );

    $form['left'] = array(
      '#prefix' => '<div class="ctools-left-container clearfix">',
      '#suffix' => '</div>',
    );

    // Set this up and we can use CTools' Export UI's built in wizard caching,
    // which already has callbacks for the context cache under this name.
    $module = 'export_ui::' . $this->plugin['name'];
    $name = $this->edit_cache_get_key($form_state['item'], $form_state['form type']);

    ctools_context_add_context_form($module, $form, $form_state, $form['right']['contexts_table'], $form_state['item'], $name);
    ctools_context_add_required_context_form($module, $form, $form_state, $form['left']['required_contexts_table'], $form_state['item'], $name);
    ctools_context_add_relationship_form($module, $form, $form_state, $form['right']['relationships_table'], $form_state['item'], $name);
  }

  function edit_form_rules(&$form, &$form_state) {
    // The 'access' UI passes everything via $form_state, unlike the 'context' UI.
    // The main difference is that one is about 3 years newer than the other.
    ctools_include('context');
    ctools_include('context-access-admin');

    $form_state['access'] = $form_state['item']->access;
    $form_state['contexts'] = ctools_context_load_contexts($form_state['item']);

    $form_state['module'] = 'ctools_export_ui';
    $form_state['callback argument'] = $form_state['object']->plugin['name'] . ':' . $form_state['object']->edit_cache_get_key($form_state['item'], $form_state['form type']);
    $form_state['no buttons'] = TRUE;

    $form = ctools_access_admin_form($form, $form_state);
  }

  function edit_form_rules_submit(&$form, &$form_state) {
    $form_state['item']->access['logic'] = $form_state['values']['logic'];
  }

  function edit_form_submit(&$form, &$form_state) {
    parent::edit_form_submit($form, $form_state);
  }
}
