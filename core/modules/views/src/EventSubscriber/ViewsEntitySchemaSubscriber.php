<?php

/**
 * @file
 * Contains \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber.
 */

namespace Drupal\views\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeEventSubscriberTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\views\Views;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to changes on entity types to update all views entities.
 */
class ViewsEntitySchemaSubscriber implements EntityTypeListenerInterface, EventSubscriberInterface {

  use EntityTypeEventSubscriberTrait;

  /**
   * Indicates that a base table got renamed.
   */
  const BASE_TABLE_RENAME = 0;

  /**
   * Indicates that a data table got renamed.
   */
  const DATA_TABLE_RENAME = 1;

  /**
   * Indicates that a data table got added.
   */
  const DATA_TABLE_ADDITION = 2;

  /**
   * Indicates that a data table got removed.
   */
  const DATA_TABLE_REMOVAL = 3;

  /**
   * Indicates that a revision table got renamed.
   */
  const REVISION_TABLE_RENAME = 4;

  /**
   * Indicates that a revision table got added.
   */
  const REVISION_TABLE_ADDITION = 5;

  /**
   * Indicates that a revision table got removed.
   */
  const REVISION_TABLE_REMOVAL = 6;

  /**
   * Indicates that a revision data table got renamed.
   */
  const REVISION_DATA_TABLE_RENAME = 7;

  /**
   * Indicates that a revision data table got added.
   */
  const REVISION_DATA_TABLE_ADDITION = 8;

  /**
   * Indicates that a revision data table got removed.
   */
  const REVISION_DATA_TABLE_REMOVAL = 9;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ViewsEntitySchemaSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return static::getEntityTypeEvents();
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $changes = [];

    // We implement a specific logic for table updates, which is bound to the
    // default sql content entity storage.
    if (!$this->entityManager->getStorage($entity_type->id()) instanceof SqlContentEntityStorage) {
      return;
    }

    if ($entity_type->getBaseTable() != $original->getBaseTable()) {
      $changes[] = static::BASE_TABLE_RENAME;
    }

    $revision_add = $entity_type->isRevisionable() && !$original->isRevisionable();
    $revision_remove = !$entity_type->isRevisionable() && $original->isRevisionable();
    $translation_add = $entity_type->isTranslatable() && !$original->isTranslatable();
    $translation_remove = !$entity_type->isTranslatable() && $original->isTranslatable();

    if ($revision_add) {
      $changes[] = static::REVISION_TABLE_ADDITION;
    }
    elseif ($revision_remove) {
      $changes[] = static::REVISION_TABLE_REMOVAL;
    }
    elseif ($entity_type->isRevisionable() && $entity_type->getRevisionTable() != $original->getRevisionTable()) {
      $changes[] = static::REVISION_TABLE_RENAME;
    }

    if ($translation_add) {
      $changes[] = static::DATA_TABLE_ADDITION;
    }
    elseif ($translation_remove) {
      $changes[] = static::DATA_TABLE_REMOVAL;
    }
    elseif ($entity_type->isTranslatable() && $entity_type->getDataTable() != $original->getDataTable()) {
      $changes[] = static::DATA_TABLE_RENAME;
    }

    if ($entity_type->isRevisionable() && $entity_type->isTranslatable()) {
      if ($revision_add || $translation_add) {
        $changes[] = static::REVISION_DATA_TABLE_ADDITION;
      }
      elseif ($entity_type->getRevisionDataTable() != $original->getRevisionDataTable()) {
        $changes[] = static::REVISION_DATA_TABLE_RENAME;
      }
    }
    elseif ($original->isRevisionable() && $original->isTranslatable() && ($revision_remove || $translation_remove)) {
      $changes[] = static::REVISION_DATA_TABLE_REMOVAL;
    }

    /** @var \Drupal\views\Entity\View[] $all_views */
    $all_views = $this->entityManager->getStorage('view')->loadMultiple(NULL);

    foreach ($changes as $change) {
      switch ($change) {
        case static::BASE_TABLE_RENAME:
          $this->baseTableRename($all_views, $entity_type->id(), $original->getBaseTable(), $entity_type->getBaseTable());
          break;
        case static::DATA_TABLE_RENAME:
          $this->dataTableRename($all_views, $entity_type->id(), $original->getDataTable(), $entity_type->getDataTable());
          break;
        case static::DATA_TABLE_ADDITION:
          $this->dataTableAddition($all_views, $entity_type, $entity_type->getDataTable(), $entity_type->getBaseTable());
          break;
        case static::DATA_TABLE_REMOVAL:
          $this->dataTableRemoval($all_views, $entity_type->id(), $original->getDataTable(), $entity_type->getBaseTable());
          break;
        case static::REVISION_TABLE_RENAME:
          $this->baseTableRename($all_views, $entity_type->id(), $original->getRevisionTable(), $entity_type->getRevisionTable());
          break;
        case static::REVISION_TABLE_ADDITION:
          // If we add revision support we don't have to do anything.
          break;
        case static::REVISION_TABLE_REMOVAL:
          $this->revisionRemoval($all_views, $original);
          break;
        case static::REVISION_DATA_TABLE_RENAME:
          $this->dataTableRename($all_views, $entity_type->id(), $original->getRevisionDataTable(), $entity_type->getRevisionDataTable());
          break;
        case static::REVISION_DATA_TABLE_ADDITION:
          $this->dataTableAddition($all_views, $entity_type, $entity_type->getRevisionDataTable(), $entity_type->getRevisionTable());
          break;
        case static::REVISION_DATA_TABLE_REMOVAL:
          $this->dataTableRemoval($all_views, $entity_type->id(), $original->getRevisionDataTable(), $entity_type->getRevisionTable());
          break;
      }
    }

