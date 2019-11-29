<?php

namespace Drupal\BuildTests\Framework;

use Behat\Mink\Driver\Goutte\Client;
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Drupal\Component\FileSystem\FileSystem as DrupalFilesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\Client as SymfonyClient;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Provides a workspace to test build processes.
 *
 * If you need to build a file system and then run a command from the command
 * line then this is the test framework for you.
 *
 * Tests using this interface run in separate processes.
 *
 * Tests can perform HTTP requests against the assembled codebase.
 *
 * The results of these HTTP requests can be asserted using Mink.
 *
 * This framework does not use the same Mink extensions as BrowserTestBase.
 *
 * Features:
 * - Provide complete isolation between the test runner and the site under test.
 * - Provide a workspace where filesystem build processes can be performed.
 * - Allow for the use of PHP's build-in HTTP server to send requests to the
 *   site built using the filesystem.
 * - Allow for commands and HTTP requests to be made to different subdirectories
 *   of the workspace filesystem, to facilitate comparison between different
 *   build results, and to support Composer builds which have an alternate
 *   docroot.
 * - Provide as little framework as possible. Convenience methods should be
 *   built into the test, or abstract base classes.
 * - Allow parallel testing, using random/unique port numbers for different HTTP
 *   servers.
 * - Allow the use of PHPUnit-style (at)require annotations for external shell
 *   commands.
 *
 * We don't use UiHelperInterface because it is too tightly integrated to
 * Drupal.
 */
abstract class BuildTestBase extends TestCase {

  use ExternalCommandRequirementsTrait;

  /**
   * The working directory where this test will manipulate files.
   *
   * Use getWorkspaceDirectory() to access this information.
   *
   * @var string
   *
   * @see self::getWorkspaceDirectory()
   */
  private $workspaceDir;

  /**
   * The process that's running the HTTP server.
   *
   * @var \Symfony\Component\Process\Process
   *
   * @see self::standUpServer()
   * @see self::stopServer()
   */
  private $serverProcess = NULL;

  /**
   * Default to destroying build artifacts after a test finishes.
   *
   * Mainly useful for debugging.
   *
   * @var bool
   */
  protected $destroyBuild = TRUE;

  /**
   * The docroot for the server process.
   *
   * This stores the last docroot directory used to start the server process. We
   * keep this information so we can restart the server if the desired docroot
   * changes.
   *
   * @var string
   */
  private $serverDocroot = NULL;

  /**
   * Our native host name, used by PHP when it starts up the server.
   *
   * Requests should always be made to 'localhost', and not this IP address.
   *
   * @var string
   */
  private static $hostName = '127.0.0.1';

  /**
   * Port that will be tested.
   *
   * Generated internally. Use getPortNumber().
   *
   * @var int
   */
  private $hostPort;

  /**
   * A list of ports used by the test.
   *
   * Prevent the same process finding the same port by storing a list of ports
   * already discovered. This also stores locks so they are not released until
   * the test class is torn down.
   *
   * @var \Symfony\Component\Lock\LockInterface[]
   */
  private $portLocks = [];

  /**
   * The Mink session manager.
   *
   * @var \Behat\Mink\Mink
   */
  private $mink;

