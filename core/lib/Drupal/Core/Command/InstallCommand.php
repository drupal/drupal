<?php

namespace Drupal\Core\Command;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParserDynamic;
use Drupal\Core\Site\Settings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Installs a Drupal site for local testing/development.
 *
 * @internal
 *   This command makes no guarantee of an API for Drupal extensions.
 */
class InstallCommand extends Command {

  /**
   * The class loader.
   *
   * @var object
   */
  protected $classLoader;

  /**
   * Constructs a new InstallCommand command.
   *
   * @param object $class_loader
   *   The class loader.
   */
  public function __construct($class_loader) {
    parent::__construct('install');
    $this->classLoader = $class_loader;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('install')
      ->setDescription('Installs a Drupal demo site. This is not meant for production and might be too simple for custom development. It is a quick and easy way to get Drupal running.')
      ->addArgument('install-profile', InputArgument::OPTIONAL, 'Install profile to install the site in.')
      ->addOption('langcode', NULL, InputOption::VALUE_OPTIONAL, 'The language to install the site in.', 'en')
      ->addOption('site-name', NULL, InputOption::VALUE_OPTIONAL, 'Set the site name.', 'Drupal')
      ->addUsage('demo_umami --langcode fr')
      ->addUsage('standard --site-name QuickInstall');

    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    if (!extension_loaded('pdo_sqlite')) {
      $io->getErrorStyle()->error('You must have the pdo_sqlite PHP extension installed. See core/INSTALL.sqlite.txt for instructions.');
      return 1;
    }

    // Change the directory to the Drupal root.
    chdir(dirname(dirname(dirname(dirname(dirname(__DIR__))))));

    // Check whether there is already an installation.
    if ($this->isDrupalInstalled()) {
      // Do not fail if the site is already installed so this command can be
      // chained with ServerCommand.
      $output->writeln('<info>Drupal is already installed.</info> If you want to reinstall, remove sites/default/files and sites/default/settings.php.');
      return 0;
    }

    $install_profile = $input->getArgument('install-profile');
    if ($install_profile && !$this->validateProfile($install_profile, $io)) {
      return 1;
    }
    if (!$install_profile) {
      $install_profile = $this->selectProfile($io);
    }

    return $this->install($this->classLoader, $io, $install_profile, $input->getOption('langcode'), $this->getSitePath(), $input->getOption('site-name'));
  }

  /**
   * Returns whether there is already an existing Drupal installation.
   *
   * @return bool
   */
  protected function isDrupalInstalled() {
    try {
      $kernel = new DrupalKernel('prod', $this->classLoader, FALSE);
      $kernel::bootEnvironment();
      $kernel->setSitePath($this->getSitePath());
      Settings::initialize($kernel->getAppRoot(), $kernel->getSitePath(), $this->classLoader);
      $kernel->boot();
    }
    catch (ConnectionNotDefinedException $e) {
      return FALSE;
    }
    return !empty(Database::getConnectionInfo());
  }

