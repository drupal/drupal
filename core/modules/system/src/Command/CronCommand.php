<?php

declare(strict_types=1);

namespace Drupal\system\Command;

use Drupal\Core\CronInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs cron implementations.
 *
 * @internal
 */
#[AsCommand(
  name: 'system:cron',
  description: 'Runs cron implementations.',
  aliases: [
    'cron',
    'core:cron',
  ],
)]
class CronCommand {

  public function __construct(
    protected CronInterface $cron,
  ) {}

  /**
   * Runs cron implementations.
   */
  public function __invoke(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    if ($this->cron->run()) {
      $io->success('Cron ran successfully.');
      return Command::SUCCESS;
    }
    $io->error('Cron run failed.');
    return Command::FAILURE;
  }

}
