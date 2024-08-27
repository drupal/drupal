<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Command\BootableCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Shows information about a particular recipe.
 *
 * @internal
 *   This API is experimental.
 */
final class RecipeInfoCommand extends Command {

  use BootableCommandTrait;

  public function __construct($class_loader) {
    parent::__construct('recipe:info');
    $this->classLoader = $class_loader;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setDescription('Shows information about a recipe.')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to the recipe\'s folder');
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
    $this->boot();

    $recipe = Recipe::createFromDirectory($recipe_path);
    $io->section('Description');
    $io->text($recipe->description);

    $io->section('Inputs');
    $descriptions = $recipe->input->describeAll();
    if ($descriptions) {
      // Passing NULL as the callable to array_map() makes it act like a Python
      // zip() operation.
      // @see https://docs.python.org/3.8/library/functions.html#zip
      $rows = array_map(NULL, array_keys($descriptions), $descriptions);
      $io->table(['Name', 'Description'], $rows);
    }
    else {
      $io->writeln("This recipe does not accept any input.");
    }
    return 0;
  }

}
