<?php

namespace Drupal\migrate\Plugin\migrate\id_map;

use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Exception\SchemaTableKeyTooLargeException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Audit\HighestIdInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\Event\MigrateMapDeleteEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// cspell:ignore destid idmap sourceid

/**
 * Defines the sql based ID map implementation.
 *
 * It creates one map and one message table per migration entity to store the
 * relevant information.
 *
 * @PluginID("sql")
 */
class Sql extends PluginBase implements MigrateIdMapInterface, ContainerFactoryPluginInterface, HighestIdInterface {

  /**
   * Column name of hashed source id values.
   */
  const SOURCE_IDS_HASH = 'source_ids_hash';

  /**
   * An event dispatcher instance to use for map events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The migration map table name.
   *
   * @var string
   */
  protected $mapTableName;

  /**
   * The message table name.
   *
   * @var string
   */
  protected $messageTableName;

  /**
   * The migrate message service.
   *
   * @var \Drupal\migrate\MigrateMessageInterface
   */
  protected $message;

  /**
   * The database connection for the map/message tables on the destination.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The select query.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * The migration being done.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The source ID fields.
   *
   * @var array
   */
  protected $sourceIdFields;

  /**
   * The destination ID fields.
   *
   * @var array
   */
  protected $destinationIdFields;

  /**
   * Whether the plugin is already initialized.
   *
   * @var bool
   */
  protected $initialized;

  /**
   * The result.
   *
   * @var \Drupal\Core\Database\StatementInterface
   */
  protected $result;

  /**
   * The source identifiers.
   *
   * @var array
   */
  protected $sourceIds = [];

  /**
   * The destination identifiers.
   *
   * @var array
   */
  protected $destinationIds = [];

  /**
   * The current row.
   *
   * @var array|false|null
   */
  protected $currentRow = NULL;

  /**
   * The current key.
   *
   * @var array
   */
  protected $currentKey = [];

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs an SQL object.
   *
   * Sets up the tables and builds the maps,
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID for the migration process to do.
   * @param mixed $plugin_definition
   *   The configuration for the plugin.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to do.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EventDispatcherInterface $event_dispatcher, MigrationPluginManagerInterface $migration_plugin_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->eventDispatcher = $event_dispatcher;
    $this->message = new MigrateMessage();

    if (!isset($this->database)) {
      $this->database = \Drupal::database();
    }

    // Default generated table names, limited to 63 characters.
    $machine_name = str_replace(':', '__', $this->migration->id());
    $prefix_length = strlen($this->database->getPrefix());
    $this->mapTableName = 'migrate_map_' . mb_strtolower($machine_name);
    $this->mapTableName = mb_substr($this->mapTableName, 0, 63 - $prefix_length);
    $this->messageTableName = 'migrate_message_' . mb_strtolower($machine_name);
    $this->messageTableName = mb_substr($this->messageTableName, 0, 63 - $prefix_length);

