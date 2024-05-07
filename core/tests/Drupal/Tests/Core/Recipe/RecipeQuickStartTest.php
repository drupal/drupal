<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Recipe;

use Drupal\sqlite\Driver\Database\sqlite\Install\Tasks;
use Drupal\Core\Test\TestDatabase;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Tests the quick-start command with recipes.
 *
 * These tests are run in a separate process because they load Drupal code via
 * an include.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires extension pdo_sqlite
 *
 * @group Command
 * @group Recipe
 */
class RecipeQuickStartTest extends TestCase {

  /**
   * The PHP executable path.
   *
   * @var string
   */
  protected string $php;

  /**
   * A test database object.
   *
   * @var \Drupal\Core\Test\TestDatabase
   */
  protected TestDatabase $testDb;

  /**
   * The Drupal root directory.
   *
   * @var string
   */
  protected string $root;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $php_executable_finder = new PhpExecutableFinder();
    $this->php = (string) $php_executable_finder->find();
    $this->root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
    if (!is_writable("{$this->root}/sites/simpletest")) {
      $this->markTestSkipped('This test requires a writable sites/simpletest directory');
    }
    // Get a lock and a valid site path.
    $this->testDb = new TestDatabase();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->testDb) {
      $test_site_directory = $this->root . DIRECTORY_SEPARATOR . $this->testDb->getTestSitePath();
      if (file_exists($test_site_directory)) {
        // @todo use the tear down command from
        //   https://www.drupal.org/project/drupal/issues/2926633
        // Delete test site directory.
        $this->fileUnmanagedDeleteRecursive($test_site_directory, BrowserTestBase::filePreDeleteCallback(...));
      }
    }
    parent::tearDown();
  }

  /**
   * Tests the quick-start command with a recipe.
   */
  public function testQuickStartRecipeCommand(): void {
    $sqlite = (string) (new \PDO('sqlite::memory:'))->query('select sqlite_version()')->fetch()[0];
    if (version_compare($sqlite, Tasks::SQLITE_MINIMUM_VERSION) < 0) {
      $this->markTestSkipped();
    }

    // Install a site using the standard recipe to ensure the one time login
    // link generation works.

    $install_command = [
      $this->php,
      'core/scripts/drupal',
      'quick-start',
      'core/recipes/standard',
      "--site-name='Test site {$this->testDb->getDatabasePrefix()}'",
      '--suppress-login',
    ];
    $process = new Process($install_command, NULL, ['DRUPAL_DEV_SITE_PATH' => $this->testDb->getTestSitePath()]);
    $process->setTimeout(500);
    $process->start();
    $guzzle = new Client();
    $port = FALSE;
    $process->waitUntil(function ($type, $output) use (&$port) {
      if (preg_match('/127.0.0.1:(\d+)/', $output, $match)) {
        $port = $match[1];
        return TRUE;
      }
    });
    // The progress bar uses STDERR to write messages.
    $this->assertStringContainsString('Congratulations, you installed Drupal!', $process->getErrorOutput());
    // Ensure the command does not trigger any PHP deprecations.
    $this->assertStringNotContainsStringIgnoringCase('deprecated', $process->getErrorOutput());
    $this->assertNotFalse($port, "Web server running on port $port");

    // Give the server a couple of seconds to be ready.
    sleep(2);
    $this->assertStringContainsString("127.0.0.1:$port/user/reset/1/", $process->getOutput());

    // Generate a cookie so we can make a request against the installed site.
    define('DRUPAL_TEST_IN_CHILD_SITE', FALSE);
    chmod($this->testDb->getTestSitePath(), 0755);
    $cookieJar = CookieJar::fromArray([
      'SIMPLETEST_USER_AGENT' => drupal_generate_test_ua($this->testDb->getDatabasePrefix()),
    ], '127.0.0.1');

    $response = $guzzle->get('http://127.0.0.1:' . $port, ['cookies' => $cookieJar]);
    $content = (string) $response->getBody();
    $this->assertStringContainsString('Test site ' . $this->testDb->getDatabasePrefix(), $content);
    // Test content from Standard front page.
    $this->assertStringContainsString('Congratulations and welcome to the Drupal community.', $content);

    // Stop the web server.
    $process->stop();
  }

  /**
   * Deletes all files and directories in the specified path recursively.
   *
   * Note this method has no dependencies on Drupal core to ensure that the
   * test site can be torn down even if something in the test site is broken.
   *
   * @param string $path
   *   A string containing either a URI or a file or directory path.
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
  protected function fileUnmanagedDeleteRecursive($path, $callback = NULL): bool {
    if (isset($callback)) {
      call_user_func($callback, $path);
    }
    if (is_dir($path)) {
      $dir = dir($path);
      assert($dir instanceof \Directory);
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
