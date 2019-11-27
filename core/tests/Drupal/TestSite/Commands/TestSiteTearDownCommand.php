<?php

namespace Drupal\TestSite\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\TestDatabase;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to tear down a test Drupal site.
 *
 * @internal
 */
class TestSiteTearDownCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tear-down')
      ->setDescription('Removes a test site added by the install command')
      ->setHelp('All the database tables and files will be removed.')
      ->addArgument('db-prefix', InputArgument::REQUIRED, 'The database prefix for the test site.')
      ->addOption('db-url', NULL, InputOption::VALUE_OPTIONAL, 'URL for database. Defaults to the environment variable SIMPLETEST_DB.', getenv('SIMPLETEST_DB'))
      ->addOption('keep-lock', NULL, InputOption::VALUE_NONE, 'Keeps the database prefix lock. Useful for ensuring test isolation when running concurrent tests.')
      ->addUsage('test12345678')
      ->addUsage('test12345678 --db-url "mysql://username:password@localhost/databasename#table_prefix"')
      ->addUsage('test12345678 --keep-lock');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $db_prefix = $input->getArgument('db-prefix');
    // Validate the db_prefix argument.
    try {
      $test_database = new TestDatabase($db_prefix);
    }
    catch (\InvalidArgumentException $e) {
      $io = new SymfonyStyle($input, $output);
      $io->getErrorStyle()->error("Invalid database prefix: $db_prefix\n\nValid database prefixes match the regular expression '/test(\d+)$/'. For example, 'test12345678'.");
      // Display the synopsis of the command like Composer does.
      $output->writeln(sprintf('<info>%s</info>', sprintf($this->getSynopsis(), $this->getName())), OutputInterface::VERBOSITY_QUIET);
      return 1;
    }

    $db_url = $input->getOption('db-url');
    putenv("SIMPLETEST_DB=$db_url");

    // Handle the cleanup of the test site.
    $this->tearDown($test_database, $db_url);

    // Release the test database prefix lock.
    if (!$input->getOption('keep-lock')) {
      $test_database->releaseLock();
    }

    $output->writeln("<info>Successfully uninstalled $db_prefix test site</info>");

    return 0;
  }

  /**
   * Removes a given instance by deleting all the database tables and files.
   *
   * @param \Drupal\Core\Test\TestDatabase $test_database
   *   The test database object.
   * @param string $db_url
   *   The database URL.
   *
   * @see \Drupal\Tests\BrowserTestBase::cleanupEnvironment()
   */
  protected function tearDown(TestDatabase $test_database, $db_url) {
    // Connect to the test database.
    $root = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
    $database = Database::convertDbUrlToConnectionInfo($db_url, $root);
    $database['prefix'] = ['default' => $test_database->getDatabasePrefix()];
    Database::addConnectionInfo(__CLASS__, 'default', $database);

    // Remove all the tables.
    $schema = Database::getConnection('default', __CLASS__)->schema();
    $tables = $schema->findTables('%');
    array_walk($tables, [$schema, 'dropTable']);

    // Delete test site directory.
    $this->fileUnmanagedDeleteRecursive($root . DIRECTORY_SEPARATOR . $test_database->getTestSitePath(), [BrowserTestBase::class, 'filePreDeleteCallback']);
  }

  /**
   * Deletes all files and directories in the specified path recursively.
   *
   * Note this method has no dependencies on Drupal core to ensure that the
   * test site can be torn down even if something in the test site is broken.
   *
   * @param string $path
   *   A string containing either an URI or a file or directory path.
   * @param callable $callback
   *   (optional) Callback function to run on each file prior to deleting it and
   *   on each directory prior to traversing it. For example, can be used to
   *   modify permissions.
   *
   * @return bool
   *   TRUE for success or if path does not exist, FALSE in the event of an
   *   error.
   *
   * @see \Drupal\Core\File\FileSystemInterface::deleteRecursive()
   */
  protected function fileUnmanagedDeleteRecursive($path, $callback = NULL) {
    if (isset($callback)) {
      call_user_func($callback, $path);
    }
    if (is_dir($path)) {
      $dir = dir($path);
      while (($entry = $dir->read()) !== FALSE) {
        if ($entry == '.' || $entry == '..') {
          continue;
        }
        $entry_path = $path . '/' . $entry;
        $this->fileUnmanagedDeleteRecursive($entry_path, $callback);
      }
      $dir->close();

      return rmdir($path);
    }
    return unlink($path);
  }

}
