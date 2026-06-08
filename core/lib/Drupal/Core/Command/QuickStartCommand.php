<?php

declare(strict_types=1);

namespace Drupal\Core\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs a Drupal site and starts a webserver for local testing/development.
 *
 * Wraps 'install' and 'server' commands.
 *
 * This is not meant for production. It can be used as a quick and easy way to
 * create a Drupal test site.
 *
 * @see \Drupal\Core\Command\InstallCommand
 * @see \Drupal\Core\Command\ServerCommand
 * @internal
 *   This command makes no guarantee of an API for Drupal extensions.
 */
#[AsCommand(
  name: 'quick-start',
  description: 'Installs a Drupal site and starts a web server for local testing or development.',
  usages: [
    'demo_umami --langcode fr',
    'standard --site-name QuickInstall --host localhost --port 8080',
    'minimal --host my-site.com --port 80',
    'core/recipes/standard --site-name MyDrupalRecipe',
  ],
)]
class QuickStartCommand {

  /**
   * Installs a Drupal site and starts a webserver for local development.
   */
  public function __invoke(
    Application $application,
    OutputInterface $output,
    #[Argument('Install profile or recipe directory from which to install the site.')]
    #[Ask('Install profile or recipe directory', 'standard')]
    string $install_profile_or_recipe,
    #[Option('The language to install the site in. Defaults to \'en\'.')]
    string $langcode = 'en',
    #[Option('Set the administrator password. Defaults to a randomly generated password.')]
    ?string $password = NULL,
    #[Option('Set the site name.')]
    string $site_name = 'Drupal',
    #[Option('Provide a host for the server to run on.')]
    string $host = '127.0.0.1',
    #[Option('Provide a port for the server to run on (determined automatically if none is supplied).')]
    ?string $port = NULL,
    #[Option('Disable opening a login URL in a browser.', '', 's')]
    bool $suppress_login = FALSE,
  ): int {
    $command = $application->find('install');

    $arguments = [
      'command' => 'install',
      'install-profile-or-recipe' => $install_profile_or_recipe,
      '--langcode' => $langcode,
      '--password' => $password,
      '--site-name' => $site_name,
    ];

    $installInput = new ArrayInput($arguments);
    $returnCode = $command->run($installInput, $output);

    if ($returnCode === 0) {
      $command = $application->find('server');
      $arguments = [
        'command' => 'server',
        '--host' => $host,
        '--port' => $port,
      ];
      if ($suppress_login) {
        $arguments['--suppress-login'] = TRUE;
      }
      $serverInput = new ArrayInput($arguments);
      $returnCode = $command->run($serverInput, $output);
    }
    return $returnCode;
  }

}
