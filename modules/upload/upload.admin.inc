<?php
// $Id$

/**
 * @file
 * This file creates the administration form for the upload module.
 */

/**
 * Form API callback to validate the upload settings form.
 */
function upload_admin_settings_validate($form, &$form_state) {
  if (!is_numeric($form_state['values']['upload_max_resolution_x'])) {
    form_set_error('upload_max_resolution_x', t('The maximum allowed image width should be entered as a numeric value. Set to 0 for no restriction.'));
  }
  if (!is_numeric($form_state['values']['upload_max_resolution_y'])) {
    form_set_error('upload_max_resolution_y', t('The maximum allowed image height should be entered as a numeric value. Set to 0 for no restriction.'));
  }

  $default_uploadsize = $form_state['values']['upload_uploadsize_default'];
  $default_usersize = $form_state['values']['upload_usersize_default'];

  $exceed_max_msg = t('Your PHP settings limit the maximum file size per upload to %size.', array('%size' => format_size(file_upload_max_size()))) . '<br/>';
  $more_info = t("Depending on your server environment, these settings may be changed in the system-wide php.ini file, a php.ini file in your Drupal root directory, in your Drupal site's settings.php file, or in the .htaccess file in your Drupal root directory.");

  if (!is_numeric($default_uploadsize) || ($default_uploadsize <= 0)) {
    form_set_error('upload_uploadsize_default', t('The %role file size limit must be a number and greater than zero.', array('%role' => t('default'))));
  }
  if (!is_numeric($default_usersize) || ($default_usersize < 0)) {
    form_set_error('upload_usersize_default', t('The %role file size limit must be a number zero or greater.', array('%role' => t('default'))));
  }
  if ($default_uploadsize * 1024 * 1024 > file_upload_max_size()) {
    form_set_error('upload_uploadsize_default', $exceed_max_msg . $more_info);
    $more_info = '';
  }
  if ($default_usersize && $default_uploadsize > $default_usersize) {
    form_set_error('upload_uploadsize_default', t('The %role maximum file size per upload is greater than the total file size allowed per user', array('%role' => t('default'))));
  }

  foreach ($form_state['values']['roles'] as $rid => $role) {
    $uploadsize = $form_state['values']['upload_uploadsize_' . $rid];
    $usersize = $form_state['values']['upload_usersize_' . $rid];

    if (!is_numeric($uploadsize) || ($uploadsize <= 0)) {
      form_set_error('upload_uploadsize_' . $rid, t('The %role file size limit must be a number and greater than zero.', array('%role' => $role)));
    }
    if (!is_numeric($usersize) || ($usersize < 0)) {
      form_set_error('upload_usersize_' . $rid, t('The %role file size limit must be a number zero or greater.', array('%role' => $role)));
    }
    if ($uploadsize * 1024 * 1024 > file_upload_max_size()) {
      form_set_error('upload_uploadsize_' . $rid, $exceed_max_msg . $more_info);
      $more_info = '';
    }
    if ($usersize && $uploadsize > $usersize) {
      form_set_error('upload_uploadsize_' . $rid, t('The %role maximum file size per upload is greater than the total file size allowed per user', array('%role' => $role)));
    }
  }
}

/**
 * Menu callback for the upload settings form.
 */
