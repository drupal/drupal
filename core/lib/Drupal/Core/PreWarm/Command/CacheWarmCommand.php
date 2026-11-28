<?php

declare(strict_types=1);

namespace Drupal\Core\PreWarm\Command;

use Drupal\Core\PreWarm\CachePreWarmerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Pre-warms all caches.
 *
 * @internal
 */
#[AsCommand(
  name: 'cache:warm',
  description: 'Pre-warm available caches.',
  aliases: ['cw'],
)]
class CacheWarmCommand {

  public function __construct(
    protected CachePreWarmerInterface $warmer,
  ) {}

  /**
   * Pre-warms all caches.
   */
  public function __invoke(InputInterface $input, OutputInterface $output): int {
    $this->warmer->preWarmAllCaches();
    (new SymfonyStyle($input, $output))->success('Warmed caches.');
    return Command::SUCCESS;
  }

}
