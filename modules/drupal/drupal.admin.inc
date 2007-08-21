<?php
// $Id: drupal.admin.inc,v 1.1 2007/08/21 08:15:59 dries Exp $

/**
 * @file
 * Admin page callbacks for the drupal module.
 */

/**
 * Form builder; Configure what sites this site can authenticate with.
 *
 * @ingroup forms
 * @see system_settings_form().
 */
function drupal_sites_registry_settings() {
  // Check if all required fields are present
  if ((variable_get('site_name', 'Drupal') == 'Drupal') || (variable_get('site_name', 'Drupal') == '')) {
    form_set_error('drupal_directory', t('You must set the name of your site on the <a href="@url">administer &raquo; settings &raquo; site information</a> page.', array('@url' => url('admin/settings/site-information'))));
  }
  else if (variable_get('site_mail', ini_get('sendmail_from')) == '') {
    form_set_error('drupal_directory', t('You must set an e-mail address for your site on the <a href="@url">site information settings page</a>.', array('@url' => url('admin/settings/site-information'))));
  }
  else if (variable_get('site_slogan', '') == '') {
    form_set_error('drupal_directory', t('You must set your site slogan on the <a href="@url">site information settings page</a>.', array('@url' => url('admin/settings/site-information'))));
  }
  else if (variable_get('site_mission', '') == '') {
    form_set_error('drupal_directory', t('You must set your site mission on the <a href="@url">site information settings page</a>.' , array('@url' => url('admin/settings/site-information'))));
  }

  $options = array('1' => t('Enabled'), '0' => t('Disabled'));

  $form['drupal_register'] = array(
    '#type' => 'radios',
    '#title' => t('Register with a Drupal server'),
    '#default_value' => variable_get('drupal_register', 0),
    '#options' => $options,
    '#description' => t("If enabled, your Drupal site will register itself with the specified Drupal XML-RPC server. For this to work properly, you must set your site's name, e-mail address, slogan and mission statement. When the Drupal XML-RPC server field is set to %drupal-xml-rpc, your website will register itself with drupal.org. Requires the cron feature to be enabled.", array("%drupal-xml-rpc" => "http://drupal.org/xmlrpc.php"))
  );

  $form['drupal_server'] =  array(
    '#type' => 'textfield',
    '#title' => t('Drupal XML-RPC server'),
    '#default_value' => variable_get('drupal_server', 'http://drupal.org/xmlrpc.php'),
    '#description' => t('The URL of the Drupal XML-RPC server you wish to register with.')
  );

  $form['drupal_system'] = array(
    '#type' => 'radios',
    '#title' => t('Send system information'),
    '#default_value' => variable_get('drupal_system', 0),
    '#options' => $options,
    '#description' => t("If enabled, your site will send information on its installed components (modules, themes, and theme engines). This information can help in compiling statistics on usage of Drupal projects.")
  );

  $form['drupal_statistics'] = array(
    '#type' => 'radios',
    '#title' => t('Send statistics'),
    '#default_value' => variable_get('drupal_statistics', 0),
    '#options' => $options,
    '#description' => t("If enabled, your site will send summary statistics on the number of registered users and the total number of posts. No private information will be sent. These data help to improve the ranking statistics of Drupal projects.")
  );

  $form['drupal_client_service'] = array(
    '#type' => 'radios',
    '#title' => t('Allow other Drupal sites to register'),
    '#default_value' => variable_get('drupal_client_service', 0),
    '#options' => $options,
    '#description' => t('If enabled, your Drupal site will allow other sites to register with your site and send information to this site. This functionality can be used to maintain a list of related sites.')
  );

  return system_settings_form($form);
}

/**
 * Form builder; Configure how distributed authentication will work.
 *
 * @ingroup forms
 * @see system_settings_form().
 */
function drupal_distributed_authentication_settings() {

  $options = array('1' => t('Enabled'), '0' => t('Disabled'));

  $form['drupal_authentication_service'] = array(
    '#type' => 'radios',
    '#title' => t('Authentication service'),
    '#default_value' => variable_get('drupal_authentication_service', 0),
    '#options' => $options,
    '#description' => t('If enabled, your Drupal site will accept logins with the user names of other Drupal sites, and likewise provide authentication for users logging into other Drupal sites, based on their user accounts here.')
  );

  $form['drupal_default_da_server'] =  array(
    '#type' => 'textfield',
    '#title' => t('Default authentication server'),
    '#default_value' => variable_get('drupal_default_da_server', ''),
    '#description' => t('The URL of the default Drupal authentication server. Omit the %http prefix (e.g. drupal.org, www.example.com, etc.). If the authentication service has been enabled, users registered at the server specified here, will not need to append the server to their user name when logging into your site. This enables users to provide a briefer, more familiar username in the login form.', array('%http' => 'http'))
  );

  $form['drupal_default_da_server_only'] = array(
    '#type' => 'radios',
    '#title' => t('Only allow authentication from default server'),
    '#default_value' => variable_get('drupal_default_da_server_only', 0),
    '#options' => $options,
    '#description' => t("Only accept remote logins from the above specified default authentication server and not from any other server. Useful when an external system is the solitary authority on user accounts for this site. A common usage is to enable this setting and also enable an authentication module which talks to your company's directory server.")
  );

  return system_settings_form($form);
}
