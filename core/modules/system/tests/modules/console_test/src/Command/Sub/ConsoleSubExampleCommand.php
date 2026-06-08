<?php

declare(strict_types=1);

namespace Drupal\console_test\Command\Sub;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * An example command in a subdirectory.
 *
 * @internal
 */
#[AsCommand(
  name: 'example:sub:command',
  description: 'An example command in a subdirectory.',
)]
class ConsoleSubExampleCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $io->success('Done.');
    return Command::SUCCESS;
  }

}
