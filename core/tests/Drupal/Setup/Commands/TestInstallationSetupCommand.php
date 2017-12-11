<?php

namespace Drupal\Setup\Commands;

use Drupal\Setup\TestInstallationSetup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony console command to setup Drupal.
 *
 * @internal
 */
class TestInstallationSetupCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('setup-drupal-test')
      ->addOption('setup_file', NULL, InputOption::VALUE_OPTIONAL)
      ->addOption('db_url', NULL, InputOption::VALUE_OPTIONAL, '', getenv('SIMPLETEST_DB'))
      ->addOption('base_url', NULL, InputOption::VALUE_OPTIONAL, '', getenv('SIMPLETEST_BASE_URL'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $test = new TestInstallationSetup();
    $test->setup('testing', $input->getOption('setup_file'));

    $db_url = $input->getOption('db_url');
    $base_url = $input->getOption('base_url');
    putenv("SIMPLETEST_DB=$db_url");
    putenv("SIMPLETEST_BASE_URL=$base_url");

    $output->writeln(drupal_generate_test_ua($test->getDatabasePrefix()));
  }

}
