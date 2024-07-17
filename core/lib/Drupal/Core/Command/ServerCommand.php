<?php

namespace Drupal\Core\Command;

use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\DrupalKernel;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Site\Settings;
use Drupal\user\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

/**
 * Runs the PHP webserver for a Drupal site for local testing/development.
 *
 * @internal
 *   This command makes no guarantee of an API for Drupal extensions.
 */
class ServerCommand extends Command {

  /**
   * The class loader.
   *
   * @var object
   */
  protected $classLoader;

  /**
   * Constructs a new ServerCommand command.
   *
   * @param object $class_loader
   *   The class loader.
   */
  public function __construct($class_loader) {
    parent::__construct('server');
    $this->classLoader = $class_loader;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Starts up a webserver for a site.')
      ->addOption('host', NULL, InputOption::VALUE_OPTIONAL, 'Provide a host for the server to run on.', '127.0.0.1')
      ->addOption('port', NULL, InputOption::VALUE_OPTIONAL, 'Provide a port for the server to run on. Will be determined automatically if none supplied.')
      ->addOption('suppress-login', 's', InputOption::VALUE_NONE, 'Disable opening a login URL in a browser.')
      ->addUsage('--host localhost --port 8080')
      ->addUsage('--host my-site.com --port 80');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $host = $input->getOption('host');
    $port = $input->getOption('port');
    if (!$port) {
      $port = $this->findAvailablePort($host);
    }
    if (!$port) {
      $io->getErrorStyle()->error('Unable to automatically determine a port. Use the --port to hardcode an available port.');
    }

    try {
      $kernel = $this->boot();
    }
    catch (ConnectionNotDefinedException) {
      $io->getErrorStyle()->error("No installation found. Use the 'install' command.");
      return 1;
    }
    return $this->start($host, $port, $kernel, $input, $io);
  }

  /**
   * Boots up a Drupal environment.
   *
   * @return \Drupal\Core\DrupalKernelInterface
   *   The Drupal kernel.
   *
   * @throws \Exception
   *   Exception thrown if kernel does not boot.
   */
  protected function boot() {
    $kernel = new DrupalKernel('prod', $this->classLoader, FALSE);
    $kernel::bootEnvironment();
    $kernel->setSitePath($this->getSitePath());
    Settings::initialize($kernel->getAppRoot(), $kernel->getSitePath(), $this->classLoader);
    $kernel->boot();
    // Some services require a request to work. For example, CommentManager.
    // This is needed as generating the URL fires up entity load hooks.
    $kernel->getContainer()
      ->get('request_stack')
      ->push(Request::createFromGlobals());

    return $kernel;
  }

  /**
   * Finds an available port.
   *
   * @param string $host
   *   The host to find a port on.
   *
   * @return int|false
   *   The available port or FALSE, if no available port found,
   */
  protected function findAvailablePort($host) {
    $port = 8888;
    while ($port >= 8888 && $port <= 9999) {
      $connection = @fsockopen($host, $port);
      if (is_resource($connection)) {
        // Port is being used.
        fclose($connection);
      }
      else {
        // Port is available.
        return $port;
      }
      $port++;
    }
    return FALSE;
  }

  /**
   * Opens a URL in your system default browser.
   *
   * @param string $url
   *   The URL to browser to.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The IO.
   */
  protected function openBrowser($url, SymfonyStyle $io) {
    $is_windows = defined('PHP_WINDOWS_VERSION_BUILD');
    if ($is_windows) {
      // Handle escaping ourselves.
      $cmd = 'start "web" "' . $url . '""';
    }
    else {
      $url = escapeshellarg($url);
    }

    $is_linux = Process::fromShellCommandline('which xdg-open')->run();
    $is_osx = Process::fromShellCommandline('which open')->run();
    if ($is_linux === 0) {
      $cmd = 'xdg-open ' . $url;
    }
    elseif ($is_osx === 0) {
      $cmd = 'open ' . $url;
    }

    if (empty($cmd)) {
      $io->getErrorStyle()
        ->error('No suitable browser opening command found, open yourself: ' . $url);
      return;
    }

    if ($io->isVerbose()) {
      $io->writeln("<info>Browser command:</info> $cmd");
    }

    // Need to escape double quotes in the command so the PHP will work.
    $cmd = str_replace('"', '\"', $cmd);
    // Sleep for 2 seconds before opening the browser. This allows the command
    // to start up the PHP built-in webserver in the meantime. We use a
    // PhpProcess so that Windows powershell users also get a browser opened
    // for them.
    $php = "<?php sleep(2); passthru(\"$cmd\"); ?>";
    $process = new PhpProcess($php);
    $process->start();
  }

  /**
   * Gets a one time login URL for user 1.
   *
   * @return string
   *   The one time login URL for user 1.
   */
  protected function getOneTimeLoginUrl() {
    $user = User::load(1);
    \Drupal::moduleHandler()->load('user');
    return user_pass_reset_url($user);
  }

  /**
   * Starts up a webserver with a running Drupal.
   *
   * @param string $host
   *   The hostname of the webserver.
   * @param int $port
   *   The port to start the webserver on.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The Drupal kernel.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The IO.
   *
   * @return int
   *   The exit status of the PHP in-built webserver command.
   */
  protected function start($host, $port, DrupalKernelInterface $kernel, InputInterface $input, SymfonyStyle $io) {
    $finder = new PhpExecutableFinder();
    $binary = $finder->find();
    if ($binary === FALSE) {
      throw new \RuntimeException('Unable to find the PHP binary.');
    }

    $io->writeln("<info>Drupal development server started:</info> <http://{$host}:{$port}>");
    $io->writeln('<info>This server is not meant for production use.</info>');
    $one_time_login = "http://$host:$port{$this->getOneTimeLoginUrl()}/login";
    $io->writeln("<info>One time login url:</info> <$one_time_login>");
    $io->writeln('Press Ctrl-C to quit the Drupal development server.');

    if (!$input->getOption('suppress-login')) {
      if ($this->openBrowser("$one_time_login?destination=" . urlencode("/"), $io) === 1) {
        $io->error('Error while opening up a one time login URL');
      }
    }

    // Use the Process object to construct an escaped command line.
    $process = new Process([
      $binary,
      '-S',
      $host . ':' . $port,
      '.ht.router.php',
    ], $kernel->getAppRoot(), [], NULL, NULL);
    if ($io->isVerbose()) {
      $io->writeln("<info>Server command:</info> {$process->getCommandLine()}");
    }

    // Carefully manage output so we can display output only in verbose mode.
    $descriptors = [];
    $descriptors[0] = STDIN;
    $descriptors[1] = ['pipe', 'w'];
    $descriptors[2] = ['pipe', 'w'];
    $server = proc_open($process->getCommandLine(), $descriptors, $pipes, $kernel->getAppRoot());
    if (is_resource($server)) {
      if ($io->isVerbose()) {
        // Write a blank line so that server output and the useful information are
        // visually separated.
        $io->writeln('');
      }
      $server_status = proc_get_status($server);
      while ($server_status['running']) {
        if ($io->isVerbose()) {
          fpassthru($pipes[2]);
        }
        sleep(1);
        $server_status = proc_get_status($server);
      }
    }
    return proc_close($server);
  }

  /**
   * Gets the site path.
   *
   * Defaults to 'sites/default'. For testing purposes this can be overridden
   * using the DRUPAL_DEV_SITE_PATH environment variable.
   *
   * @return string
   *   The site path to use.
   */
  protected function getSitePath() {
    return getenv('DRUPAL_DEV_SITE_PATH') ?: 'sites/default';
  }

}