function upload_admin_settings() {
  $upload_extensions_default = variable_get('upload_extensions_default', 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp');
  $upload_uploadsize_default = variable_get('upload_uploadsize_default', 1);
  $upload_usersize_default = variable_get('upload_usersize_default', 1);

  $form['settings_general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General settings'),
    '#collapsible' => TRUE,
    '#attached_css' => array(
      drupal_get_path('module', 'upload') . '/upload.admin.css',
    ),
  );
  $form['settings_general']['upload_max_resolution'] = array(
    '#type' => 'item',
    '#title' => t('Maximum resolution for uploaded images'),
    '#description' => t('The maximum allowed image size (e.g. 640x480). Set to 0x0 for no restriction. If an <a href="!image-toolkit-link">image toolkit</a> is installed, files exceeding this value will be scaled down to fit.', array('!image-toolkit-link' => url('admin/config/media/image-toolkit'))),
    '#prefix' => '<div class="form-item-wrapper form-item-resolution">',
    '#suffix' => '</div>',
  );
  $form['settings_general']['upload_max_resolution']['upload_max_resolution_x'] = array(
    '#type' => 'textfield',
    '#title' => t('Width'),
    '#default_value' => variable_get('upload_max_resolution_x', 0),
    '#size' => 5,
    '#maxlength' => 5,
    '#field_suffix' => t('x'),
  );
  $form['settings_general']['upload_max_resolution']['upload_max_resolution_y'] = array(
    '#type' => 'textfield',
    '#title' => t('Height'),
    '#default_value' => variable_get('upload_max_resolution_y', 0),
    '#size' => 5,
    '#maxlength' => 5,
    '#field_suffix' => t('px'),
  );
  $form['settings_general']['upload_list_default'] = array(
    '#type' => 'select',
    '#title' => t('List files by default'),
    '#default_value' => variable_get('upload_list_default', 1),
    '#options' => array(0 => t('No'), 1 => t('Yes')),
    '#description' => t('Display attached files when viewing a post.'),
  );

  $form['settings_general']['upload_extensions_default'] = array(
    '#type' => 'textfield',
    '#title' => t('Default permitted file extensions'),
    '#default_value' => $upload_extensions_default,
    '#maxlength' => 255,
    '#description' => t('Default extensions that users can upload. Separate extensions with a space and do not include the leading dot.'),
  );
  $form['settings_general']['upload_uploadsize_default'] = array(
    '#type' => 'textfield',
    '#title' => t('Default maximum file size per upload'),
    '#default_value' => $upload_uploadsize_default,
    '#size' => 5,
    '#maxlength' => 5,
    '#description' => t('The default maximum file size a user can upload. If an image is uploaded and a maximum resolution is set, the size will be checked after the file has been resized.'),
    '#field_suffix' => t('MB'),
  );
  $form['settings_general']['upload_usersize_default'] = array(
    '#type' => 'textfield',
    '#title' => t('Default total file size per user'),
    '#default_value' => $upload_usersize_default,
    '#size' => 7,
    '#maxlength' => 7,
    '#description' => t('The default maximum size of all files a user can have on the site. Set to 0 for no restriction. The least restrictive limit is used, so setting this to 0 will disable any role-specific total file size limits.'),
    '#field_suffix' => t('MB'),
  );

  $form['settings_general']['upload_max_size'] = array('#markup' => '<p>' . t('Your PHP settings limit the maximum file size per upload to %size.', array('%size' => format_size(file_upload_max_size()))) . '</p>');

  $roles = user_roles(FALSE, 'upload files');
  $form['roles'] = array('#type' => 'value', '#value' => $roles);

  foreach ($roles as $rid => $role) {
    $form['settings_role_' . $rid] = array(
      '#type' => 'fieldset',
      '#title' => t('Settings for @role', array('@role' => $role)),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['settings_role_' . $rid]['upload_extensions_' . $rid] = array(
      '#type' => 'textfield',
      '#title' => t('Permitted file extensions'),
      '#default_value' => variable_get('upload_extensions_' . $rid, $upload_extensions_default),
      '#maxlength' => 255,
      '#description' => t('Extensions that users in this role can upload. Separate extensions with a space and do not include the leading dot.'),
    );
    $form['settings_role_' . $rid]['upload_uploadsize_' . $rid] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum file size per upload'),
      '#default_value' => variable_get('upload_uploadsize_' . $rid, $upload_uploadsize_default),
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => t('The maximum size of a file a user can upload. If an image is uploaded and a maximum resolution is set, the size will be checked after the file has been resized.'),
      '#field_suffix' => t('MB'),
    );
    $form['settings_role_' . $rid]['upload_usersize_' . $rid] = array(
      '#type' => 'textfield',
      '#title' => t('Total file size per user'),
      '#default_value' => variable_get('upload_usersize_' . $rid, $upload_usersize_default),
      '#size' => 7,
      '#maxlength' => 7,
      '#description' => t('The maximum size of all files a user can have on the site. Set to 0 for no restriction.'),
      '#field_suffix' => t('MB'),
    );
  }

  $form['#validate'] = array('upload_admin_settings_validate');

  return system_settings_form($form, FALSE);
}
