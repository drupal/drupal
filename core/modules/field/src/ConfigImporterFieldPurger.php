<?php

/**
 * @file
 * Contains \Drupal\field\ConfigImporterFieldPurger.
 */

namespace Drupal\field;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Processes field purges before a configuration synchronization.
 */
class ConfigImporterFieldPurger {

  /**
   * Processes fields targeted for purge as part of a configuration sync.
   *
   * This takes care of deleting the field if necessary, and purging the data on
   * the fly.
   *
   * @param array $context
   *   The batch context.
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The config importer.
   */
  public static function process(array &$context, ConfigImporter $config_importer) {
    if (!isset($context['sandbox']['field'])) {
      static::initializeSandbox($context, $config_importer);
    }

    // Get the list of fields to purge.
    $fields = static::getFieldsToPurge($context['sandbox']['field']['extensions'], $config_importer->getUnprocessedConfiguration('delete'));
    // Get the first field to process.
    $field = reset($fields);
    if (!isset($context['sandbox']['field']['current_field_id']) || $context['sandbox']['field']['current_field_id'] != $field->id()) {
      $context['sandbox']['field']['current_field_id'] = $field->id();
      // If the field has not been deleted yet we need to do that. This is the
      // case when the field deletion is staged.
      if (!$field->deleted) {
        $field->delete();
      }
    }
    field_purge_batch($context['sandbox']['field']['purge_batch_size'], $field->uuid());
    $context['sandbox']['field']['current_progress']++;
    $fields_to_delete_count = count(static::getFieldsToPurge($context['sandbox']['field']['extensions'], $config_importer->getUnprocessedConfiguration('delete')));
    if ($fields_to_delete_count == 0) {
      $context['finished'] = 1;
    }
    else {
      $context['finished'] = $context['sandbox']['field']['current_progress'] / $context['sandbox']['field']['steps_to_delete'];
      $context['message'] = \Drupal::translation()->translate('Purging field @field_label', array('@field_label' => $field->label()));
    }
  }

  /**
   * Initializes the batch context sandbox for processing field deletions.
   *
   * This calculates the number of steps necessary to purge all the field data
   * and saves data for later use.
   *
   * @param array $context
   *   The batch context.
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The config importer.
   */
  protected static function initializeSandbox(array &$context, ConfigImporter $config_importer) {
    $context['sandbox']['field']['purge_batch_size'] = \Drupal::config('field.settings')->get('purge_batch_size');
    // Save the future list of installed extensions to limit the amount of times
    // the configuration is read from disk.
    $context['sandbox']['field']['extensions'] = $config_importer->getStorageComparer()->getSourceStorage()->read('core.extension');

    $context['sandbox']['field']['steps_to_delete'] = 0;
    $fields = static::getFieldsToPurge($context['sandbox']['field']['extensions'], $config_importer->getUnprocessedConfiguration('delete'));
    foreach ($fields as $field) {
      $row_count = \Drupal::entityManager()->getStorage($field->getTargetEntityTypeId())
        ->countFieldData($field);
      if ($row_count > 0) {
        // The number of steps to delete each field is determined by the
        // purge_batch_size setting. For example if the field has 9 rows and the
        // batch size is 10 then this will add 1 step to $number_of_steps.
        $how_many_steps = ceil($row_count / $context['sandbox']['field']['purge_batch_size']);
        $context['sandbox']['field']['steps_to_delete'] += $how_many_steps;
      }
    }
    // Each field possibly needs one last field_purge_batch() call to remove the
    // last instance and the field itself.
    $context['sandbox']['field']['steps_to_delete'] += count($fields);

    $context['sandbox']['field']['current_progress'] = 0;
  }

  /**
   * Gets the list of fields to purge before configuration synchronization.
   *
   * If, during a configuration synchronization, a field is being deleted and
   * the module that provides the field type is being uninstalled then the field
   * data must be purged before the module is uninstalled. Also, if deleted
   * fields exist whose field types are provided by modules that are being
   * uninstalled their data need to be purged too.
   *
   * @param array $extensions
   *   The list of extensions that will be enabled after the configuration
   *   synchronization has finished.
   * @param array $deletes
   *   The configuration that will be deleted by the configuration
   *   synchronization.
   *
   * @return \Drupal\field\Entity\FieldConfig[]
   *   An array of fields that need purging before configuration can be
   *   synchronized.
   */
  public static function getFieldsToPurge(array $extensions, array $deletes) {
    $providers = array_keys($extensions['module']);
    $providers[] = 'Core';
    $fields_to_delete = array();

    // Gather fields that will be deleted during configuration synchronization
    // where the module that provides the field type is also being uninstalled.
    $field_ids = array();
    foreach ($deletes as $config_name) {
      if (strpos($config_name, 'field.field.') === 0) {
        $field_ids[] = ConfigEntityStorage::getIDFromConfigName($config_name, 'field.field');
      }
    }
    if (!empty($field_ids)) {
      $fields = \Drupal::entityQuery('field_config')
        ->condition('id', $field_ids, 'IN')
        ->condition('module', $providers, 'NOT IN')
        ->execute();
      if (!empty($fields)) {
        $fields_to_delete = entity_load_multiple('field_config', $fields);
      }
    }

    // Gather deleted fields from modules that are being uninstalled.
    $fields = entity_load_multiple_by_properties('field_config', array('deleted' => TRUE, 'include_deleted' => TRUE));
    foreach ($fields as $field) {
      if (!in_array($field->module, $providers)) {
        $fields_to_delete[$field->id()] = $field;
      }
    }
    return $fields_to_delete;
  }

}
