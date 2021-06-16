<?php

namespace Drupal\node\Plugin\migrate;

use Drupal\migrate\Plugin\Migration;
use Drupal\migrate_drupal\Plugin\MigrationWithFollowUpInterface;

/**
 * Migration plugin for the Drupal 7 node translations.
 */
class D7NodeTranslation extends Migration implements MigrationWithFollowUpInterface {

  /**
   * {@inheritdoc}
   */
  public function generateFollowUpMigrations() {
    $this->migrationPluginManager->clearCachedDefinitions();
    return $this->migrationPluginManager->createInstances('d7_entity_reference_translation');
  }

}
