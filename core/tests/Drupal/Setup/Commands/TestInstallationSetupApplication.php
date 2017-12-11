<?php

namespace Drupal\Setup\Commands;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Application wrapper for TestInstallationSetupCommand.
 *
 * @internal
 */
class TestInstallationSetupApplication extends Application {

  /**
   * SetupDrupalApplication constructor.
   */
  public function __construct() {
    parent::__construct('setup-drupal-test', '1.0.0');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCommandName(InputInterface $input) {
    return 'setup-drupal-test';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCommands() {
    // Even though this is a single command, keep the HelpCommand (--help).
    $default_commands = parent::getDefaultCommands();
    $default_commands[] = new TestInstallationSetupCommand();
    return $default_commands;
  }

}