    foreach ($all_views as $view) {
      $view->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $tables = [
      $entity_type->getBaseTable(),
      $entity_type->getDataTable(),
      $entity_type->getRevisionTable(),
      $entity_type->getRevisionDataTable(),
    ];

    $all_views = $this->entityManager->getStorage('view')->loadMultiple(NULL);
    /** @var \Drupal\views\Entity\View $view */
    foreach ($all_views as $id => $view) {

      // First check just the base table.
      if (in_array($view->get('base_table'), $tables)) {
        $view->disable();
        $view->save();
      }
    }
  }

  /**
   * Applies a callable onto all handlers of all passed in views.
   *
   * @param \Drupal\views\Entity\View[] $all_views
   *   All views entities.
   * @param callable $process
   *   A callable which retrieves a handler config array.
   */
  protected function processHandlers(array $all_views, callable $process) {
    foreach ($all_views as $view) {
      foreach (array_keys($view->get('display')) as $display_id) {
        $display = &$view->getDisplay($display_id);
        foreach (Views::getHandlerTypes() as $handler_type) {
          $handler_type = $handler_type['plural'];
          if (!isset($display['display_options'][$handler_type])) {
            continue;
          }
          foreach ($display['display_options'][$handler_type] as $id => &$handler_config) {
            $process($handler_config);
            if ($handler_config === NULL) {
              unset($display['display_options'][$handler_type][$id]);
            }
          }
        }
      }
    }
  }

  /**
   * Updates views if a base table is renamed.
   *
   * @param \Drupal\views\Entity\View[] $all_views
   *   All views.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $old_base_table
   *   The old base table name.
   * @param string $new_base_table
   *   The new base table name.
   */
  protected function baseTableRename($all_views, $entity_type_id, $old_base_table, $new_base_table) {
    foreach ($all_views as $view) {
      if ($view->get('base_table') == $old_base_table) {
        $view->set('base_table', $new_base_table);
      }
    }

    $this->processHandlers($all_views, function (array &$handler_config) use ($entity_type_id, $old_base_table, $new_base_table) {
      if (isset($handler_config['entity_type']) && $handler_config['entity_type'] == $entity_type_id && $handler_config['table'] == $old_base_table) {
        $handler_config['table'] = $new_base_table;
      }
    });
  }

  /**
   *
   * Updates views if a data table is renamed.
   *
   * @param \Drupal\views\Entity\View[] $all_views
   *   All views.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $old_data_table
   *   The old data table name.
   * @param string $new_data_table
   *   The new data table name.
   */
  protected function dataTableRename($all_views, $entity_type_id, $old_data_table, $new_data_table) {
    foreach ($all_views as $view) {
      if ($view->get('base_table') == $old_data_table) {
        $view->set('base_table', $new_data_table);
      }
    }

    $this->processHandlers($all_views, function (array &$handler_config) use ($entity_type_id, $old_data_table, $new_data_table) {
      if (isset($handler_config['entity_type']) && $handler_config['entity_type'] == $entity_type_id && $handler_config['table'] == $old_data_table) {
        $handler_config['table'] = $new_data_table;
      }
    });
  }

  /**
   * Updates views if a data table is added.
   *
   * @param \Drupal\views\Entity\View[] $all_views
   *   All views.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $new_data_table
   *   The new data table.
   * @param string $base_table
   *   The base table.
   */
  protected function dataTableAddition($all_views, EntityTypeInterface $entity_type, $new_data_table, $base_table) {
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
    $entity_type_id = $entity_type->id();
    $storage = $this->entityManager->getStorage($entity_type_id);
    $storage->setEntityType($entity_type);
    $table_mapping = $storage->getTableMapping();
    $data_table_fields = $table_mapping->getFieldNames($new_data_table);
    $base_table_fields = $table_mapping->getFieldNames($base_table);

    $data_table = $new_data_table;

    $this->processHandlers($all_views, function (array &$handler_config) use ($entity_type_id, $base_table, $data_table, $base_table_fields, $data_table_fields) {
      if (isset($handler_config['entity_type']) && isset($handler_config['entity_field']) && $handler_config['entity_type'] == $entity_type_id) {
        // Move all fields which just exists on the data table.
        if ($handler_config['table'] == $base_table && in_array($handler_config['entity_field'], $data_table_fields) && !in_array($handler_config['entity_field'], $base_table_fields)) {
          $handler_config['table'] = $data_table;
        }
      }
    });
  }

  /**
   * Updates views if a data table is removed.
   *
   * @param \Drupal\views\Entity\View[] $all_views
   *   All views.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $old_data_table
   *   The name of the previous existing data table.
   * @param string $base_table
   *   The name of the base table.
   */
  protected function dataTableRemoval($all_views, $entity_type_id, $old_data_table, $base_table) {
    // We move back the data table back to the base table.
    $this->processHandlers($all_views, function (array &$handler_config) use ($entity_type_id, $old_data_table, $base_table) {
      if (isset($handler_config['entity_type']) && $handler_config['entity_type'] == $entity_type_id) {
        if ($handler_config['table'] == $old_data_table) {
          $handler_config['table'] = $base_table;
        }
      }
    });
  }

  /**
   * Updates views if revision support is removed
   *
   * @param \Drupal\views\Entity\View[] $all_views
   *   All views.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The origin entity type.
   */
  protected function revisionRemoval($all_views, EntityTypeInterface $original) {
    $revision_base_table = $original->getRevisionTable();
    $revision_data_table = $original->getRevisionDataTable();

    foreach ($all_views as $view) {
      if (in_array($view->get('base_table'), [$revision_base_table, $revision_data_table])) {
        // Let's disable the views as we no longer support revisions.
        $view->setStatus(FALSE);
      }

      // For any kind of field, let's rely on the broken handler functionality.
    }
  }

}
