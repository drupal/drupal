<?php

namespace Drupal\Core\Command;

use Drupal\Core\Database\Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Base command that abstracts handling of database connection arguments.
 */
class DbCommandBase extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->addOption('database', NULL, InputOption::VALUE_OPTIONAL, 'The database connection name to use.', 'default')
      ->addOption('database-url', 'db-url', InputOption::VALUE_OPTIONAL, 'A database url to parse and use as the database connection.')
      ->addOption('prefix', NULL, InputOption::VALUE_OPTIONAL, 'Override or set the table prefix used in the database connection.');
  }

  /**
   * Parse input options decide on a database.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input object.
   *
   * @return \Drupal\Core\Database\Connection
   */
  protected function getDatabaseConnection(InputInterface $input) {
    // Load connection from a URL.
    if ($input->getOption('database-url')) {
      // @todo this could probably be refactored to not use a global connection.
      // Ensure database connection isn't set.
      if (Database::getConnectionInfo('db-tools')) {
        throw new \RuntimeException('Database "db-tools" is already defined. Cannot define database provided.');
      }
      $info = Database::convertDbUrlToConnectionInfo($input->getOption('database-url'), \Drupal::root());
      Database::addConnectionInfo('db-tools', 'default', $info);
      $key = 'db-tools';
    }
    else {
      $key = $input->getOption('database');
    }

    // If they supplied a prefix, replace it in the connection information.
    $prefix = $input->getOption('prefix');
    if ($prefix) {
      $info = Database::getConnectionInfo($key)['default'];
      $info['prefix'] = $prefix;

      Database::removeConnection($key);
      Database::addConnectionInfo($key, 'default', $info);
    }

    return Database::getConnection('default', $key);
  }

}
