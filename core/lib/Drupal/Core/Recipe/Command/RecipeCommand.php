<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe\Command;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\Checkpoint\Checkpoint;
use Drupal\Core\Config\Checkpoint\CheckpointStorageInterface;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigImporterFactory;
use Drupal\Core\Config\StorageCacheInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Recipe\ConsoleInputCollector;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Applies recipe.
 *
 * @internal
 *   This API is experimental.
 */
#[AsCommand(
  name: 'recipe:apply',
  aliases: [
    'recipe',
  ],
  description: 'Applies a recipe to a site.',
)]
final class RecipeCommand extends Command {

  use StringTranslationTrait;

  public function __construct(
    protected CheckpointStorageInterface $checkpoint_storage,
    #[Autowire(service: 'logger.channel.default')]
    protected LoggerInterface $logger,
    protected StorageCacheInterface $configStorage,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->addArgument('path', InputArgument::REQUIRED, 'The path to the recipe\'s folder to apply');

    ConsoleInputCollector::configureCommand($this);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $recipe_path = $input->getArgument('path');
    if (!is_string($recipe_path) || !is_dir($recipe_path)) {
      $io->error(sprintf('The supplied path %s is not a directory', $recipe_path));
      return Command::FAILURE;
    }

    $recipe = Recipe::createFromDirectory($recipe_path);

    // Collect input for this recipe and all the recipes it directly and
    // indirectly applies.
    $recipe->input->collectAll(new ConsoleInputCollector($input, $io));

    if ($this->checkpoint_storage instanceof LoggerAwareInterface) {
      $this->checkpoint_storage->setLogger($this->logger);
    }
    $backup_checkpoint = $this->checkpoint_storage
      ->checkpoint("Backup before the '$recipe->name' recipe.");
    try {
      $steps = RecipeRunner::toBatchOperations($recipe);
      $progress_bar = $io->createProgressBar();
      $progress_bar->setFormat("%current%/%max% [%bar%]\n%message%\n");
      $progress_bar->setMessage($this->toPlainString($this->t('Applying recipe')));
      $progress_bar->start(count($steps));

      /** @var array{message?: \Stringable|string, results: array{module?: string[], theme?: string[], content?: string[], recipe?: string[]}} $context */
      $context = ['results' => []];
      foreach ($steps as $step) {
        call_user_func_array($step[0], array_merge($step[1], [&$context]));
        if (isset($context['message'])) {
          $progress_bar->setMessage($this->toPlainString($context['message']));
        }
        unset($context['message']);
        $progress_bar->advance();
      }
      if ($io->isVerbose()) {
        if (!empty($context['results']['module'])) {
          $io->section($this->toPlainString($this->t('Modules installed')));
          $modules = array_map(fn ($module) => \Drupal::service('extension.list.module')->getName($module), $context['results']['module']);
          \sort($modules, SORT_NATURAL);
          $io->listing($modules);
        }
        if (!empty($context['results']['theme'])) {
          $io->section($this->toPlainString($this->t('Themes installed')));
          $themes = array_map(fn ($theme) => \Drupal::service('extension.list.theme')->getName($theme), $context['results']['theme']);
          sort($themes, SORT_NATURAL);
          $io->listing($themes);
        }
        if (!empty($context['results']['content'])) {
          $io->section($this->toPlainString($this->t('Content created for recipes')));
          $io->listing($context['results']['content']);
        }
        if (!empty($context['results']['recipe'])) {
          $io->section($this->toPlainString($this->t('Recipes applied')));
          $io->listing($context['results']['recipe']);
        }
      }
      $io->success($this->toPlainString($this->t('%recipe applied successfully', ['%recipe' => $recipe->name])));
      return Command::SUCCESS;
    }
    catch (\Throwable $e) {
      try {
        $this->rollBackToCheckpoint($backup_checkpoint);
      }
      catch (ConfigImporterException $importer_exception) {
        $io->error($importer_exception->getMessage());
      }
      throw $e;
    }
  }

  /**
   * Converts a stringable like TranslatableMarkup to a plain text string.
   *
   * @param \Stringable|string $text
   *   The string to convert.
   *
   * @return string
   *   The plain text string.
   */
  private function toPlainString(\Stringable|string $text): string {
    return PlainTextOutput::renderFromHtml((string) $text);
  }

  /**
   * Rolls config back to a particular checkpoint.
   *
   * @param \Drupal\Core\Config\Checkpoint\Checkpoint $checkpoint
   *   The checkpoint to roll back to.
   */
  private function rollBackToCheckpoint(Checkpoint $checkpoint): void {
    $container = \Drupal::getContainer();
    $this->checkpoint_storage->setCheckpointToReadFrom($checkpoint);

    assert($this->configStorage instanceof StorageInterface);
    $storage_comparer = new StorageComparer($this->checkpoint_storage, $this->configStorage);
    $storage_comparer->reset();

    $container->get(ConfigImporterFactory::class)->get($storage_comparer)->import();
  }

}
