<?php

/**
 * @file
 * Contains \Drupal\Core\Command\DbDumpCommand.
 */

namespace Drupal\Core\Command;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\SchemaObjectExistsException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command to import the current database from a script.
 *
 * This script runs on databases exported using using one of the database dump
 * commands and imports it into the current database connection.
 *
 * @see \Drupal\Core\Command\DbImportApplication
 */
class DbImportCommand extends DbCommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setName('import')
      ->setDescription('Import database from a generation script.')
      ->addArgument('script', InputOption::VALUE_REQUIRED, 'Import script');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $script = $input->getArgument('script');
    if (!is_file($script)) {
      $output->writeln('File must exist.');
      return;
    }

    $connection = $this->getDatabaseConnection($input);
    $this->runScript($connection, $script);
    $output->writeln('Import completed successfully.');
  }

  /**
   * Run the database script.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Connection used by the script when included.
   * @param string $script
   *   Path to dump script.
   */
  protected function runScript(Connection $connection, $script) {
    if (substr($script, -3) == '.gz') {
      $script = "compress.zlib://$script";
    }
    try {
      require $script;
    }
    catch (SchemaObjectExistsException $e) {
      throw new \RuntimeException('An existing Drupal installation exists at this location. Try removing all tables or changing the database prefix in your settings.php file.');
    }
  }

}
