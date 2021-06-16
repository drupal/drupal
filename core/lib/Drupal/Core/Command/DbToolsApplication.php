<?php

namespace Drupal\Core\Command;

use Symfony\Component\Console\Application;

/**
 * Provides a command to import a database generation script.
 */
class DbToolsApplication extends Application {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct('Database Tools', \Drupal::VERSION);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCommands() {
    $default_commands = parent::getDefaultCommands();
    $default_commands[] = new DbDumpCommand();
    $default_commands[] = new DbImportCommand();
    return $default_commands;
  }

}
