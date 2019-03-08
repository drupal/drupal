<?php

namespace Drupal\comment\Plugin\migrate;

use Drupal\migrate_drupal\Plugin\migrate\FieldMigration;

/**
 * Migration plugin for Drupal 7 comments with fields.
 */
class D7Comment extends FieldMigration {

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $this->fieldDiscovery->addEntityFieldProcesses($this, 'comment');
    }
    return parent::getProcess();
  }

}
