<?php

/**
 * @file
 * Contains \Drupal\Core\Command\DbDumpApplication.
 */

namespace Drupal\Core\Command;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides a command to dump a database generation script.
 */
class DbDumpApplication extends Application {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Construct the application.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  function __construct(Connection $connection, ModuleHandlerInterface $module_handler) {
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
    parent::__construct();
  }

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
    $default_commands[] = new DbDumpCommand($this->connection, $this->moduleHandler);
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
