<?php

namespace Drupal\Tests\Scripts;

use Drupal\Component\FileSystem\FileSystem;
use Drupal\Core\Database\Database;
use Drupal\Core\Test\TestDatabase;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Tests core/scripts/test-site.php.
 *
 * @group Setup
 *
 * This test uses the Drupal\Core\Database\Database class which has a static.
 * Therefore run in a separate process to avoid side effects.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @see \Drupal\TestSite\TestSiteApplication
 * @see \Drupal\TestSite\Commands\TestSiteInstallCommand
 * @see \Drupal\TestSite\Commands\TestSiteTearDownCommand
 */
class TestSiteApplicationTest extends UnitTestCase {

  /**
   * The PHP executable path.
   *
   * @var string
   */
  protected $php;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $php_executable_finder = new PhpExecutableFinder();
    $this->php = $php_executable_finder->find();
    $this->root = dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))));
  }

  /**
   * @coversNothing
   */
  public function testInstallWithNonExistingFile() {

    // Create a connection to the DB configured in SIMPLETEST_DB.
    $connection = Database::getConnection('default', $this->addTestDatabase(''));
    $table_count = count($connection->schema()->findTables('%'));

    $command_line = $this->php . ' core/scripts/test-site.php install --setup-file "this-class-does-not-exist" --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    $process->run();

    $this->assertContains('The file this-class-does-not-exist does not exist.', $process->getErrorOutput());
    $this->assertSame(1, $process->getExitCode());
    $this->assertCount($table_count, $connection->schema()->findTables('%'), 'No additional tables created in the database');
  }

  /**
   * @coversNothing
   */
  public function testInstallWithFileWithNoClass() {

    // Create a connection to the DB configured in SIMPLETEST_DB.
    $connection = Database::getConnection('default', $this->addTestDatabase(''));
    $table_count = count($connection->schema()->findTables('%'));

    $command_line = $this->php . ' core/scripts/test-site.php install --setup-file core/tests/fixtures/empty_file.php.module --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    $process->run();

    $this->assertContains('The file core/tests/fixtures/empty_file.php.module does not contain a class', $process->getErrorOutput());
    $this->assertSame(1, $process->getExitCode());
    $this->assertCount($table_count, $connection->schema()->findTables('%'), 'No additional tables created in the database');
  }

  /**
   * @coversNothing
   */
  public function testInstallWithNonSetupClass() {

    // Create a connection to the DB configured in SIMPLETEST_DB.
    $connection = Database::getConnection('default', $this->addTestDatabase(''));
    $table_count = count($connection->schema()->findTables('%'));

    // Use __FILE__ to test absolute paths.
    $command_line = $this->php . ' core/scripts/test-site.php install --setup-file "' . __FILE__ . '" --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root, ['COLUMNS' => PHP_INT_MAX]);
    $process->run();

    $this->assertContains('The class Drupal\Tests\Scripts\TestSiteApplicationTest contained in', $process->getErrorOutput());
    $this->assertContains('needs to implement \Drupal\TestSite\TestSetupInterface', $process->getErrorOutput());
    $this->assertSame(1, $process->getExitCode());
    $this->assertCount($table_count, $connection->schema()->findTables('%'), 'No additional tables created in the database');
  }

  /**
   * @coversNothing
   */
  public function testInstallScript() {
    $simpletest_path = $this->root . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'simpletest';
    if (!is_writable($simpletest_path)) {
      $this->markTestSkipped("Requires the directory $simpletest_path to exist and be writable");
    }

    // Install a site using the JSON output.
    $command_line = $this->php . ' core/scripts/test-site.php install --json --setup-file core/tests/Drupal/TestSite/TestSiteInstallTestScript.php --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();

    $this->assertSame(0, $process->getExitCode());
    $result = json_decode($process->getOutput(), TRUE);
    $db_prefix = $result['db_prefix'];
    $this->assertStringStartsWith('simpletest' . substr($db_prefix, 4) . ':', $result['user_agent']);

    $http_client = new Client();
    $request = (new Request('GET', getenv('SIMPLETEST_BASE_URL') . '/test-page'))
      ->withHeader('User-Agent', trim($result['user_agent']));

    $response = $http_client->send($request);
    // Ensure the test_page_test module got installed.
    $this->assertContains('Test page | Drupal', (string) $response->getBody());

    // Ensure that there are files and database tables for the tear down command
    // to clean up.
    $key = $this->addTestDatabase($db_prefix);
    $this->assertGreaterThan(0, count(Database::getConnection('default', $key)->schema()->findTables('%')));
    $test_database = new TestDatabase($db_prefix);
    $test_file = $this->root . DIRECTORY_SEPARATOR . $test_database->getTestSitePath() . DIRECTORY_SEPARATOR . '.htkey';
    $this->assertFileExists($test_file);

    // Ensure the lock file exists.
    $this->assertFileExists($this->getTestLockFile($db_prefix));

    // Install another site so we can ensure the tear down command only removes
    // one site at a time. Use the regular output.
    $command_line = $this->php . ' core/scripts/test-site.php install --setup-file core/tests/Drupal/TestSite/TestSiteInstallTestScript.php --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();
    $this->assertContains('Successfully installed a test site', $process->getOutput());
    $this->assertSame(0, $process->getExitCode());
    $regex = '/Database prefix\s+([^\s]*)/';
    $this->assertRegExp($regex, $process->getOutput());
    preg_match('/Database prefix\s+([^\s]*)/', $process->getOutput(), $matches);
    $other_db_prefix = $matches[1];
    $other_key = $this->addTestDatabase($other_db_prefix);
    $this->assertGreaterThan(0, count(Database::getConnection('default', $other_key)->schema()->findTables('%')));

    // Ensure the lock file exists for the new install.
    $this->assertFileExists($this->getTestLockFile($other_db_prefix));

    // Now test the tear down process as well, but keep the lock.
    $command_line = $this->php . ' core/scripts/test-site.php tear-down ' . $db_prefix . ' --keep-lock --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(0, $process->getExitCode());
    $this->assertContains("Successfully uninstalled $db_prefix test site", $process->getOutput());

    // Ensure that all the tables and files for this DB prefix are gone.
    $this->assertCount(0, Database::getConnection('default', $key)->schema()->findTables('%'));
    $this->assertFileNotExists($test_file);

    // Ensure the other site's tables and files still exist.
    $this->assertGreaterThan(0, count(Database::getConnection('default', $other_key)->schema()->findTables('%')));
    $test_database = new TestDatabase($other_db_prefix);
    $test_file = $this->root . DIRECTORY_SEPARATOR . $test_database->getTestSitePath() . DIRECTORY_SEPARATOR . '.htkey';
    $this->assertFileExists($test_file);

    // Tear down the other site. Tear down should work if the test site is
    // broken. Prove this by removing its settings.php.
    $test_site_settings = $this->root . DIRECTORY_SEPARATOR . $test_database->getTestSitePath() . DIRECTORY_SEPARATOR . 'settings.php';
    $this->assertTrue(unlink($test_site_settings));
    $command_line = $this->php . ' core/scripts/test-site.php tear-down ' . $other_db_prefix . ' --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(0, $process->getExitCode());
    $this->assertContains("Successfully uninstalled $other_db_prefix test site", $process->getOutput());

    // Ensure that all the tables and files for this DB prefix are gone.
    $this->assertCount(0, Database::getConnection('default', $other_key)->schema()->findTables('%'));
    $this->assertFileNotExists($test_file);

    // The lock for the first site should still exist but the second site's lock
    // is released during tear down.
    $this->assertFileExists($this->getTestLockFile($db_prefix));
    $this->assertFileNotExists($this->getTestLockFile($other_db_prefix));
  }

  /**
   * @coversNothing
   */
  public function testInstallInDifferentLanguage() {
    $simpletest_path = $this->root . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'simpletest';
    if (!is_writable($simpletest_path)) {
      $this->markTestSkipped("Requires the directory $simpletest_path to exist and be writable");
    }

    $command_line = $this->php . ' core/scripts/test-site.php install --json --langcode fr --setup-file core/tests/Drupal/TestSite/TestSiteInstallTestScript.php --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    $process->setTimeout(500);
    $process->run();
    $this->assertEquals(0, $process->getExitCode());

    $result = json_decode($process->getOutput(), TRUE);
    $db_prefix = $result['db_prefix'];
    $http_client = new Client();
    $request = (new Request('GET', getenv('SIMPLETEST_BASE_URL') . '/test-page'))
      ->withHeader('User-Agent', trim($result['user_agent']));

    $response = $http_client->send($request);
    // Ensure the test_page_test module got installed.
    $this->assertContains('Test page | Drupal', (string) $response->getBody());
    $this->assertContains('lang="fr"', (string) $response->getBody());

    // Now test the tear down process as well.
    $command_line = $this->php . ' core/scripts/test-site.php tear-down ' . $db_prefix . ' --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(0, $process->getExitCode());

    // Ensure that all the tables for this DB prefix are gone.
    $this->assertCount(0, Database::getConnection('default', $this->addTestDatabase($db_prefix))->schema()->findTables('%'));
  }

  /**
   * @coversNothing
   */
  public function testTearDownDbPrefixValidation() {
    $command_line = $this->php . ' core/scripts/test-site.php tear-down not-a-valid-prefix';
    $process = new Process($command_line, $this->root);
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(1, $process->getExitCode());
    $this->assertContains('Invalid database prefix: not-a-valid-prefix', $process->getErrorOutput());
  }

  /**
   * @coversNothing
   */
  public function testUserLogin() {
    $simpletest_path = $this->root . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'simpletest';
    if (!is_writable($simpletest_path)) {
      $this->markTestSkipped("Requires the directory $simpletest_path to exist and be writable");
    }

    // Install a site using the JSON output.
    $command_line = $this->php . ' core/scripts/test-site.php install --json --setup-file core/tests/Drupal/TestSite/TestSiteInstallTestScript.php --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();

    $this->assertSame(0, $process->getExitCode());
    $result = json_decode($process->getOutput(), TRUE);
    $db_prefix = $result['db_prefix'];
    $site_path = $result['site_path'];
    $this->assertSame('sites/simpletest/' . str_replace('test', '', $db_prefix), $site_path);

    // Test the user login command with valid uid.
    $command_line = $this->php . ' core/scripts/test-site.php user-login 1 --site-path ' . $site_path;
    $process = new Process($command_line, $this->root);
    $process->run();
    $this->assertSame(0, $process->getExitCode());
    $this->assertContains('/user/reset/1/', $process->getOutput());

    $http_client = new Client();
    $request = (new Request('GET', getenv('SIMPLETEST_BASE_URL') . trim($process->getOutput())))
      ->withHeader('User-Agent', trim($result['user_agent']));

    $response = $http_client->send($request);

    // Ensure the response sets a new session.
    $this->assertTrue($response->getHeader('Set-Cookie'));

    // Test the user login command with invalid uid.
    $command_line = $this->php . ' core/scripts/test-site.php user-login invalid-uid --site-path ' . $site_path;
    $process = new Process($command_line, $this->root);
    $process->run();
    $this->assertSame(1, $process->getExitCode());
    $this->assertContains('The "uid" argument needs to be an integer, but it is "invalid-uid".', $process->getErrorOutput());

    // Now tear down the test site.
    $command_line = $this->php . ' core/scripts/test-site.php tear-down ' . $db_prefix . ' --db-url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(0, $process->getExitCode());
    $this->assertContains("Successfully uninstalled $db_prefix test site", $process->getOutput());
  }

  /**
   * Adds the installed test site to the database connection info.
   *
   * @param string $db_prefix
   *   The prefix of the installed test site.
   *
   * @return string
   *   The database key of the added connection.
   */
  protected function addTestDatabase($db_prefix) {
    $database = Database::convertDbUrlToConnectionInfo(getenv('SIMPLETEST_DB'), $this->root);
    $database['prefix'] = ['default' => $db_prefix];
    $target = __CLASS__ . $db_prefix;
    Database::addConnectionInfo($target, 'default', $database);
    return $target;
  }

  /**
   * Gets the lock file path.
   *
   * @param string $db_prefix
   *   The prefix of the installed test site.
   *
   * @return string
   *   The lock file path.
   */
  protected function getTestLockFile($db_prefix) {
    $lock_id = str_replace('test', '', $db_prefix);
    return FileSystem::getOsTemporaryDirectory() . '/test_' . $lock_id;
  }

}