  /**
   * The most recent command process.
   *
   * @var \Symfony\Component\Process\Process
   *
   * @see ::executeCommand()
   */
  private $commandProcess;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    static::checkClassCommandRequirements();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    static::checkMethodCommandRequirements($this->getName());
    $this->phpFinder = new PhpExecutableFinder();
    // Set up the workspace directory.
    // @todo Glean working directory from env vars, etc.
    $fs = new SymfonyFilesystem();
    $this->workspaceDir = $fs->tempnam(DrupalFilesystem::getOsTemporaryDirectory(), '/build_workspace_' . md5($this->getName() . microtime(TRUE)));
    $fs->remove($this->workspaceDir);
    $fs->mkdir($this->workspaceDir);
    $this->initMink();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    $this->stopServer();
    foreach ($this->portLocks as $lock) {
      $lock->release();
    }
    $ws = $this->getWorkspaceDirectory();
    $fs = new SymfonyFilesystem();
    if ($this->destroyBuild && $fs->exists($ws)) {
      // Filter out symlinks as chmod cannot alter them.
      $finder = new Finder();
      $finder->in($ws)
        ->directories()
        ->ignoreVCS(FALSE)
        ->ignoreDotFiles(FALSE)
        // composer script is a symlink and fails chmod. Ignore it.
        ->notPath('/^vendor\/bin\/composer$/');
      $fs->chmod($finder->getIterator(), 0775, 0000);
      $fs->remove($ws);
    }
  }

  /**
   * Get the working directory within the workspace, creating if necessary.
   *
   * @param string $working_dir
   *   The path within the workspace directory.
   *
   * @return string
   *   The full path to the working directory within the workspace directory.
   */
  protected function getWorkingPath($working_dir = NULL) {
    $full_path = $this->getWorkspaceDirectory();
    if ($working_dir) {
      $full_path .= '/' . $working_dir;
    }
    if (!file_exists($full_path)) {
      $fs = new SymfonyFilesystem();
      $fs->mkdir($full_path);
    }
    return $full_path;
  }

  /**
   * Set up the Mink session manager.
   *
   * @return \Behat\Mink\Session
   */
  protected function initMink() {
    // If the Symfony BrowserKit client can followMetaRefresh(), we should use
    // the Goutte descendent instead of ours.
    if (method_exists(SymfonyClient::class, 'followMetaRefresh')) {
      $client = new Client();
    }
    else {
      $client = new DrupalMinkClient();
    }
    $client->followMetaRefresh(TRUE);
    $driver = new GoutteDriver($client);
    $session = new Session($driver);
    $this->mink = new Mink();
    $this->mink->registerSession('default', $session);
    $this->mink->setDefaultSessionName('default');
    $session->start();
    return $session;
  }

  /**
   * Get the Mink instance.
   *
   * Use the Mink object to perform assertions against the content returned by a
   * request.
   *
   * @return \Behat\Mink\Mink
   *   The Mink object.
   */
  public function getMink() {
    return $this->mink;
  }

  /**
   * Full path to the workspace where this test can build.
   *
   * This is often a directory within the system's temporary directory.
   *
   * @return string
   *   Full path to the workspace where this test can build.
   */
  public function getWorkspaceDirectory() {
    return $this->workspaceDir;
  }

  /**
   * Assert that text is present in the error output of the most recent command.
   *
   * @param string $expected
   *   Text we expect to find in the error output of the command.
   */
  public function assertErrorOutputContains($expected) {
    $this->assertContains($expected, $this->commandProcess->getErrorOutput());
  }

  /**
   * Assert that text is present in the output of the most recent command.
   *
   * @param string $expected
   *   Text we expect to find in the output of the command.
   */
  public function assertCommandOutputContains($expected) {
    $this->assertContains($expected, $this->commandProcess->getOutput());
  }

  /**
   * Asserts that the last command ran without error.
   *
   * This assertion checks whether the last command returned an exit code of 0.
   *
   * If you need to assert a different exit code, then you can use
   * executeCommand() and perform a different assertion on the process object.
   */
  public function assertCommandSuccessful() {
    return $this->assertCommandExitCode(0);
  }

  /**
   * Asserts that the last command returned the specified exit code.
   *
   * @param int $expected_code
   *   The expected process exit code.
   */
  public function assertCommandExitCode($expected_code) {
    $this->assertEquals($expected_code, $this->commandProcess->getExitCode(),
      'COMMAND: ' . $this->commandProcess->getCommandLine() . "\n" .
      'OUTPUT: ' . $this->commandProcess->getOutput() . "\n" .
      'ERROR: ' . $this->commandProcess->getErrorOutput() . "\n"
    );
  }

  /**
   * Run a command.
   *
   * @param string $command_line
   *   A command line to run in an isolated process.
   * @param string $working_dir
   *   (optional) A working directory relative to the workspace, within which to
   *   execute the command. Defaults to the workspace directory.
   *
   * @return \Symfony\Component\Process\Process
   */
  public function executeCommand($command_line, $working_dir = NULL) {
    $this->commandProcess = new Process($command_line);
    $this->commandProcess->setWorkingDirectory($this->getWorkingPath($working_dir))
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $this->commandProcess->run();
    return $this->commandProcess;
  }

  /**
   * Helper function to assert that the last visit was a Drupal site.
   *
   * This method asserts that the X-Generator header shows that the site is a
   * Drupal site.
   */
  public function assertDrupalVisit() {
    $this->getMink()->assertSession()->responseHeaderMatches('X-Generator', '/Drupal \d+ \(https:\/\/www.drupal.org\)/');
  }

  /**
   * Visit a URI on the HTTP server.
   *
   * The concept here is that there could be multiple potential docroots in the
   * workspace, so you can use whichever ones you want.
   *
   * @param string $request_uri
   *   (optional) The non-host part of the URL. Example: /some/path?foo=bar.
   *   Defaults to visiting the homepage.
   * @param string $working_dir
   *   (optional) Relative path within the test workspace file system that will
   *   be the docroot for the request. Defaults to the workspace directory.
   *
   * @return \Behat\Mink\Mink
   *   The Mink object. Perform assertions against this.
   *
   * @throws \InvalidArgumentException
   *   Thrown when $request_uri does not start with a slash.
   */
  public function visit($request_uri = '/', $working_dir = NULL) {
    if ($request_uri[0] !== '/') {
      throw new \InvalidArgumentException('URI: ' . $request_uri . ' must be relative. Example: /some/path?foo=bar');
    }
    // Try to make a server.
    $this->standUpServer($working_dir);

    $request = 'http://localhost:' . $this->getPortNumber() . $request_uri;
    $this->mink->getSession()->visit($request);
    return $this->mink;
  }

  /**
   * Makes a local test server using PHP's internal HTTP server.
   *
   * Test authors should call visit() or assertVisit() instead.
   *
   * @param string|null $working_dir
   *   (optional) Server docroot relative to the workspace file system. Defaults
   *   to the workspace directory.
   */
  protected function standUpServer($working_dir = NULL) {
    // If the user wants to test a new docroot, we have to shut down the old
    // server process and generate a new port number.
    if ($working_dir !== $this->serverDocroot && !empty($this->serverProcess)) {
      $this->stopServer();
    }
    // If there's not a server at this point, make one.
    if (!$this->serverProcess || $this->serverProcess->isTerminated()) {
      $this->serverProcess = $this->instantiateServer($this->getPortNumber(), $working_dir);
      if ($this->serverProcess) {
        $this->serverDocroot = $working_dir;
      }
    }
  }

  /**
   * Do the work of making a server process.
   *
   * Test authors should call visit() or assertVisit() instead.
   *
   * When initializing the server, if '.ht.router.php' exists in the root, it is
   * leveraged. If testing with a version of Drupal before 8.5.x., this file
   * does not exist.
   *
   * @param int $port
   *   The port number for the server.
   * @param string|null $working_dir
   *   (optional) Server docroot relative to the workspace filesystem. Defaults
   *   to the workspace directory.
   *
   * @return \Symfony\Component\Process\Process
   *   The server process.
   *
   * @throws \RuntimeException
   *   Thrown if we were unable to start a web server.
   */
  protected function instantiateServer($port, $working_dir = NULL) {
    $finder = new PhpExecutableFinder();
    $working_path = $this->getWorkingPath($working_dir);
    $server = [
      $finder->find(),
      '-S',
      self::$hostName . ':' . $port,
      '-t',
      $working_path,
    ];
    if (file_exists($working_path . DIRECTORY_SEPARATOR . '.ht.router.php')) {
      $server[] = $working_path . DIRECTORY_SEPARATOR . '.ht.router.php';
    }
    $ps = new Process($server, $working_path);
    $ps->setIdleTimeout(30)
      ->setTimeout(30)
      ->start();
    // Wait until the web server has started. It is started if the port is no
    // longer available.
    for ($i = 0; $i < 1000; $i++) {
      if (!$this->checkPortIsAvailable($port)) {
        return $ps;
      }
      usleep(1000);
    }
    throw new \RuntimeException(sprintf("Unable to start the web server.\nERROR OUTPUT:\n%s", $ps->getErrorOutput()));
  }

  /**
   * Stop the HTTP server, zero out all necessary variables.
   */
  protected function stopServer() {
    if (!empty($this->serverProcess)) {
      $this->serverProcess->stop();
    }
    $this->serverProcess = NULL;
    $this->serverDocroot = NULL;
    $this->hostPort = NULL;
    $this->initMink();
  }

  /**
   * Discover an available port number.
   *
   * @return int
   *   The available port number that we discovered.
   *
   * @throws \RuntimeException
   *   Thrown when there are no available ports within the range.
   */
  protected function findAvailablePort() {
    $store = new FlockStore(DrupalFilesystem::getOsTemporaryDirectory());
    $lock_factory = new Factory($store);

    $counter = 100;
    while ($counter--) {
      // Limit to 9999 as higher ports cause random fails on DrupalCI.
      $port = random_int(1024, 9999);

      if (isset($this->portLocks[$port])) {
        continue;
      }

      // Take a lock so that no other process can use the same port number even
      // if the server is yet to start.
      $lock = $lock_factory->createLock('drupal-build-test-port-' . $port);
      if ($lock->acquire()) {
        if ($this->checkPortIsAvailable($port)) {
          $this->portLocks[$port] = $lock;
          return $port;
        }
        else {
          $lock->release();
        }
      }
    }
    throw new \RuntimeException('Unable to find a port available to run the web server.');
  }

  /**
   * Checks whether a port is available.
   *
   * @param $port
   *   A number between 1024 and 65536.
   *
   * @return bool
   */
  protected function checkPortIsAvailable($port) {
    $fp = @fsockopen(self::$hostName, $port, $errno, $errstr, 1);
    // If fsockopen() fails to connect, probably nothing is listening.
    // It could be a firewall but that's impossible to detect, so as a
    // best guess let's return it as available.
    if ($fp === FALSE) {
      return TRUE;
    }
    else {
      fclose($fp);
    }
    return FALSE;
  }

  /**
   * Get the port number for requests.
   *
   * Test should never call this. Used by standUpServer().
   *
   * @return int
   */
  protected function getPortNumber() {
    if (empty($this->hostPort)) {
      $this->hostPort = $this->findAvailablePort();
    }
    return $this->hostPort;
  }

  /**
   * Copy the current working codebase into a workspace.
   *
   * Use this method to copy the current codebase, including any patched
   * changes, into the workspace.
   *
   * By default, the copy will exclude sites/default/settings.php,
   * sites/default/files, and vendor/. Use the $iterator parameter to override
   * this behavior.
   *
   * @param \Iterator|null $iterator
   *   (optional) An iterator of all the files to copy. Default behavior is to
   *   exclude site-specific directories and files.
   * @param string|null $working_dir
   *   (optional) Relative path within the test workspace file system that will
   *   contain the copy of the codebase. Defaults to the workspace directory.
   */
  public function copyCodebase(\Iterator $iterator = NULL, $working_dir = NULL) {
    $working_path = $this->getWorkingPath($working_dir);

    if ($iterator === NULL) {
      $iterator = $this->getCodebaseFinder()->getIterator();
    }

    $fs = new SymfonyFilesystem();
    $options = ['override' => TRUE, 'delete' => FALSE];
    $fs->mirror($this->getDrupalRoot(), $working_path, $iterator, $options);
  }

  /**
   * Get a default Finder object for a Drupal codebase.
   *
   * This method can be used two ways:
   * - Override this method and provide your own default Finder object for
   *   copyCodebase().
   * - Call the method to get a default Finder object which can then be
   *   modified for other purposes.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder object ready to iterate over core codebase.
   */
  public function getCodebaseFinder() {
    $finder = new Finder();
    $finder->files()
      ->ignoreUnreadableDirs()
      ->in($this->getDrupalRoot())
      ->notPath('#^sites/default/files#')
      ->notPath('#^sites/simpletest#')
      ->notPath('#^vendor#')
      ->notPath('#^sites/default/settings\..*php#')
      ->ignoreDotFiles(FALSE)
      ->ignoreVCS(FALSE);
    return $finder;
  }

  /**
   * Get the root path of this Drupal codebase.
   *
   * @return string
   *   The full path to the root of this Drupal codebase.
   */
  protected function getDrupalRoot() {
    return realpath(dirname(__DIR__, 5));
  }

}
