<?php

namespace Drupal\user\Plugin\migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\Migration;

/**
 * Plugin class for user migrations dealing with profile values.
 */
class ProfileValues extends Migration {

  /**
   * Flag determining whether the process plugin has been initialized.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $definition['source'] = [
        'plugin' => 'profile_field',
        'ignore_map' => TRUE,
      ] + $this->source;
      $definition['destination']['plugin'] = 'null';
      $definition['idMap']['plugin'] = 'null';
      try {
        $profile_field_migration = $this->migrationPluginManager->createStubMigration($definition);
        $source_plugin = $profile_field_migration->getSourcePlugin();
        $source_plugin->checkRequirements();
        foreach ($source_plugin as $row) {
          $name = $row->getSourceProperty('name');
          $this->process[$name] = $name;
        }
      }
      catch (RequirementsException $e) {
        // The checkRequirements() call will fail when the profile module does
        // not exist on the source site.
      }
    }
    return parent::getProcess();
  }

}
