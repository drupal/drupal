<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\SortArray;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder as SymfonyFinder;

/**
 * Finds all default content in a directory, in dependency order.
 *
 * @internal
 *   This API is experimental.
 */
final class Finder {

  /**
   * The content entity data to import, in dependency order, keyed by entity UUID.
   *
   * @var array<string, array<mixed>>
   */
  public readonly array $data;

  public function __construct(string $path) {
    try {
      // Scan for all YAML files in the content directory.
      $finder = SymfonyFinder::create()
        ->in($path)
        ->files()
        ->name('*.yml');
    }
    catch (DirectoryNotFoundException) {
      $this->data = [];
      return;
    }

    $graph = $files = [];
    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($finder as $file) {
      /** @var array{_meta: array{uuid: string|null, depends: array<string, string>|null}} $decoded */
      $decoded = Yaml::decode($file->getContents());
      $decoded['_meta']['path'] = $file->getPathname();
      $uuid = $decoded['_meta']['uuid'] ?? throw new ImportException($decoded['_meta']['path'] . ' does not have a UUID.');
      $files[$uuid] = $decoded;

      // For the graph to work correctly, every entity must be mentioned in it.
      // This is inspired by
      // \Drupal\Core\Config\Entity\ConfigDependencyManager::getGraph().
      $graph += [
        $uuid => [
          'edges' => [],
          'uuid' => $uuid,
        ],
      ];

      foreach ($decoded['_meta']['depends'] ?? [] as $dependency_uuid => $entity_type) {
        $graph[$dependency_uuid]['edges'][$uuid] = TRUE;
        $graph[$dependency_uuid]['uuid'] = $dependency_uuid;
      }
    }
    ksort($graph);

    // Sort the dependency graph. The entities that are dependencies of other
    // entities should come first.
    $graph_object = new Graph($graph);
    $sorted = $graph_object->searchAndSort();
    uasort($sorted, SortArray::sortByWeightElement(...));

    $entities = [];
    foreach ($sorted as ['uuid' => $uuid]) {
      if (array_key_exists($uuid, $files)) {
        $entities[$uuid] = $files[$uuid];
      }
    }
    $this->data = $entities;
  }

}
