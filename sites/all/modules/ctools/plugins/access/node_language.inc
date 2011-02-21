<?php
// $Id: node_language.inc,v 1.11 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control based upon node type.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
if (module_exists('locale')) {
  $plugin = array(
    'title' => t("Node: language"),
    'description' => t('Control access by node language.'),
    'callback' => 'ctools_node_language_ctools_access_check',
    'default' => array('language' => array()),
    'settings form' => 'ctools_node_language_ctools_access_settings',
    'settings form submit' => 'ctools_node_language_ctools_access_settings_submit',
    'summary' => 'ctools_node_language_ctools_access_summary',
    'required context' => new ctools_context_required(t('Node'), 'node'),
  );
}

/**
 * Settings form for the 'by node_language' access plugin
 */
function ctools_node_language_ctools_access_settings($form, &$form_state, $conf) {
  $options = array(
    'current' => t('Current site language'),
    'default' => t('Default site language'),
    'no_language' => t('No language'),
  );
  $options = array_merge($options, locale_language_list());
  $form['settings']['language'] = array(
    '#title' => t('Language'),
    '#type' => 'checkboxes',
    '#options' => $options,
    '#description' => t('Pass only if the node is in one of the selected languages.'),
    '#default_value' => $conf['language'],
  );
  return $form;
}

/**
 * Check for access.
 */
function ctools_node_language_ctools_access_check($conf, $context) {
  // As far as I know there should always be a context at this point, but this
  // is safe.
  if (empty($context) || empty($context->data) || !isset($context->data->language)) {
    return FALSE;
  }

  global $language;

  // Specialcase: if 'no language' is checked, return TRUE if the language field is
  // empty.
  if (!empty($conf['language']['no_language'])) {
    if (empty($context->data->language)) {
      return TRUE;
    }
  }

  // Specialcase: if 'current' is checked, return TRUE if the current site language
  // matches the node language.
  if (!empty($conf['language']['current'])) {
    if ($context->data->language == $language->language) {
      return TRUE;
    }
  }

  // Specialcase: If 'default' is checked, return TRUE if the default site language
  // matches the node language.
  if (!empty($conf['language']['default'])) {
    if ($context->data->language == language_default('language')) {
      return TRUE;
    }
  }

  if (array_filter($conf['language']) && empty($conf['language'][$context->data->language])) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Provide a summary description based upon the checked node_languages.
 */
function ctools_node_language_ctools_access_summary($conf, $context) {
  $languages = array(
    'current' => t('Current site language'),
    'default' => t('Default site language'),
    'no_language' => t('No language'),
  );
  $languages = array_merge($languages, locale_language_list());

  if (!isset($conf['language'])) {
    $conf['language'] = array();
  }

  $names = array();
  foreach (array_filter($conf['language']) as $language) {
    $names[] = $languages[$language];
  }

  if (empty($names)) {
    return t('@identifier is in any language', array('@identifier' => $context->identifier));
  }

  return format_plural(count($names), '@identifier language is "@languages"', '@identifier language is one of "@languages"', array('@languages' => implode(', ', $names), '@identifier' => $context->identifier));
}

