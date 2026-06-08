<?php

declare(strict_types=1);

namespace Drupal\console_test\Command;

use Drupal\autowire_test\TestService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * An example command.
 *
 * @internal
 */
#[AsCommand(
  name: 'example:command',
  description: 'An example command.',
)]
class ConsoleExampleCommand extends Command {

  /**
   * Constructs a command with autowiring.
   */
  public function __construct(
    protected readonly TestService $testService,
    #[Autowire(service: 'logger.channel.default')]
    protected LoggerInterface $logger,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->addArgument('argument-test', mode: InputArgument::OPTIONAL)
      ->addOption('option-test', mode: InputOption::VALUE_NONE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $this->logger->notice('Option test: ' . ($input->getOption('option-test') ? 'Yes' : 'No'));
    $this->logger->notice('Argument test: ' . ($input->getArgument('argument-test') ? 'Yes' : 'No'));
    $this->logger->notice('Dependency injection test: ' . $this->testService->getTestInjection()::class);
    $io->success('Done.');

    return Command::SUCCESS;
  }

}
