<?php

namespace Drupal\pgsql;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cSpell:ignore relkind objid regclass

/**
 * An update class for sequence ownership.
 * @see https://www.drupal.org/i/3028706
 *
 * @internal
 */
class Update10101 implements ContainerInjectionInterface {

  /**
   * Sequence owner update constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository
   *   The last installed schema repository service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository,
    protected Connection $connection,
    protected ModuleExtensionList $moduleExtensionList,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.last_installed_schema.repository'),
      $container->get('database'),
      $container->get('extension.list.module'),
      $container->get('module_handler')
    );
  }

  /**
   * Update *all* existing sequences to include the owner tables.
   *
   * @param array $sandbox
   *   Stores information for batch updates.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup|null
   *   Returns the amount of orphaned sequences fixed.
   */
  public function update(array &$sandbox): ?PluralTranslatableMarkup {
    if ($this->connection->databaseType() !== 'pgsql') {
      // This database update is a no-op for all other core database drivers.
      $sandbox['#finished'] = 1;
      return NULL;
    }

    if (!isset($sandbox['progress'])) {
      $sandbox['fixed'] = 0;
      $sandbox['progress'] = 0;
      $sandbox['tables'] = [];

      // Discovers all tables defined with hook_schema().
      // @todo We need to add logic to do the same for on-demand tables. See
      //   https://www.drupal.org/i/3358777
      $modules = $this->moduleExtensionList->getList();
      foreach ($modules as $extension) {
        $module = $extension->getName();
        $this->moduleHandler->loadInclude($module, 'install');
        $schema = $this->moduleHandler->invoke($module, 'schema');
        if (!empty($schema)) {
          foreach ($schema as $table_name => $table_info) {
            foreach ($table_info['fields'] as $column_name => $column_info) {
              if (str_starts_with($column_info['type'], 'serial')) {
                $sandbox['tables'][] = [
                  'table' => $table_name,
                  'column' => $column_name,
                ];
              }
            }
          }
        }
      }

      // Discovers all content entity types with integer entity keys that are
      // most likely serial columns.
      $entity_types = $this->entityTypeManager->getDefinitions();
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      foreach ($entity_types as $entity_type) {
        $storage_class = $entity_type->getStorageClass();
        if (is_subclass_of($storage_class, SqlContentEntityStorage::class)) {
          $id_key = $entity_type->getKey('id');
          $revision_key = $entity_type->getKey('revision');

          $original_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type->id());
          if ($original_storage_definitions[$id_key]->getType() === 'integer') {
            $sandbox['tables'][] = [
              'table' => $entity_type->getBaseTable(),
              'column' => $id_key,
            ];
          }

          if ($entity_type->isRevisionable() &&
            $original_storage_definitions[$revision_key]->getType() === 'integer') {
            $sandbox['tables'][] = [
              'table' => $entity_type->getRevisionTable(),
              'column' => $revision_key,
            ];
          }
        }
      }
      $sandbox['max'] = count($sandbox['tables']);
    }
    else {
      // Adds ownership of orphan sequences to tables.
      $to_process = array_slice($sandbox['tables'], $sandbox['progress'], 50);

      // Ensures that a sequence is not owned first, then ensures that the a
      // sequence exists at all before trying to alter it.
      foreach ($to_process as $table_info) {
        if ($this->connection->schema()->tableExists($table_info['table'])) {
          $owned = (bool) $this->getSequenceName($table_info['table'], $table_info['column']);

          if (!$owned) {
            $sequence_name = $this->connection
              ->makeSequenceName($table_info['table'], $table_info['column']);
            $exists = $this->sequenceExists($sequence_name);
            if ($exists) {
              $transaction = $this->connection->startTransaction($sequence_name);
              try {
                $this->updateSequenceOwnership($sequence_name, $table_info['table'], $table_info['column']);

                $sandbox['fixed']++;
              }
              catch (DatabaseExceptionWrapper $e) {
                $transaction->rollBack();
              }
            }
          }
        }
        $sandbox['progress']++;
      }
    }

    if ($sandbox['max'] && $sandbox['progress'] < $sandbox['max']) {
      $sandbox['#finished'] = $sandbox['progress'] / $sandbox['max'];
      return NULL;
    }
    else {
      $sandbox['#finished'] = 1;
      return new PluralTranslatableMarkup(
        $sandbox['fixed'],
        '1 orphaned sequence fixed.',
        '@count orphaned sequences fixed'
      );
    }
  }

  /**
   * Alters the ownership of a sequence.
   *
   * This is used for updating orphaned sequences.
   *
   * @param string $sequence_name
   *   The appropriate sequence name for a given table and serial field.
   * @param string $table
   *   The unquoted or prefixed table name.
   * @param string $column
   *   The column name for the sequence.
   *
   * @see https://www.drupal.org/i/3028706
   */
  private function updateSequenceOwnership(string $sequence_name, string $table, string $column): void {
    $this->connection->query('ALTER SEQUENCE IF EXISTS ' . $sequence_name . ' OWNED BY {' . $table . '}.[' . $column . ']');
  }

  /**
   * Retrieves a sequence name that is owned by the table and column.
   *
   * @param string $table
   *   A table name that is not prefixed or quoted.
   * @param string $column
   *   The column name.
   *
   * @return string|null
   *   The name of the sequence or NULL if it does not exist.
   */
  public function getSequenceName(string $table, string $column): ?string {
    return $this->connection
      ->query("SELECT pg_get_serial_sequence(:table, :column)", [
        ':table' => $this->connection->getPrefix() . $table,
        ':column' => $column,
      ])
      ->fetchField();
  }

  /**
   * Checks if a sequence exists.
   *
   * @param string $name
   *   The fully-qualified sequence name.
   *
   * @return bool
   *   TRUE if the sequence exists by the name.
   *
   * @see \Drupal\pgsql\Driver\Database\pgsql\Connection::makeSequenceName()
   */
  private function sequenceExists(string $name): bool {
    return (bool) \Drupal::database()
      ->query("SELECT c.relname FROM pg_class as c WHERE c.relkind = 'S' AND c.relname = :name", [':name' => $name])
      ->fetchField();
  }

}