    if (!$migration_plugin_manager) {
      @trigger_error('Calling Sql::__construct() without the $migration_manager argument is deprecated in drupal:9.5.0 and the $migration_manager argument will be required in drupal:11.0.0. See https://www.drupal.org/node/3277306', E_USER_DEPRECATED);
      $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
    }
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('event_dispatcher'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Retrieves the hash of the source identifier values.
   *
   * @internal
   *
   * @param array $source_id_values
   *   The source identifiers
   *
   * @return string
   *   A hash containing the hashed values of the source identifiers.
   */
  public function getSourceIdsHash(array $source_id_values) {
    // When looking up the destination ID we require an array with both the
    // source key and value, e.g. ['nid' => 41]. In this case, $source_id_values
    // need to be ordered the same order as $this->sourceIdFields().
    // However, the Migration process plugin doesn't currently have a way to get
    // the source key so we presume the values have been passed through in the
    // correct order.
    if (!isset($source_id_values[0])) {
      $source_id_values_keyed = [];
      foreach ($this->sourceIdFields() as $field_name => $source_id) {
        $source_id_values_keyed[] = $source_id_values[$field_name];
      }
      $source_id_values = $source_id_values_keyed;
    }
    return hash('sha256', serialize(array_map('strval', $source_id_values)));
  }

  /**
   * The source ID fields.
   *
   * @return array
   *   The source ID fields.
   */
  protected function sourceIdFields() {
    if (!isset($this->sourceIdFields)) {
      // Build the source and destination identifier maps.
      $this->sourceIdFields = [];
      $count = 1;
      foreach ($this->migration->getSourcePlugin()->getIds() as $field => $schema) {
        $this->sourceIdFields[$field] = 'sourceid' . $count++;
      }
    }
    return $this->sourceIdFields;
  }

  /**
   * The destination ID fields.
   *
   * @return array
   *   The destination ID fields.
   */
  protected function destinationIdFields() {
    if (!isset($this->destinationIdFields)) {
      $this->destinationIdFields = [];
      $count = 1;
      foreach ($this->migration->getDestinationPlugin()->getIds() as $field => $schema) {
        $this->destinationIdFields[$field] = 'destid' . $count++;
      }
    }
    return $this->destinationIdFields;
  }

  /**
   * The name of the database map table.
   *
   * @return string
   *   The map table name.
   */
  public function mapTableName() {
    return $this->mapTableName;
  }

  /**
   * The name of the database message table.
   *
   * @return string
   *   The message table name.
   */
  public function messageTableName() {
    return $this->messageTableName;
  }

  /**
   * Get the fully qualified map table name.
   *
   * @return string
   *   The fully qualified map table name.
   */
  public function getQualifiedMapTableName() {
    return $this->getDatabase()->getFullQualifiedTableName($this->mapTableName);
  }

  /**
   * Gets the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection object.
   */
  public function getDatabase() {
    $this->init();
    return $this->database;
  }

  /**
   * Initialize the plugin.
   */
  protected function init() {
    if (!$this->initialized) {
      $this->initialized = TRUE;
      $this->ensureTables();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage(MigrateMessageInterface $message) {
    $this->message = $message;
  }

  /**
   * Create the map and message tables if they don't already exist.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   */
  protected function ensureTables() {
    if (!$this->getDatabase()->schema()->tableExists($this->mapTableName)) {
      // Generate appropriate schema info for the map and message tables,
      // and map from the source field names to the map/msg field names.
      $count = 1;
      $source_id_schema = [];
      $indexes = [];
      foreach ($this->migration->getSourcePlugin()->getIds() as $id_definition) {
        $map_key = 'sourceid' . $count++;
        $indexes['source'][] = $map_key;
        $source_id_schema[$map_key] = $this->getFieldSchema($id_definition);
        $source_id_schema[$map_key]['not null'] = TRUE;
      }

      $source_ids_hash[$this::SOURCE_IDS_HASH] = [
        'type' => 'varchar',
        'length' => '64',
        'not null' => TRUE,
        'description' => 'Hash of source ids. Used as primary key',
      ];
      $fields = $source_ids_hash + $source_id_schema;

      // Add destination identifiers to map table.
      // @todo How do we discover the destination schema?
      $count = 1;
      foreach ($this->migration->getDestinationPlugin()->getIds() as $id_definition) {
        // Allow dest identifier fields to be NULL (for IGNORED/FAILED cases).
        $map_key = 'destid' . $count++;
        $fields[$map_key] = $this->getFieldSchema($id_definition);
        $fields[$map_key]['not null'] = FALSE;
      }
      $fields['source_row_status'] = [
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => MigrateIdMapInterface::STATUS_IMPORTED,
        'description' => 'Indicates current status of the source row',
      ];
      $fields['rollback_action'] = [
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'description' => 'Flag indicating what to do for this item on rollback',
      ];
      $fields['last_imported'] = [
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
        'description' => 'UNIX timestamp of the last time this row was imported',
        'size' => 'big',
      ];
      $fields['hash'] = [
        'type' => 'varchar',
        'length' => '64',
        'not null' => FALSE,
        'description' => 'Hash of source row data, for detecting changes',
      ];

      // To keep within the MySQL maximum key length of 3072 bytes we try
      // different groupings of the source IDs. Groups are created in chunks
      // starting at a chunk size equivalent to the number of the source IDs.
      // On each loop the chunk size is reduced by one until either the map
      // table is successfully created or the chunk_size is less than zero. If
      // there are no source IDs the table is created.
      $chunk_size = count($source_id_schema);
      while ($chunk_size >= 0) {
        $indexes = [];
        if ($chunk_size > 0) {
          foreach (array_chunk(array_keys($source_id_schema), $chunk_size) as $key => $index_columns) {
            $index_name = ($key === 0) ? 'source' : "source$key";
            $indexes[$index_name] = $index_columns;
          }
        }
        $schema = [
          'description' => 'Mappings from source identifier value(s) to destination identifier value(s).',
          'fields' => $fields,
          'primary key' => [$this::SOURCE_IDS_HASH],
          'indexes' => $indexes,
        ];

        try {
          $this->getDatabase()
            ->schema()
            ->createTable($this->mapTableName, $schema);
          break;
        }
        catch (SchemaTableKeyTooLargeException $e) {
          $chunk_size--;
          // Rethrow the exception if the source IDs can not be in smaller
          // groups.
          if ($chunk_size <= 0) {
            throw $e;
          }
        }
      }

      // Now do the message table.
      if (!$this->getDatabase()->schema()->tableExists($this->messageTableName())) {
        $fields = [];
        $fields['msgid'] = [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ];
        $fields += $source_ids_hash;

        $fields['level'] = [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 1,
        ];
        $fields['message'] = [
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ];
        $schema = [
          'description' => 'Messages generated during a migration process',
          'fields' => $fields,
          'primary key' => ['msgid'],
          'indexes' => [$this::SOURCE_IDS_HASH => [$this::SOURCE_IDS_HASH]],
        ];
        $this->getDatabase()->schema()->createTable($this->messageTableName(), $schema);
      }
    }
    else {
      // Add any missing columns to the map table.
      if (!$this->getDatabase()->schema()->fieldExists($this->mapTableName,
                                                    'rollback_action')) {
        $this->getDatabase()->schema()->addField($this->mapTableName, 'rollback_action',
          [
            'type' => 'int',
            'size' => 'tiny',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Flag indicating what to do for this item on rollback',
          ]
        );
      }
      if (!$this->getDatabase()->schema()->fieldExists($this->mapTableName, 'hash')) {
        $this->getDatabase()->schema()->addField($this->mapTableName, 'hash',
          [
            'type' => 'varchar',
            'length' => '64',
            'not null' => FALSE,
            'description' => 'Hash of source row data, for detecting changes',
          ]
        );
      }
      if (!$this->getDatabase()->schema()->fieldExists($this->mapTableName, $this::SOURCE_IDS_HASH)) {
        $this->getDatabase()->schema()->addField($this->mapTableName, $this::SOURCE_IDS_HASH, [
          'type' => 'varchar',
          'length' => '64',
          'not null' => TRUE,
          'description' => 'Hash of source ids. Used as primary key',
        ]);
      }
    }
  }

  /**
   * Creates schema from an ID definition.
   *
   * @param array $id_definition
   *   The definition of the field having the structure as the items returned by
   *   MigrateSourceInterface or MigrateDestinationInterface::getIds().
   *
   * @return array
   *   The database schema definition.
   *
   * @see \Drupal\migrate\Plugin\MigrateSourceInterface::getIds()
   * @see \Drupal\migrate\Plugin\MigrateDestinationInterface::getIds()
   */
  protected function getFieldSchema(array $id_definition) {
    $type_parts = explode('.', $id_definition['type']);
    if (count($type_parts) == 1) {
      $type_parts[] = 'value';
    }
    unset($id_definition['type']);

    // Get the field storage definition.
    $definition = BaseFieldDefinition::create($type_parts[0]);

    // Get a list of setting keys belonging strictly to the field definition.
    $default_field_settings = $definition->getSettings();
    // Separate field definition settings from custom settings. Custom settings
    // are settings passed in $id_definition that are not part of field storage
    // definition settings.
    $field_settings = array_intersect_key($id_definition, $default_field_settings);
    $custom_settings = array_diff_key($id_definition, $default_field_settings);

    // Resolve schema from field storage definition settings.
    $schema = $definition
      ->setSettings($field_settings)
      ->getColumns()[$type_parts[1]];

    // Merge back custom settings.
    return $schema + $custom_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getRowBySource(array $source_id_values) {
    $query = $this->getDatabase()->select($this->mapTableName(), 'map')
      ->fields('map');
    $query->condition($this::SOURCE_IDS_HASH, $this->getSourceIdsHash($source_id_values));
    $result = $query->execute();
    return $result->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function getRowByDestination(array $destination_id_values) {
    $query = $this->getDatabase()->select($this->mapTableName(), 'map')
      ->fields('map');
    foreach ($this->destinationIdFields() as $field_name => $destination_id) {
      if (!isset($destination_id_values[$field_name])) {
        return [];
      }
      $query->condition("map.$destination_id", $destination_id_values[$field_name], '=');
    }
    $result = $query->execute()->fetchAssoc();
    return $result ? $result : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRowsNeedingUpdate($count) {
    $rows = [];
    $result = $this->getDatabase()->select($this->mapTableName(), 'map')
      ->fields('map')
      ->condition('source_row_status', MigrateIdMapInterface::STATUS_NEEDS_UPDATE)
      ->range(0, $count)
      ->execute();
    foreach ($result as $row) {
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function lookupSourceId(array $destination_id_values) {
    $source_id_fields = $this->sourceIdFields();
    $query = $this->getDatabase()->select($this->mapTableName(), 'map');
    foreach ($source_id_fields as $source_field_name => $id_map_field_name) {
      $query->addField('map', $id_map_field_name, $source_field_name);
    }
    foreach ($this->destinationIdFields() as $field_name => $destination_id) {
      $query->condition("map.$destination_id", $destination_id_values[$field_name], '=');
    }
    $result = $query->execute();
    return $result->fetchAssoc() ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function lookupDestinationIds(array $source_id_values) {
    if (empty($source_id_values)) {
      return [];
    }

    // Canonicalize the keys into a hash of DB-field => value.
    $is_associative = !isset($source_id_values[0]);
    $conditions = [];
    foreach ($this->sourceIdFields() as $field_name => $db_field) {
      if ($is_associative) {
        // Ensure to handle array elements with a NULL value.
        if (array_key_exists($field_name, $source_id_values)) {
          // Associative $source_id_values can have fields out of order.
          if (isset($source_id_values[$field_name])) {
            // Only add a condition if the value is not NULL.
            $conditions[$db_field] = $source_id_values[$field_name];
          }
          unset($source_id_values[$field_name]);
        }
      }
      else {
        // For non-associative $source_id_values, we assume they're the first
        // few fields.
        if (empty($source_id_values)) {
          break;
        }
        $conditions[$db_field] = array_shift($source_id_values);
      }
    }

    if (!empty($source_id_values)) {
      $var_dump = var_export($source_id_values, TRUE);
      throw new MigrateException(sprintf("Extra unknown items for map %s in source IDs: %s", $this->mapTableName(), $var_dump));
    }

    $query = $this->getDatabase()->select($this->mapTableName(), 'map')
      ->fields('map', $this->destinationIdFields());
    if (count($this->sourceIdFields()) === count($conditions)) {
      // Optimization: Use the primary key.
      $query->condition($this::SOURCE_IDS_HASH, $this->getSourceIdsHash(array_values($conditions)));
    }
    else {
      foreach ($conditions as $db_field => $value) {
        $query->condition($db_field, $value);
      }
    }

    try {
      return $query->execute()->fetchAll(\PDO::FETCH_NUM);
    }
    catch (DatabaseExceptionWrapper $e) {
      // It's possible that the query will cause an exception to be thrown. For
      // example, the URL alias migration uses a dummy node ID of 'INVALID_NID'
      // to cause the lookup to return no results. On PostgreSQL this causes an
      // exception because 'INVALID_NID' is not the expected type.
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveIdMapping(Row $row, array $destination_id_values, $source_row_status = MigrateIdMapInterface::STATUS_IMPORTED, $rollback_action = MigrateIdMapInterface::ROLLBACK_DELETE) {
    // Construct the source key.
    $source_id_values = $row->getSourceIdValues();
    // Construct the source key and initialize to empty variable keys.
    $fields = [];
    foreach ($this->sourceIdFields() as $field_name => $key_name) {
      // A NULL key value is usually an indication of a problem.
      if (!isset($source_id_values[$field_name])) {
        $this->message->display($this->t(
          'Did not save to map table due to NULL value for key field @field',
          ['@field' => $field_name]), 'error');
        return;
      }
      $fields[$key_name] = $source_id_values[$field_name];
    }

    if (!$fields) {
      return;
    }

    $fields += [
      'source_row_status' => (int) $source_row_status,
      'rollback_action' => (int) $rollback_action,
      'hash' => $row->getHash(),
    ];
    $count = 0;
    foreach ($destination_id_values as $dest_id) {
      $fields['destid' . ++$count] = $dest_id;
    }
    if ($count && $count != count($this->destinationIdFields())) {
      $this->message->display($this->t('Could not save to map table due to missing destination id values'), 'error');
      return;
    }
    $fields['last_imported'] = time();
    // Notify anyone listening of the map row we're about to save.
    $this->eventDispatcher->dispatch(new MigrateMapSaveEvent($this, $fields), MigrateEvents::MAP_SAVE);
    $this->getDatabase()->merge($this->mapTableName())
      ->key($this::SOURCE_IDS_HASH, $this->getSourceIdsHash($source_id_values))
      ->fields($fields)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function saveMessage(array $source_id_values, $message, $level = MigrationInterface::MESSAGE_ERROR) {
    foreach ($this->sourceIdFields() as $field_name => $source_id) {
      // If any key value is not set, we can't save.
      if (!isset($source_id_values[$field_name])) {
        return;
      }
    }
    $fields[$this::SOURCE_IDS_HASH] = $this->getSourceIdsHash($source_id_values);
    $fields['level'] = $level;
    $fields['message'] = $message;
    $this->getDatabase()->insert($this->messageTableName())
      ->fields($fields)
      ->execute();

    // Notify anyone listening of the message we've saved.
    $this->eventDispatcher->dispatch(new MigrateIdMapMessageEvent($this->migration, $source_id_values, $message, $level), MigrateEvents::IDMAP_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(array $source_id_values = [], $level = NULL) {
    $query = $this->getDatabase()->select($this->messageTableName(), 'msg');
    $condition = sprintf('[msg].[%s] = [map].[%s]', $this::SOURCE_IDS_HASH, $this::SOURCE_IDS_HASH);
    $query->addJoin('LEFT', $this->mapTableName(), 'map', $condition);
    // Explicitly define the fields we want. The order will be preserved: source
    // IDs, destination IDs (if possible), and then the rest.
    foreach ($this->sourceIdFields() as $id => $column_name) {
      $query->addField('map', $column_name, "src_$id");
    }
    foreach ($this->destinationIdFields() as $id => $column_name) {
      $query->addField('map', $column_name, "dest_$id");
    }
    $query->fields('msg', ['msgid', $this::SOURCE_IDS_HASH, 'level', 'message']);
    if ($source_id_values) {
      $query->condition('msg.' . $this::SOURCE_IDS_HASH, $this->getSourceIdsHash($source_id_values));
    }
    if ($level) {
      $query->condition('msg.level', $level);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareUpdate() {
    $this->getDatabase()->update($this->mapTableName())
      ->fields(['source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function processedCount() {
    return $this->countHelper(NULL, $this->mapTableName());
  }

  /**
   * {@inheritdoc}
   */
  public function importedCount() {
    return $this->countHelper([
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateCount() {
    return $this->countHelper(MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
  }

  /**
   * {@inheritdoc}
   */
  public function errorCount() {
    return $this->countHelper(MigrateIdMapInterface::STATUS_FAILED);
  }

  /**
   * {@inheritdoc}
   */
  public function messageCount() {
    return $this->countHelper(NULL, $this->messageTableName());
  }

  /**
   * Counts records in a table.
   *
   * @param int|array $status
   *   (optional) Status code(s) to filter the source_row_status column.
   * @param string $table
   *   (optional) The table to work. Defaults to NULL.
   *
   * @return int
   *   The number of records.
   */
  protected function countHelper($status = NULL, $table = NULL) {
    // Use database directly to avoid creating tables.
    $query = $this->database->select($table ?: $this->mapTableName());
    if (isset($status)) {
      $query->condition('source_row_status', $status, is_array($status) ? 'IN' : '=');
    }
    try {
      $count = (int) $query->countQuery()->execute()->fetchField();
    }
    catch (DatabaseException $e) {
      // The table does not exist, therefore there are no records.
      $count = 0;
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $source_id_values, $messages_only = FALSE) {
    if (empty($source_id_values)) {
      throw new MigrateException('Without source identifier values it is impossible to find the row to delete.');
    }

    if (!$messages_only) {
      $map_query = $this->getDatabase()->delete($this->mapTableName());
      $map_query->condition($this::SOURCE_IDS_HASH, $this->getSourceIdsHash($source_id_values));
      // Notify anyone listening of the map row we're about to delete.
      $this->eventDispatcher->dispatch(new MigrateMapDeleteEvent($this, $source_id_values), MigrateEvents::MAP_DELETE);
      $map_query->execute();
    }
    $message_query = $this->getDatabase()->delete($this->messageTableName());
    $message_query->condition($this::SOURCE_IDS_HASH, $this->getSourceIdsHash($source_id_values));
    $message_query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDestination(array $destination_id_values) {
    $map_query = $this->getDatabase()->delete($this->mapTableName());
    $message_query = $this->getDatabase()->delete($this->messageTableName());
    $source_id_values = $this->lookupSourceId($destination_id_values);
    if (!empty($source_id_values)) {
      foreach ($this->destinationIdFields() as $field_name => $destination_id) {
        $map_query->condition($destination_id, $destination_id_values[$field_name]);
      }
      // Notify anyone listening of the map row we're about to delete.
      $this->eventDispatcher->dispatch(new MigrateMapDeleteEvent($this, $source_id_values), MigrateEvents::MAP_DELETE);
      $map_query->execute();

      $message_query->condition($this::SOURCE_IDS_HASH, $this->getSourceIdsHash($source_id_values));
      $message_query->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdate(array $source_id_values) {
    if (empty($source_id_values)) {
      throw new MigrateException('No source identifiers provided to update.');
    }
    $query = $this->getDatabase()
      ->update($this->mapTableName())
      ->fields(['source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE]);

    foreach ($this->sourceIdFields() as $field_name => $source_id) {
      $query->condition($source_id, $source_id_values[$field_name]);
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearMessages() {
    $this->getDatabase()->truncate($this->messageTableName())->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    $this->getDatabase()->schema()->dropTable($this->mapTableName());
    $this->getDatabase()->schema()->dropTable($this->messageTableName());
  }

  /**
   * Implementation of \Iterator::rewind().
   *
   * This is called before beginning a foreach loop.
   */
  #[\ReturnTypeWillChange]
  public function rewind() {
    $this->currentRow = NULL;
    $fields = [];
    foreach ($this->sourceIdFields() as $field) {
      $fields[] = $field;
    }
    foreach ($this->destinationIdFields() as $field) {
      $fields[] = $field;
    }
    $this->result = $this->getDatabase()->select($this->mapTableName(), 'map')
      ->fields('map', $fields)
      ->orderBy('destid1')
      ->execute();
    $this->next();
  }

  /**
   * Implementation of \Iterator::current().
   *
   * This is called when entering a loop iteration, returning the current row.
   */
  #[\ReturnTypeWillChange]
  public function current() {
    return $this->currentRow;
  }

  /**
   * Implementation of \Iterator::key().
   *
   * This is called when entering a loop iteration, returning the key of the
   * current row. It must be a scalar - we will serialize to fulfill the
   * requirement, but using getCurrentKey() is preferable.
   */
  #[\ReturnTypeWillChange]
  public function key() {
    return serialize($this->currentKey);
  }

  /**
   * {@inheritdoc}
   */
  public function currentDestination() {
    if ($this->valid()) {
      $result = [];
      foreach ($this->destinationIdFields() as $destination_field_name => $id_map_field_name) {
        if (!is_null($this->currentRow[$id_map_field_name])) {
          $result[$destination_field_name] = $this->currentRow[$id_map_field_name];
        }
      }
      return $result;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function currentSource() {
    if ($this->valid()) {
      $result = [];
      foreach ($this->sourceIdFields() as $field_name => $source_id) {
        $result[$field_name] = $this->currentKey[$source_id];
      }
      return $result;
    }
    else {
      return NULL;
    }
  }

  /**
   * Implementation of \Iterator::next().
   *
   * This is called at the bottom of the loop implicitly, as well as explicitly
   * from rewind().
   */
  #[\ReturnTypeWillChange]
  public function next() {
    $this->currentRow = $this->result->fetchAssoc();
    $this->currentKey = [];
    if ($this->currentRow) {
      foreach ($this->sourceIdFields() as $map_field) {
        $this->currentKey[$map_field] = $this->currentRow[$map_field];
        // Leave only destination fields.
        unset($this->currentRow[$map_field]);
      }
    }
  }

  /**
   * Implementation of \Iterator::valid().
   *
   * This is called at the top of the loop, returning TRUE to process the loop
   * and FALSE to terminate it.
   */
  #[\ReturnTypeWillChange]
  public function valid() {
    return $this->currentRow !== FALSE;
  }

  /**
   * Returns the migration plugin manager.
   *
   * @return \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   *   The migration plugin manager.
   *
   * @deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. Use
   *   $this->migrationPluginManager instead.
   *
   * @see https://www.drupal.org/node/3277306
   */
  protected function getMigrationPluginManager() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. Use $this->migrationPluginManager instead. See https://www.drupal.org/node/3277306', E_USER_DEPRECATED);
    return $this->migrationPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestId() {
    // Ensure that the first ID is an integer.
    $keys = $this->migration->getDestinationPlugin()->getIds();
    if (reset($keys)['type'] !== 'integer') {
      throw new \LogicException('To determine the highest migrated ID the first ID must be an integer');
    }

    // List of mapping tables to look in for the highest ID.
    $map_tables = [
      $this->migration->id() => $this->mapTableName(),
    ];

    // If there's a bundle, it means we have a derived migration and we need to
    // find all the mapping tables from the related derived migrations.
    if ($base_id = substr($this->migration->id(), 0, strpos($this->migration->id(), $this::DERIVATIVE_SEPARATOR))) {
      $migrations = $this->migrationPluginManager->getDefinitions();
      foreach ($migrations as $migration_id => $migration) {
        if ($migration['id'] === $base_id) {
          // Get this derived migration's mapping table and add it to the list
          // of mapping tables to look in for the highest ID.
          $stub = $this->migrationPluginManager->createInstance($migration_id);
          $map_tables[$migration_id] = $stub->getIdMap()->mapTableName();
        }
      }
    }

    // Get the highest id from the list of map tables.
    $ids = [0];
    foreach ($map_tables as $map_table) {
      // If the map_table does not exist then continue on to the next map_table.
      if (!$this->getDatabase()->schema()->tableExists($map_table)) {
        continue;
      }

      $query = $this->getDatabase()->select($map_table, 'map')
        ->fields('map', $this->destinationIdFields())
        ->range(0, 1);
      foreach (array_values($this->destinationIdFields()) as $order_field) {
        $query->orderBy($order_field, 'DESC');
      }
      $ids[] = $query->execute()->fetchField();
    }

    // Return the highest of all the mapped IDs.
    return (int) max($ids);
  }

}
