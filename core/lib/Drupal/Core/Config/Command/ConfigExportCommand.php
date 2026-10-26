<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Command;

use Drupal\Core\Command\Exception\UserAbortException;
use Drupal\Core\Config\ConfigDirectoryNotDefinedException;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\SyncFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Export configuration.
 *
 * @internal
 */
#[AsCommand(
  name: 'config:export',
  description: 'Export configuration.',
)]
final class ConfigExportCommand extends Command {

  use StringTranslationTrait;
  use StorageCopyTrait;
  use ConfigCommandTrait;

  public function __construct(
    #[Autowire(service: 'config.storage.export')]
    private readonly StorageInterface $export,
    private readonly SyncFactory $syncFactory,
  ) {
    parent::__construct();
  }

  /**
   * Export the configuration.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The symfony style.
   * @param bool $yes
   *   The command option to skip the confirmation question.
   *
   * @return int
   *   The command result.
   */
  public function __invoke(
    SymfonyStyle $io,
    #[Option(description: 'Answer the confirmation question with yes.', shortcut: 'y')]
    bool $yes = FALSE,
  ): int {
    // Resolve the sync storage here rather than in the constructor: the
    // factory throws when no sync directory is configured, and a throwing
    // constructor would break `dr list` for every command.
    try {
      $sync = $this->syncFactory->get();
    }
    catch (ConfigDirectoryNotDefinedException $e) {
      $io->error($e->getMessage());
      return self::FAILURE;
    }

    $comparer = new StorageComparer($this->export, $sync);
    $comparer->createChangelist();

    if (!$comparer->hasChanges()) {
      $io->success((string) $this->t('The active configuration to export matches the configuration in the sync directory.'));
      return self::SUCCESS;
    }

    $this->renderTable($comparer, $io);

    if (!$yes && !$io->confirm((string) $this->t('Do you want to export the configuration?'))) {
      throw new UserAbortException('Cancelled');
    }

    self::replaceStorageContents($this->export, $sync);

    $io->success((string) $this->t('Configuration successfully exported'));

    return self::SUCCESS;
  }

}
