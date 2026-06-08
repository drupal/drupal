<?php

declare(strict_types=1);

namespace Drupal\console_test\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * An example private command.
 *
 * A command is considered private when it does not have an attribute or name
 * via configure().
 *
 * @internal
 */
class ConsoleExamplePrivateCommand extends Command {

  /**
   * {@inheritdoc}
   */
  public function getName(): ?string {
    return 'example:command-private';
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $io->success('Done with private command.');

    return Command::SUCCESS;
  }

}
