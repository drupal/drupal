<?php

declare(strict_types=1);

namespace Drupal\console_test\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * An example command using configure() instead of the AsCommand attribute.
 *
 * @internal
 */
class ConsoleExampleConfigureCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('example:command-configured');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $io->success('Done with configured command.');

    return Command::SUCCESS;
  }

}
