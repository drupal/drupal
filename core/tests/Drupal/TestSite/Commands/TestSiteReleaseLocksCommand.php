<?php

declare(strict_types=1);

namespace Drupal\TestSite\Commands;

use Drupal\Core\Test\TestDatabase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to release all test site database prefix locks.
 *
 * Note that this command can't be safely tested by DrupalCI without potentially
 * causing random failures.
 *
 * @internal
 */
class TestSiteReleaseLocksCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('release-locks')
      ->setDescription('Releases all test site locks')
      ->setHelp('The locks ensure test site database prefixes are not reused.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $root = dirname(__DIR__, 5);
    chdir($root);
    TestDatabase::releaseAllTestLocks();
    $output->writeln('<info>Successfully released all the test database locks</info>');
    return 0;
  }

}