  /**
   * Installs Drupal with specified installation profile.
   *
   * @param object $class_loader
   *   The class loader.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The Symfony output decorator.
   * @param string $profile
   *   The installation profile to use.
   * @param string $langcode
   *   The language to install the site in.
   * @param string $site_path
   *   The path to install the site to, like 'sites/default'.
   * @param string $site_name
   *   The site name.
   *
   * @throws \Exception
   *   Thrown when failing to create the $site_path directory or settings.php.
   */
  protected function install($class_loader, SymfonyStyle $io, $profile, $langcode, $site_path, $site_name) {
    $password = Crypt::randomBytesBase64(12);
    $parameters = [
      'interactive' => FALSE,
      'site_path' => $site_path,
      'parameters' => [
        'profile' => $profile,
        'langcode' => $langcode,
      ],
      'forms' => [
        'install_settings_form' => [
          'driver' => 'sqlite',
          'sqlite' => [
            'database' => $site_path . '/files/.sqlite',
          ],
        ],
        'install_configure_form' => [
          'site_name' => $site_name,
          'site_mail' => 'drupal@localhost',
          'account' => [
            'name' => 'admin',
            'mail' => 'admin@localhost',
            'pass' => [
              'pass1' => $password,
              'pass2' => $password,
            ],
          ],
          'enable_update_status_module' => TRUE,
          // form_type_checkboxes_value() requires NULL instead of FALSE values
          // for programmatic form submissions to disable a checkbox.
          'enable_update_status_emails' => NULL,
        ],
      ],
    ];

    // Create the directory and settings.php if not there so that the installer
    // works.
    if (!is_dir($site_path)) {
      if ($io->isVerbose()) {
        $io->writeln("Creating directory: $site_path");
      }
      if (!mkdir($site_path, 0775)) {
        throw new \RuntimeException("Failed to create directory $site_path");
      }
    }
    if (!file_exists("{$site_path}/settings.php")) {
      if ($io->isVerbose()) {
        $io->writeln("Creating file: {$site_path}/settings.php");
      }
      if (!copy('sites/default/default.settings.php', "{$site_path}/settings.php")) {
        throw new \RuntimeException("Copying sites/default/default.settings.php to {$site_path}/settings.php failed.");
      }
    }

    require_once 'core/includes/install.core.inc';

    $progress_bar = $io->createProgressBar();
    install_drupal($class_loader, $parameters, function ($install_state) use ($progress_bar) {
      static $started = FALSE;
      if (!$started) {
        $started = TRUE;
        // We've already done 1.
        $progress_bar->setFormat("%current%/%max% [%bar%]\n%message%\n");
        $progress_bar->setMessage(t('Installing @drupal', ['@drupal' => drupal_install_profile_distribution_name()]));
        $tasks = install_tasks($install_state);
        $progress_bar->start(count($tasks) + 1);
      }
      $tasks_to_perform = install_tasks_to_perform($install_state);
      $task = current($tasks_to_perform);
      if (isset($task['display_name'])) {
        $progress_bar->setMessage($task['display_name']);
      }
      $progress_bar->advance();
    });
    $success_message = t('Congratulations, you installed @drupal!', [
      '@drupal' => drupal_install_profile_distribution_name(),
      '@name' => 'admin',
      '@pass' => $password,
    ], ['langcode' => $langcode]);
    $progress_bar->setMessage('<info>' . $success_message . '</info>');
    $progress_bar->display();
    $progress_bar->finish();
    $io->writeln('<info>Username:</info> admin');
    $io->writeln("<info>Password:</info> $password");
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

  /**
   * Selects the install profile to use.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   Symfony style output decorator.
   *
   * @return string
   *   The selected install profile.
   *
   * @see _install_select_profile()
   * @see \Drupal\Core\Installer\Form\SelectProfileForm
   */
  protected function selectProfile(SymfonyStyle $io) {
    $profiles = $this->getProfiles();

    // If there is a distribution there will be only one profile.
    if (count($profiles) == 1) {
      return key($profiles);
    }
    // Display alphabetically by human-readable name, but always put the core
    // profiles first (if they are present in the filesystem).
    natcasesort($profiles);
    if (isset($profiles['minimal'])) {
      // If the expert ("Minimal") core profile is present, put it in front of
      // any non-core profiles rather than including it with them
      // alphabetically, since the other profiles might be intended to group
      // together in a particular way.
      $profiles = ['minimal' => $profiles['minimal']] + $profiles;
    }
    if (isset($profiles['standard'])) {
      // If the default ("Standard") core profile is present, put it at the very
      // top of the list. This profile will have its radio button pre-selected,
      // so we want it to always appear at the top.
      $profiles = ['standard' => $profiles['standard']] + $profiles;
    }
    reset($profiles);
    return $io->choice('Select an installation profile', $profiles, current($profiles));
  }

  /**
   * Validates a user provided install profile.
   *
   * @param string $install_profile
   *   Install profile to validate.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   Symfony style output decorator.
   *
   * @return bool
   *   TRUE if the profile is valid, FALSE if not.
   */
  protected function validateProfile($install_profile, SymfonyStyle $io) {
    // Allow people to install hidden and non-distribution profiles if they
    // supply the argument.
    $profiles = $this->getProfiles(TRUE, FALSE);
    if (!isset($profiles[$install_profile])) {
      $error_msg = sprintf("'%s' is not a valid install profile.", $install_profile);
      $alternatives = [];
      foreach (array_keys($profiles) as $profile_name) {
        $lev = levenshtein($install_profile, $profile_name);
        if ($lev <= strlen($profile_name) / 4 || FALSE !== strpos($profile_name, $install_profile)) {
          $alternatives[] = $profile_name;
        }
      }
      if (!empty($alternatives)) {
        $error_msg .= sprintf(" Did you mean '%s'?", implode("' or '", $alternatives));
      }
      $io->getErrorStyle()->error($error_msg);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Gets a list of profiles.
   *
   * @param bool $include_hidden
   *   (optional) Whether to include hidden profiles. Defaults to FALSE.
   * @param bool $auto_select_distributions
   *   (optional) Whether to only return the first distribution found.
   *
   * @return string[]
   *   An array of profile descriptions keyed by the profile machine name.
   */
  protected function getProfiles($include_hidden = FALSE, $auto_select_distributions = TRUE) {
    // Build a list of all available profiles.
    $listing = new ExtensionDiscovery(getcwd(), FALSE);
    $listing->setProfileDirectories([]);
    $profiles = [];
    $info_parser = new InfoParserDynamic(getcwd());
    foreach ($listing->scan('profile') as $profile) {
      $details = $info_parser->parse($profile->getPathname());
      // Don't show hidden profiles.
      if (!$include_hidden && !empty($details['hidden'])) {
        continue;
      }
      // Determine the name of the profile; default to the internal name if none
      // is specified.
      $name = isset($details['name']) ? $details['name'] : $profile->getName();
      $description = isset($details['description']) ? $details['description'] : $name;
      $profiles[$profile->getName()] = $description;

      if ($auto_select_distributions && !empty($details['distribution'])) {
        return [$profile->getName() => $description];
      }
    }
    return $profiles;
  }

}
