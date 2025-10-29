<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Core\Command\BootableCommandTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Exports content entities in YAML format.
 *
 * @internal
 *    This API is experimental.
 */
final class ContentExportCommand extends Command {

  use BootableCommandTrait;
  use StringTranslationTrait;

  public function __construct(object $class_loader) {
    parent::__construct('content:export');
    $this->classLoader = $class_loader;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setDescription('Exports content entities in YAML format.')
      ->addArgument('entity_type_id', InputArgument::REQUIRED, 'The entity type to export (e.g., node, taxonomy_term).')
      ->addArgument('entity_id', InputArgument::OPTIONAL, 'The ID of the entity to export. Will usually be a number.')
      ->addOption('with-dependencies', 'W', InputOption::VALUE_NONE, "Recursively export all of the entities referenced by this entity into a directory structure.")
      ->addOption('bundle', 'b', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only export entities of the specified bundle(s).')
      ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'The path where content should be exported.')
      ->addUsage('node 42')
      ->addUsage('node 3 --with-dependencies --dir=/path/to/content')
      ->addUsage('media --bundle=image --dir=images')
      ->addUsage('taxonomy_term --bundle=tags --bundle=categories --dir=terms');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $container = $this->boot()->getContainer();

    $entity_type_id = $input->getArgument('entity_type_id');
    $entity_id = $input->getArgument('entity_id');
    $bundles = $input->getOption('bundle');
    $entity_type_manager = $container->get(EntityTypeManagerInterface::class);

    if (!$entity_type_manager->hasDefinition($entity_type_id)) {
      $io->error("The entity type \"$entity_type_id\" does not exist.");
      return 1;
    }
    if (!$entity_type_manager->getDefinition($entity_type_id)->entityClassImplements(ContentEntityInterface::class)) {
      $io->error("$entity_type_id is not a content entity type.");
      return 1;
    }

    // Confirm that all specified bundles exist.
    if ($bundles) {
      $unknown_bundles = array_diff(
        $bundles,
        array_keys($container->get(EntityTypeBundleInfoInterface::class)->getBundleInfo($entity_type_id)),
      );
      if ($unknown_bundles) {
        $io->error("These bundles do not exist on the $entity_type_id entity type: " . implode(', ', $unknown_bundles));
        return 1;
      }
    }

    $dir = $input->getOption('dir');
    $with_dependencies = $input->getOption('with-dependencies');
    $exporter = $container->get(Exporter::class);

    // If we're going to export multiple entities, or a single entity with its
    // dependencies, require the `--dir` option.
    if (empty($dir) && (empty($entity_id) || $with_dependencies)) {
      throw new RuntimeException('The --dir option is required to export multiple entities, or a single entity with its dependencies.');
    }

    $count = 0;
    $storage = $entity_type_manager->getStorage($entity_type_id);
    foreach ($this->loadEntities($storage, $entity_id, $bundles) as $entity) {
      if ($with_dependencies) {
        $count += $exporter->exportWithDependencies($entity, $dir);
      }
      elseif ($dir) {
        $exporter->exportToFile($entity, $dir);
        $count++;
      }
      else {
        $io->write((string) $exporter->export($entity));
        return 0;
      }
    }

    // If we were trying to export a specific entity and it didn't get exported,
    // that's an error.
    if ($entity_id && $count === 0) {
      $io->error("$entity_type_id $entity_id does not exist.");
      if ($bundles) {
        $io->caution('Maybe this entity is not one of the specified bundles: ' . implode(', ', $bundles));
      }
      return 1;
    }

    $file_system = $container->get(FileSystemInterface::class);
    $message = (string) $this->formatPlural(
      $count,
      'One entity was exported to @dir.',
      '@count entities were exported to @dir.',
      ['@dir' => $file_system->realpath($dir)],
    );
    $io->success($message);
    return 0;
  }

  /**
   * Find entities to export and yield them one by one.
   *
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $storage
   *   The entity storage handler.
   * @param string|int|null $entity_id
   *   The ID of the specific entity to load, or NULL to load all entities
   *   (probably filtered by bundle).
   * @param string[] $bundles
   *   (optional) The bundles to filter by.
   *
   * @return iterable<\Drupal\Core\Entity\ContentEntityInterface>
   *   A generator that yields content entities.
   */
  private function loadEntities(ContentEntityStorageInterface $storage, string|int|null $entity_id, array $bundles = []): iterable {
    $values = [];

    $entity_type = $storage->getEntityType();
    if ($bundles) {
      $values[$entity_type->getKey('bundle')] = $bundles;
    }
    if ($entity_id) {
      $values[$entity_type->getKey('id')] = $entity_id;
    }
    return $storage->loadByProperties($values);
  }

}
