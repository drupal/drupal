<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Command\BootableCommandTrait;
use Drupal\Core\Config\Checkpoint\Checkpoint;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\StorageComparer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Applies recipe.
 *
 * @internal
 *   This API is experimental.
 */
final class RecipeCommand extends Command {

  use BootableCommandTrait;

  /**
   * Constructs a new RecipeCommand command.
   *
   * @param object $class_loader
   *   The class loader.
   */
  public function __construct($class_loader) {
    parent::__construct('recipe');
    $this->classLoader = $class_loader;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setDescription('Applies a recipe to a site.')
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
      return 1;
    }
    // Recipes can only be applied to an already-installed site.
    $container = $this->boot()->getContainer();

    /** @var \Drupal\Core\Config\Checkpoint\CheckpointStorageInterface $checkpoint_storage */
    $checkpoint_storage = $container->get('config.storage.checkpoint');
    $recipe = Recipe::createFromDirectory($recipe_path);

    // Collect input for this recipe and all the recipes it directly and
    // indirectly applies.
    $recipe->input->collectAll(new ConsoleInputCollector($input, $io));

    if ($checkpoint_storage instanceof LoggerAwareInterface) {
      $logger = new ConsoleLogger($output, [
        // The checkpoint storage logs a notice if it decides to not create a
        // checkpoint, and we want to be sure those notices are seen even
        // without additional verbosity.
        LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
      ]);
      $checkpoint_storage->setLogger($logger);
    }
    $backup_checkpoint = $checkpoint_storage
      ->checkpoint("Backup before the '$recipe->name' recipe.");
    try {
      $steps = RecipeRunner::toBatchOperations($recipe);
      $progress_bar = $io->createProgressBar();
      $progress_bar->setFormat("%current%/%max% [%bar%]\n%message%\n");
      $progress_bar->setMessage($this->toPlainString(t('Applying recipe')));
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
          $io->section($this->toPlainString(t('Modules installed')));
          $modules = array_map(fn ($module) => \Drupal::service('extension.list.module')->getName($module), $context['results']['module']);
          sort($modules, SORT_NATURAL);
          $io->listing($modules);
        }
        if (!empty($context['results']['theme'])) {
          $io->section($this->toPlainString(t('Themes installed')));
          $themes = array_map(fn ($theme) => \Drupal::service('extension.list.theme')->getName($theme), $context['results']['theme']);
          sort($themes, SORT_NATURAL);
          $io->listing($themes);
        }
        if (!empty($context['results']['content'])) {
          $io->section($this->toPlainString(t('Content created for recipes')));
          $io->listing($context['results']['content']);
        }
        if (!empty($context['results']['recipe'])) {
          $io->section($this->toPlainString(t('Recipes applied')));
          $io->listing($context['results']['recipe']);
        }
      }
      $io->success($this->toPlainString(t('%recipe applied successfully', ['%recipe' => $recipe->name])));
      return 0;
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

    /** @var \Drupal\Core\Config\Checkpoint\CheckpointStorageInterface $checkpoint_storage */
    $checkpoint_storage = $container->get('config.storage.checkpoint');
    $checkpoint_storage->setCheckpointToReadFrom($checkpoint);

    $storage_comparer = new StorageComparer($checkpoint_storage, $container->get('config.storage'));
    $storage_comparer->reset();

    $config_importer = new ConfigImporter(
      $storage_comparer,
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('lock'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler'),
      $container->get('string_translation'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
    );
    $config_importer->import();
  }

}
