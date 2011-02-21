<?php
// $Id: php.inc,v 1.7 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control based on evaluated PHP.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("PHP Code"),
  'description' => t('Control access through arbitrary PHP code.'),
  'callback' => 'ctools_php_ctools_access_check',
  'default' => array('description' => '', 'php' => ''),
  'settings form' => 'ctools_php_ctools_access_settings',
  'summary' => 'ctools_php_ctools_access_summary',
  'all contexts' => TRUE,
);

/**
 * Settings form for the 'by perm' access plugin
 *
 * @todo Need a way to provide a list of all available contexts to be used by
 *       the eval-ed PHP.
 */
function ctools_php_ctools_access_settings($form, &$form_state, $conf) {
  $perms = array();

  $form['settings']['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Administrative desc'),
    '#default_value' => $conf['description'],
    '#description' => t('A description for this test for administrative purposes.'),
  );
  $form['settings']['php'] = array(
    '#type' => 'textarea',
    '#title' => t('PHP Code'),
    '#default_value' => $conf['php'],
    '#description' =>  t('Access will be granted if the following PHP code returns <code>TRUE</code>. Do not include &lt;?php ?&gt;. Note that executing incorrect PHP-code can break your Drupal site. All contexts will be available in the <em>$contexts</em> variable.'),
  );
  if (!user_access('use PHP for settings')) {
    $form['settings']['php']['#disabled'] = TRUE;
    $form['settings']['php']['#value'] = $conf['php'];
    $form['settings']['php']['#description'] .= ' ' . t('You do not have sufficient permissions to edit PHP code.');
  }
  return $form;
}

/**
 * Check for access.
 */
function ctools_php_ctools_access_check($__conf, $contexts) {
  $access = eval($__conf['php']);
  return $access;
}

/**
 * Provide a summary description based upon the checked roles.
 */
function ctools_php_ctools_access_summary($conf, $contexts) {
  return !empty($conf['description']) ? check_plain($conf['description']) : t('No description');
}
