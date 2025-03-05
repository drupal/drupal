<?php

namespace Drupal\Core\Config\Importer;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Installer\InstallerKernel;

/**
 * Methods for running the ConfigImporter in a batch.
 *
 * @see \Drupal\Core\Config\ConfigImporter
 */
class ConfigImporterBatch {

  /**
   * Processes the config import batch and persists the importer.
   *
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The batch config importer object to persist.
   * @param string $sync_step
   *   The synchronization step to do.
   * @param array $context
   *   The batch context.
   */
  public static function process(ConfigImporter $config_importer, $sync_step, &$context) {
    if (!isset($context['sandbox']['config_importer'])) {
      $context['sandbox']['config_importer'] = $config_importer;
    }

    $config_importer = $context['sandbox']['config_importer'];
    $config_importer->doSyncStep($sync_step, $context);
    if ($errors = $config_importer->getErrors()) {
      if (!isset($context['results']['errors'])) {
        $context['results']['errors'] = [];
      }
      $context['results']['errors'] = array_merge($errors, $context['results']['errors']);
    }
  }

  /**
   * Finish batch.
   *
   * This function is a static function to avoid serializing the ConfigSync
   * object unnecessarily.
   *
   * @param bool $success
   *   Indicate that the batch API tasks were all completed successfully.
   * @param array $results
   *   An array of all the results that were updated in update_do_one().
   * @param array $operations
   *   A list of the operations that had not been completed by the batch API.
   */
  public static function finish($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      if (!empty($results['errors'])) {
        $logger = \Drupal::logger('config_sync');
        foreach ($results['errors'] as $error) {
          $messenger->addError($error);
          $logger->error($error);
        }
        $messenger->addWarning(t('The configuration was imported with errors.'));
      }
      elseif (!InstallerKernel::installationAttempted()) {
        // Display a success message when not installing Drupal.
        $messenger->addStatus(t('The configuration was imported successfully.'));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $messenger->addError($message);
    }
  }

}
