<?php

/**
 * @file
 * Contains \Drupal\Core\Command\DbDumpApplication.
 */

namespace Drupal\Core\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides a command to dump a database generation script.
 */
class DbDumpApplication extends Application {

  /**
   * {@inheritdoc}
   */
  protected function getCommandName(InputInterface $input) {
    return 'dump-database-d8-mysql';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCommands() {
    // Even though this is a single command, keep the HelpCommand (--help).
    $default_commands = parent::getDefaultCommands();
    $default_commands[] = new DbDumpCommand();
    return $default_commands;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden so the application doesn't expect the command name as the first
   * argument.
   */
  public function getDefinition() {
    $definition = parent::getDefinition();
    // Clears the normal first argument (the command name).
    $definition->setArguments();
    return $definition;
  }

}
