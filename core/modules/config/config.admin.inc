<?php

/**
 * @file
 * Admin page callbacks for the config module.
 */

use Drupal\Core\Config\StorageInterface;

/**
 * Helper function to construct the storage changes in a configuration synchronization form.
 *
 * @param array $form
 *   The form structure to add to. Passed by reference.
 * @param array $form_state
 *   The current state of the form. Passed by reference.
 * @param Drupal\Core\Config\StorageInterface $source_storage
 *   The source storage to retrieve differences from.
 * @param Drupal\Core\Config\StorageInterface $target_storage
 *   The target storage to compare differences to.
 */
function config_admin_sync_form(array &$form, array &$form_state, StorageInterface $source_storage, StorageInterface $target_storage) {
  $config_changes = config_sync_get_changes($source_storage, $target_storage);
  if (empty($config_changes)) {
    $form['no_changes'] = array(
      '#markup' => t('There are no configuration changes.'),
    );
    return $form;
  }

  foreach ($config_changes as $config_change_type => $config_files) {
    if (empty($config_files)) {
      continue;
    }
    // @todo A table caption would be more appropriate, but does not have the
    //   visual importance of a heading.
    $form[$config_change_type]['heading'] = array(
      '#theme' => 'html_tag__h3',
      '#tag' => 'h3',
    );
    switch ($config_change_type) {
      case 'create':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count new', '@count new');
        break;

      case 'change':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count changed', '@count changed');
        break;

      case 'delete':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count removed', '@count removed');
        break;
    }
    $form[$config_change_type]['list'] = array(
      '#theme' => 'table',
      '#header' => array('Name'),
    );
    foreach ($config_files as $config_file) {
      $form[$config_change_type]['list']['#rows'][] = array($config_file);
    }
  }
}

/**
 * Form constructor for configuration import form.
 *
 * @see config_admin_import_form_submit()
 * @see config_import()
 */
function config_admin_import_form($form, &$form_state) {
  // Retrieve a list of differences between last known state and active store.
  $source_storage = drupal_container()->get('config.storage.staging');
  $target_storage = drupal_container()->get('config.storage');

  // Prevent users from deleting all configuration.
  // If the source storage is empty, that signals the unique condition of not
  // having exported anything at all, and thus no valid storage to compare the
  // active storage against.
  // @todo StorageInterface::listAll() can easily yield hundreds or even
  //   thousands of entries; consider to add a dedicated isEmpty() method for
  //   storage controllers.
  $all = $source_storage->listAll();
  if (empty($all)) {
    form_set_error('', t('There is no base configuration. <a href="@export-url">Export</a> it first.', array(
      '@export-url' => url('admin/config/development/sync/export'),
    )));
    return $form;
  }

  config_admin_sync_form($form, $form_state, $source_storage, $target_storage);

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import all'),
  );
  return $form;
}

/**
 * Form submission handler for config_admin_import_form().
 */
function config_admin_import_form_submit($form, &$form_state) {
  if (config_import()) {
    drupal_set_message(t('The configuration was imported successfully.'));
  }
  else {
    // Another request may be synchronizing configuration already. Wait for it
    // to complete before returning the error, so already synchronized changes
    // do not appear again.
    lock_wait(__FUNCTION__);
    drupal_set_message(t('The import failed due to an error. Any errors have been logged.'), 'error');
  }
}

/**
 * Form constructor for configuration export form.
 *
 * @see config_admin_export_form_submit()
 * @see config_export()
 *
 * @todo "export" is a misnomer with config.storage.staging.
 */
function config_admin_export_form($form, &$form_state) {
  // Retrieve a list of differences between active store and last known state.
  $source_storage = drupal_container()->get('config.storage');
  $target_storage = drupal_container()->get('config.storage.staging');

  config_admin_sync_form($form, $form_state, $source_storage, $target_storage);

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Export all'),
  );
  return $form;
}

/**
 * Form submission handler for config_admin_export_form().
 */
function config_admin_export_form_submit($form, &$form_state) {
  config_export();
  drupal_set_message(t('The configuration was exported successfully.'));
}

