<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Entity\Migration.
 */

namespace Drupal\migrate_drupal\Entity;

use Drupal\migrate\Entity\Migration as BaseMigration;

class Migration extends BaseMigration implements MigrationInterface {

  /**
   * The load plugin configuration, if any.
   *
   * @var array
   */
  protected $load = array();

  /**
   * The load plugin.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateLoadInterface|false
   */
  protected $loadPlugin = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getLoadPlugin() {
    if ($this->load && !$this->loadPlugin) {
      $this->loadPlugin = \Drupal::service('plugin.manager.migrate.load')->createInstance($this->load['plugin'], $this->load, $this);
    }
    return $this->loadPlugin;
  }

}
