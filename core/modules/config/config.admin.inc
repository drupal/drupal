<?php

/**
 * @file
 * Admin page callbacks for the config module.
 */

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;

/**
 * Helper function to construct the storage changes in a configuration synchronization form.
 *
 * @param array $form
 *   The form structure to add to. Passed by reference.
 * @param array $form_state
 *   The current state of the form. Passed by reference.
 * @param \Drupal\Core\Config\StorageInterface $source_storage
 *   The source storage to retrieve differences from.
 *
 * @return array
 *   The form with the configuration storage changes.
 */
function config_admin_sync_form(array &$form, array &$form_state, StorageInterface $source_storage) {
  $source_list = $source_storage->listAll();
  $config_comparer = new StorageComparer(Drupal::service('config.storage.staging'), Drupal::service('config.storage'));
  if (empty($source_list) || !$config_comparer->createChangelist()->hasChanges()) {
    $form['no_changes'] = array(
      '#markup' => t('There are no configuration changes.'),
    );
    $form['actions']['#access'] = FALSE;
    return $form;
  }
  else {
    // Store the comparer for use in the submit.
    $form_state['storage_comparer'] = $config_comparer;
  }

  // Add the AJAX library to the form for dialog support.
  $form['#attached']['library'][] = array('system', 'drupal.ajax');

  foreach ($config_comparer->getChangelist() as $config_change_type => $config_files) {
    if (empty($config_files)) {
      continue;
    }

    // @todo A table caption would be more appropriate, but does not have the
    //   visual importance of a heading.
    $form[$config_change_type]['heading'] = array(
      '#type' => 'html_tag',
      '#tag' => 'h3',
    );
    switch ($config_change_type) {
      case 'create':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count new', '@count new');
        break;

      case 'update':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count changed', '@count changed');
        break;

      case 'delete':
        $form[$config_change_type]['heading']['#value'] = format_plural(count($config_files), '@count removed', '@count removed');
        break;
    }
    $form[$config_change_type]['list'] = array(
      '#theme' => 'table',
      '#header' => array('Name', 'Operations'),
    );

    foreach ($config_files as $config_file) {
      $links['view_diff'] = array(
        'title' => t('View differences'),
        'href' => 'admin/config/development/sync/diff/' . $config_file,
        'attributes' => array(
          'class' => array('use-ajax'),
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => json_encode(array(
            'width' => 700
          )),
        ),
      );
      $form[$config_change_type]['list']['#rows'][] = array(
        'name' => $config_file,
        'operations' => array(
          'data' => array(
            '#type' => 'operations',
            '#links' => $links,
          ),
        ),
      );
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

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import all'),
  );

  config_admin_sync_form($form, $form_state, $source_storage, $target_storage);

  return $form;
}

/**
 * Form submission handler for config_admin_import_form().
 */
function config_admin_import_form_submit($form, &$form_state) {
  $config_importer = new ConfigImporter(
    $form_state['storage_comparer'],
    Drupal::service('event_dispatcher'),
    Drupal::service('config.factory'),
    Drupal::entityManager(),
    Drupal::lock()
  );
  if ($config_importer->alreadyImporting()) {
    drupal_set_message(t('Another request may be synchronizing configuration already.'));
  }
  else{
    try {
      $config_importer->import();
      drupal_flush_all_caches();
      drupal_set_message(t('The configuration was imported successfully.'));
    }
    catch (ConfigException $e) {
      // Return a negative result for UI purposes. We do not differentiate between
      // an actual synchronization error and a failed lock, because concurrent
      // synchronizations are an edge-case happening only when multiple developers
      // or site builders attempt to do it without coordinating.
      watchdog_exception('config_import', $e);
      drupal_set_message(t('The import failed due to an error. Any errors have been logged.'), 'error');
    }
  }
}
