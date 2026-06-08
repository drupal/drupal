<?php

declare(strict_types=1);

namespace Drupal\system\Command;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

/**
 * Prints status information about the site.
 *
 * @internal
 */
#[AsCommand(
  name: 'system:status',
  description: 'Show system status.',
  aliases: [
    'status',
  ],
)]
class StatusCommand {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $config,
  ) {}

  /**
   * Show system status.
   */
  public function __invoke(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $rows = [
      [(string) $this->t('Drupal version') => ': ' . \Drupal::VERSION],
      [(string) $this->t('Site URL') => ': ' . Url::fromRoute('<front>')->setAbsolute()->toString()],
      [(string) $this->t('PHP binary') => ': ' . Path::canonicalize(PHP_BINARY)],
      [(string) $this->t('PHP version') => ': ' . PHP_VERSION],
      [(string) $this->t('PHP OS') => ': ' . PHP_OS],
      [(string) $this->t('Default theme') => ': ' . $this->config->get('system.theme')->get('default')],
      [(string) $this->t('Admin theme') => ': ' . $this->config->get('system.theme')->get('admin')],
    ];
    $io->definitionList(...$rows);
    return Command::SUCCESS;
  }

}
