<?php

namespace Drupal\Core\Config\Command;

use Drupal\Core\Config\StorageComparer;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait for methods used across config commands.
 */
trait ConfigCommandTrait {

  /**
   * Prints the change list of a storage comparer as a table.
   *
   * @param \Drupal\Core\Config\StorageComparer $comparer
   *   The comparer holding the change list to render.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The input and output style.
   */
  protected function renderTable(StorageComparer $comparer, SymfonyStyle $io): void {
    $translations = [
      'delete' => (string) $this->t('Delete'),
      'update' => (string) $this->t('Update'),
      'create' => (string) $this->t('Create'),
      'rename' => (string) $this->t('Rename'),
    ];
    $styles = [
      'delete' => 'fg=white;bg=red',
      'update' => 'fg=black;bg=yellow',
      'create' => 'fg=white;bg=green',
      'rename' => 'fg=black;bg=cyan',
    ];
    $header = [
      (string) $this->t('Collection'),
      (string) $this->t('Config'),
      (string) $this->t('Operation'),
    ];

    $rows = [];
    foreach ($comparer->getAllCollectionNames() as $collection) {
      foreach ($comparer->getChangelist(NULL, $collection) as $op => $names) {
        foreach ($names as $name) {
          $rows[] = [
            $collection,
            $op !== 'rename' ? $name : str_replace('::', ' -> ', $name),
            \sprintf('<%s>%s</>', $styles[$op], $translations[$op]),
          ];
        }
      }
    }

    $io->table($header, $rows);
  }

}
