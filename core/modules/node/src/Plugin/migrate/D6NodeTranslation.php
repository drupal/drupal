<?php

namespace Drupal\node\Plugin\migrate;

use Drupal\migrate\Plugin\Migration;
use Drupal\migrate_drupal\Plugin\MigrationWithFollowUpInterface;

/**
 * Migration plugin for the Drupal 6 node translations.
 */
class D6NodeTranslation extends Migration implements MigrationWithFollowUpInterface {

  /**
   * {@inheritdoc}
   */
  public function generateFollowUpMigrations() {
    $this->migrationPluginManager->clearCachedDefinitions();
    return $this->migrationPluginManager->createInstances('d6_entity_reference_translation');
  }

}
