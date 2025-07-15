<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Core\Command\BootableCommandTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Exports a single content entity in YAML format.
 *
 * @internal
 *    This API is experimental.
 */
final class ContentExportCommand extends Command {

  use BootableCommandTrait;

  public function __construct(object $class_loader) {
    parent::__construct('content:export');
    $this->classLoader = $class_loader;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setDescription('Exports a single content entity in YAML format.')
      ->addArgument('entity_type_id', InputArgument::REQUIRED, 'The type of entity to export (e.g., node, taxonomy_term).')
      ->addArgument('entity_id', InputArgument::REQUIRED, 'The ID of the entity to export. Will usually be a number.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $container = $this->boot()->getContainer();

    $entity_type_id = $input->getArgument('entity_type_id');
    $entity_id = $input->getArgument('entity_id');
    $entity_type_manager = $container->get(EntityTypeManagerInterface::class);

    if (!$entity_type_manager->hasDefinition($entity_type_id)) {
      $io->error("The entity type \"$entity_type_id\" does not exist.");
      return 1;
    }

    if (!$entity_type_manager->getDefinition($entity_type_id)->entityClassImplements(ContentEntityInterface::class)) {
      $io->error("$entity_type_id is not a content entity type.");
      return 1;
    }

    $entity = $entity_type_manager
      ->getStorage($entity_type_id)
      ->load($entity_id);
    if (!$entity instanceof ContentEntityInterface) {
      $io->error("$entity_type_id $entity_id does not exist.");
      return 1;
    }

    $data = $container->get(Exporter::class)->export($entity);
    $io->write(Yaml::encode($data));
    return 0;
  }

}
