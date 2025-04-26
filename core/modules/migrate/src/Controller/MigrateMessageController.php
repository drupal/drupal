<?php

namespace Drupal\migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseConnectionRefusedException;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// cspell:ignore sourceid

/**
 * Provides controller methods for the Message form.
 */
class MigrateMessageController extends ControllerBase {

  /**
   * Constructs a MigrateController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationPluginManager
   *   The migration plugin manager.
   */
  public function __construct(
    protected Connection $database,
    FormBuilderInterface $formBuilder,
    protected MigrationPluginManagerInterface $migrationPluginManager,
  ) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * Displays an overview of migrate messages.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function overview(): array {
    // Check if there are migrate_message tables.
    $tables = $this->database->schema()->findTables('migrate_message_%');
    if (empty($tables)) {
      $build['no_tables'] = [
        '#type' => 'item',
        '#markup' => $this->t('There are no migration message tables.'),
      ];
      return $build;
    }

    // There are migrate_message tables so build the overview form.
    $migrations = $this->migrationPluginManager->createInstances([]);

    $header = [
      $this->t('Migration'),
      $this->t('Machine Name'),
      $this->t('Messages'),
    ];

    // Display the number of messages for each migration.
    $rows = [];
    foreach ($migrations as $id => $migration) {
      $message_count = $migration->getIdMap()->messageCount();
      // The message count is zero when there are no messages or when the
      // message table does not exist.
      if ($message_count == 0) {
        continue;
      }
      $row = [];
      $row['label'] = $migration->label();
      $row['machine_name'] = $id;
      $route_parameters = [
        'migration_id' => $migration->id(),
      ];
      $row['messages'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $message_count,
          '#url' => Url::fromRoute('migrate.messages.detail', $route_parameters),
        ],
      ];
      $rows[] = $row;
    }

    $build['migrations_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No migration messages available.'),
    ];
    $build['message_pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Displays a listing of migration messages for the given migration ID.
   *
   * @param string $migration_id
   *   A migration ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   A render array.
   */
  public function details(string $migration_id, Request $request): array {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance($migration_id);

    if (!$migration) {
      throw new NotFoundHttpException();
    }

    // Get the map and message table names.
    $map_table = $migration->getIdMap()->mapTableName();
    $message_table = $migration->getIdMap()->messageTableName();

    // If the map table does not exist then do not continue.
    if (!$this->database->schema()->tableExists($map_table)) {
      throw new NotFoundHttpException();
    }

    // If there is a map table but no message table display an error.
    if (!$this->database->schema()->tableExists($message_table)) {
      $this->messenger()->addError($this->t('The message table is missing for this migration.'));
      return [];
    }

    // Create the column header names.
    $header = [];
    $source_plugin = $migration->getSourcePlugin();
    // Create the column header names from the source plugin fields() method.
    // Fallback to the source_id name when the source ID is missing from
    // fields() method.
    try {
      $fields = $source_plugin->fields();
    }
    catch (DatabaseConnectionRefusedException | DatabaseNotFoundException | RequirementsException | \PDOException) {
    }

    $source_id_field_names = array_keys($source_plugin->getIds());
    $count = 1;
    foreach ($source_id_field_names as $source_id_field_name) {
      $display_name = preg_replace(
        [
          '/^[Tt]he /',
          '/\.$/',
        ], '', $fields[$source_id_field_name] ?? $source_id_field_name);
      $header[] = [
        'data' => ucfirst($display_name),
        'field' => 'sourceid' . $count++,
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ];
    }

    $header[] = [
      'data' => $this->t('Severity level'),
      'field' => 'level',
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header[] = [
      'data' => $this->t('Message'),
      'field' => 'message',
    ];

    $levels = [
      MigrationInterface::MESSAGE_ERROR => $this->t('Error'),
      MigrationInterface::MESSAGE_WARNING => $this->t('Warning'),
      MigrationInterface::MESSAGE_NOTICE => $this->t('Notice'),
      MigrationInterface::MESSAGE_INFORMATIONAL => $this->t('Info'),
    ];

    // Gets each message row and the source ID(s) for that message.
    $query = $this->database->select($message_table, 'msg')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    // Not all messages have a matching row in the map table.
    $query->leftJoin($map_table, 'map', 'msg.source_ids_hash = map.source_ids_hash');
    $query->fields('msg');
    $query->fields('map');
    $this->addFilterToQuery($request, $query);
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    // Build the rows to display.
    $rows = [];
    $add_explanation = FALSE;
    $num_ids = count($source_id_field_names);
    foreach ($result as $message_row) {
      $new_row = [];
      for ($count = 1; $count <= $num_ids; $count++) {
        $map_key = 'sourceid' . $count;
        $new_row[$map_key] = $message_row->$map_key ?? NULL;
        if (empty($new_row[$map_key])) {
          $new_row[$map_key] = $this->t('Not available');
          $add_explanation = TRUE;
        }
      }
      $new_row['level'] = $levels[$message_row->level];
      $new_row['message'] = $message_row->message;
      $rows[] = $new_row;
    }

    // Build the complete form.
    $build['message_filter_form'] = $this->formBuilder->getForm('Drupal\migrate\Form\MessageForm');
    $build['message_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No messages for this migration.'),
      '#attributes' => ['id' => 'admin-migrate-msg', 'class' => ['admin-migrate-msg']],
    ];
    $build['message_pager'] = ['#type' => 'pager'];

    if ($add_explanation) {
      $build['explanation'] = [
        '#type' => 'item',
        '#markup' => $this->t("When there is an error processing a row, the migration system saves the error message but not the source ID(s) of the row. That is why some messages in this table have 'Not available' in the source ID column(s)."),
      ];
    }
    return $build;
  }

  /**
   * Adds a filter to the query for migrate message administration.
   *
   * This method retrieves the session-based filters from the request and
   * applies them to the provided query object. If no filters are present, the
   * query is left unchanged.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The database query.
   */
  protected function addFilterToQuery(Request $request, SelectInterface $query): void {
    $session_filters = $request->getSession()->get('migration_messages_overview_filter', []);
    if (empty($session_filters)) {
      return;
    }

    // Build the condition.
    foreach ($session_filters as $filter) {
      if (empty($filter['value'])) {
        continue;
      }
      switch ($filter['type']) {
        case 'array':
          $values = array_values($filter['value']);
          if ($filter['field'] === 'msg.level') {
            $values = array_map(fn($x) => (int) $x, $values);
          }
          $query->condition($filter['field'], $values, 'IN');
          break;

        case 'string':
          $query->condition($filter['field'], "%{$filter['value']}%", 'LIKE');
          break;

        default:
          $query->condition($filter['field'], $filter['value']);
      }
    }
  }

  /**
   * Gets the title for the details page.
   *
   * @param string $migration_id
   *   A migration ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated title.
   */
  public function title(string $migration_id): TranslatableMarkup {
    return $this->t(
      'Messages of %migration',
      ['%migration' => $migration_id]
    );
  }

}
