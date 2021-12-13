<?php

namespace Drupal\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs a Drupal site and starts a webserver for local testing/development.
 *
 * Wraps 'install' and 'server' commands.
 *
 * @internal
 *   This command makes no guarantee of an API for Drupal extensions.
 *
 * @see \Drupal\Core\Command\InstallCommand
 * @see \Drupal\Core\Command\ServerCommand
 */
class QuickStartCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('quick-start')
      ->setDescription('Installs a Drupal site and runs a web server. This is not meant for production and might be too simple for custom development. It is a quick and easy way to get Drupal running.')
      ->addArgument('install-profile', InputArgument::OPTIONAL, 'Install profile to install the site in.')
      ->addOption('langcode', NULL, InputOption::VALUE_OPTIONAL, 'The language to install the site in. Defaults to en.', 'en')
      ->addOption('site-name', NULL, InputOption::VALUE_OPTIONAL, 'Set the site name. Defaults to Drupal.', 'Drupal')
      ->addOption('host', NULL, InputOption::VALUE_OPTIONAL, 'Provide a host for the server to run on. Defaults to 127.0.0.1.', '127.0.0.1')
      ->addOption('port', NULL, InputOption::VALUE_OPTIONAL, 'Provide a port for the server to run on. Will be determined automatically if none supplied.')
      ->addOption('suppress-login', 's', InputOption::VALUE_NONE, 'Disable opening a login URL in a browser.')
      ->addUsage('demo_umami --langcode fr')
      ->addUsage('standard --site-name QuickInstall --host localhost --port 8080')
      ->addUsage('minimal --host my-site.com --port 80');

    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $command = $this->getApplication()->find('install');

    $arguments = [
      'command' => 'install',
      'install-profile' => $input->getArgument('install-profile'),
      '--langcode' => $input->getOption('langcode'),
      '--site-name' => $input->getOption('site-name'),
    ];

    $installInput = new ArrayInput($arguments);
    $returnCode = $command->run($installInput, $output);

    if ($returnCode === 0) {
      $command = $this->getApplication()->find('server');
      $arguments = [
        'command' => 'server',
        '--host' => $input->getOption('host'),
        '--port' => $input->getOption('port'),
      ];
      if ($input->getOption('suppress-login')) {
        $arguments['--suppress-login'] = TRUE;
      }
      $serverInput = new ArrayInput($arguments);
      $returnCode = $command->run($serverInput, $output);
    }
    return $returnCode;
  }

}
