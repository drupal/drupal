<?php

namespace Drupal\Core\Config\Command;

use Drupal\Core\Command\Exception\UserAbortException;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigImporterFactory;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Import configuration.
 *
 * @internal
 */
#[AsCommand(
  name: 'config:import',
  description: 'Import configuration',
  aliases: ['cim'],
)]
class ConfigImportCommand extends Command {

  use StringTranslationTrait;
  use ConfigCommandTrait;

  public function __construct(
    #[Autowire(service: 'config.storage')]
    private readonly StorageInterface $active,
    #[Autowire(service: 'config.storage.sync')]
    private readonly StorageInterface $sync,
    private readonly ImportStorageTransformer $transformer,
    private readonly ConfigImporterFactory $importerFactory,
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

    // Do the import storage transformation.
    $import = $this->transformer->transform($this->sync);

    $comparer = new StorageComparer($import, $this->active);
    $comparer->createChangelist();

    if (!$comparer->hasChanges()) {
      $io->success((string) $this->t('There are no changes to import.'));
      return self::SUCCESS;
    }

    $this->renderTable($comparer, $io);

    if (!$yes && !$io->confirm((string) $this->t('Import the listed configuration changes?'))) {
      throw new UserAbortException('Cancelled');
    }

    $importer = $this->importerFactory->get($comparer);

    if ($importer->alreadyImporting()) {
      $io->warning((string) $this->t('Another request may be synchronizing configuration already.'));
      return Command::FAILURE;
    }

    try {
      $importer->validate();
      $importer->import();
    }
    catch (ConfigImporterException $e) {
      $io->error($e->getMessage());
      return Command::FAILURE;
    }

    $errors = $importer->getErrors();
    if (empty($errors)) {
      $io->success((string) $this->t('The configuration was imported successfully.'));
      return Command::SUCCESS;
    }

    $io->error((string) $this->t('The configuration was imported with errors.'));
    foreach ($errors as $error) {
      $io->error((string) $error);
    }

    return Command::FAILURE;
  }

}
