<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\id_map\Sql.
 */

namespace Drupal\migrate\Plugin\migrate\id_map;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\Event\MigrateMapDeleteEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the sql based ID map implementation.
 *
 * It creates one map and one message table per migration entity to store the
 * relevant information.
 *
 * @PluginID("sql")
 */
class Sql extends PluginBase implements MigrateIdMapInterface, ContainerFactoryPluginInterface {

  /**
   * An event dispatcher instance to use for map events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
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
   * The migrate message.
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
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * The migration being done.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
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
   * @var null
   */
  protected $result = NULL;

  /**
   * The source identifiers.
   *
   * @var array
   */
  protected $sourceIds = array();

  /**
   * The destination identifiers.
   *
   * @var array
   */
  protected $destinationIds = array();

  /**
   * The current row.
   *
   * @var null
   */
  protected $currentRow = NULL;

  /**
   * The current key.
   *
   * @var array
   */
  protected $currentKey = array();

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
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration to do.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->eventDispatcher = $event_dispatcher;
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
      $container->get('event_dispatcher')
    );
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
      $this->sourceIdFields = array();
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
      $this->destinationIdFields = array();
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
    $this->init();
    return $this->mapTableName;
  }

  /**
   * The name of the database message table.
   *
   * @return string
   *   The message table name.
   */
  public function messageTableName() {
    $this->init();
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
    if (!isset($this->database)) {
      $this->database = \Drupal::database();
    }
    $this->init();
    return $this->database;
  }

  /**
   * Initialize the plugin.
   */
  protected function init() {
    if (!$this->initialized) {
      $this->initialized = TRUE;
      // Default generated table names, limited to 63 characters.
      $machine_name = str_replace(':', '__', $this->migration->id());
      $prefix_length = strlen($this->getDatabase()->tablePrefix());
      $this->mapTableName = 'migrate_map_' . Unicode::strtolower($machine_name);
      $this->mapTableName = Unicode::substr($this->mapTableName, 0, 63 - $prefix_length);
      $this->messageTableName = 'migrate_message_' . Unicode::strtolower($machine_name);
      $this->messageTableName = Unicode::substr($this->messageTableName, 0, 63 - $prefix_length);
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
   */
  protected function ensureTables() {
    if (!$this->getDatabase()->schema()->tableExists($this->mapTableName)) {
      // Generate appropriate schema info for the map and message tables,
      // and map from the source field names to the map/msg field names.
      $count = 1;
      $source_id_schema = array();
      $pks = array();
      foreach ($this->migration->getSourcePlugin()->getIds() as $id_definition) {
        $mapkey = 'sourceid' . $count++;
        $source_id_schema[$mapkey] = $this->getFieldSchema($id_definition);
        $source_id_schema[$mapkey]['not null'] = TRUE;

        // With InnoDB, utf8mb4-based primary keys can't be over 191 characters.
        // Use ASCII-based primary keys instead.
        if (isset($source_id_schema[$mapkey]['type']) && $source_id_schema[$mapkey]['type'] == 'varchar') {
          $source_id_schema[$mapkey]['type'] = 'varchar_ascii';
        }
        $pks[] = $mapkey;
      }

      $fields = $source_id_schema;

      // Add destination identifiers to map table.
      // TODO: How do we discover the destination schema?
      $count = 1;
      foreach ($this->migration->getDestinationPlugin()->getIds() as $id_definition) {
        // Allow dest identifier fields to be NULL (for IGNORED/FAILED
        // cases).
        $mapkey = 'destid' . $count++;
        $fields[$mapkey] = $this->getFieldSchema($id_definition);
        $fields[$mapkey]['not null'] = FALSE;
      }
      $fields['source_row_status'] = array(
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => MigrateIdMapInterface::STATUS_IMPORTED,
        'description' => 'Indicates current status of the source row',
      );
      $fields['rollback_action'] = array(
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'description' => 'Flag indicating what to do for this item on rollback',
      );
      $fields['last_imported'] = array(
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
        'description' => 'UNIX timestamp of the last time this row was imported',
      );
      $fields['hash'] = array(
        'type' => 'varchar',
        'length' => '64',
        'not null' => FALSE,
        'description' => 'Hash of source row data, for detecting changes',
      );
      $schema = array(
        'description' => 'Mappings from source identifier value(s) to destination identifier value(s).',
        'fields' => $fields,
      );
      if ($pks) {
        $schema['primary key'] = $pks;
      }
      $this->getDatabase()->schema()->createTable($this->mapTableName, $schema);

      // Now do the message table.
      if (!$this->getDatabase()->schema()->tableExists($this->messageTableName())) {
        $fields = array();
        $fields['msgid'] = array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        );
        $fields += $source_id_schema;

        $fields['level'] = array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 1,
        );
        $fields['message'] = array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        );
        $schema = array(
          'description' => 'Messages generated during a migration process',
          'fields' => $fields,
          'primary key' => array('msgid'),
        );
        if ($pks) {
          $schema['indexes']['sourcekey'] = $pks;
        }
        $this->getDatabase()->schema()->createTable($this->messageTableName(), $schema);
      }
    }
    else {
      // Add any missing columns to the map table.
      if (!$this->getDatabase()->schema()->fieldExists($this->mapTableName,
                                                    'rollback_action')) {
        $this->getDatabase()->schema()->addField($this->mapTableName,
                                              'rollback_action', array(
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Flag indicating what to do for this item on rollback',
        ));
      }
      if (!$this->getDatabase()->schema()->fieldExists($this->mapTableName, 'hash')) {
        $this->getDatabase()->schema()->addField($this->mapTableName, 'hash', array(
          'type' => 'varchar',
          'length' => '64',
          'not null' => FALSE,
          'description' => 'Hash of source row data, for detecting changes',
        ));
      }
    }
  }

  /**
   * Create schema from an id definition.
   *
   * @param array $id_definition
   *   A field schema definition. Can be SQL schema or a type data
   *   based schema. In the latter case, the value of type needs to be
   *   $typed_data_type.$column
   * @return array
   */
  protected function getFieldSchema(array $id_definition) {
    $type_parts = explode('.', $id_definition['type']);
    if (count($type_parts) == 1) {
      $type_parts[] = 'value';
    }
    $schema = BaseFieldDefinition::create($type_parts[0])->getColumns();
    return $schema[$type_parts[1]];
  }

  /**
   * {@inheritdoc}
   */
  public function getRowBySource(array $source_id_values) {
    $query = $this->getDatabase()->select($this->mapTableName(), 'map')
              ->fields('map');
    foreach ($this->sourceIdFields() as $field_name => $source_id) {
      $query->condition("map.$source_id", $source_id_values[$field_name], '=');
    }
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
      $query->condition("map.$destination_id", $destination_id_values[$field_name], '=');
    }
    $result = $query->execute();
    return $result->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function getRowsNeedingUpdate($count) {
    $rows = array();
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
  public function lookupSourceID(array $destination_id_values) {
    $source_id_fields = $this->sourceIdFields();
    $query = $this->getDatabase()->select($this->mapTableName(), 'map');
    foreach ($source_id_fields as $source_field_name => $idmap_field_name) {
      $query->addField('map', $idmap_field_name, $source_field_name);
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
  public function lookupDestinationId(array $source_id_values) {
    if (empty($source_id_values)) {
      return array();
    }
    $query = $this->getDatabase()->select($this->mapTableName(), 'map')
              ->fields('map', $this->destinationIdFields());

    // When looking up the destination ID we require an array with both the
    // source key and value, e.g. ['nid' => 41]. However, the Migration process
    // plugin doesn't currently have a way to get the source key so we presume
    // the values have been passed through in the correct order.
    $have_keys = !isset($source_id_values[0]);
    foreach ($this->sourceIdFields() as $field_name => $source_id) {
      $query->condition("map.$source_id", $have_keys ? $source_id_values[$field_name] : array_shift($source_id_values), '=');
    }
    $result = $query->execute();
    $destination_id = $result->fetchAssoc();
    return array_values($destination_id ?: array());
  }

  /**
   * {@inheritdoc}
   */
  public function saveIdMapping(Row $row, array $destination_id_values, $source_row_status = MigrateIdMapInterface::STATUS_IMPORTED, $rollback_action = MigrateIdMapInterface::ROLLBACK_DELETE) {
    // Construct the source key.
    $source_id_values = $row->getSourceIdValues();
    // Construct the source key and initialize to empty variable keys.
    $keys = array();
    foreach ($this->sourceIdFields() as $field_name => $key_name) {
      // A NULL key value will fail.
      if (!isset($source_id_values[$field_name])) {
        $this->message->display(t(
          'Could not save to map table due to NULL value for key field @field',
          array('@field' => $field_name)), 'error');
        return;
      }
      $keys[$key_name] = $source_id_values[$field_name];
    }

    $fields = array(
      'source_row_status' => (int) $source_row_status,
      'rollback_action' => (int) $rollback_action,
      'hash' => $row->getHash(),
    );
    $count = 0;
    foreach ($destination_id_values as $dest_id) {
      $fields['destid' . ++$count] = $dest_id;
    }
    if ($count && $count != count($this->destinationIdFields())) {
      $this->message->display(t('Could not save to map table due to missing destination id values'), 'error');
      return;
    }
    if ($this->migration->get('trackLastImported')) {
      $fields['last_imported'] = time();
    }
    if ($keys) {
      // Notify anyone listening of the map row we're about to save.
      $this->eventDispatcher->dispatch(MigrateEvents::MAP_SAVE, new MigrateMapSaveEvent($this, $keys + $fields));
      $this->getDatabase()->merge($this->mapTableName())
        ->key($keys)
        ->fields($fields)
        ->execute();
    }
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
      $fields[$source_id] = $source_id_values[$field_name];
    }
    $fields['level'] = $level;
    $fields['message'] = $message;
    $this->getDatabase()->insert($this->messageTableName())
      ->fields($fields)
      ->execute();

    // Notify anyone listening of the message we've saved.
    $this->eventDispatcher->dispatch(MigrateEvents::IDMAP_MESSAGE,
      new MigrateIdMapMessageEvent($this->migration, $source_id_values, $message, $level));
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageIterator(array $source_id_values = [], $level = NULL) {
    $query = $this->getDatabase()->select($this->messageTableName(), 'msg')
      ->fields('msg');
    foreach ($this->sourceIdFields() as $field_name => $source_id) {
      if (isset($source_id_values[$field_name])) {
        $query->condition($source_id, $source_id_values[$field_name]);
      }
    }

    if ($level) {
      $query->condition('level', $level);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareUpdate() {
    $this->getDatabase()->update($this->mapTableName())
    ->fields(array('source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE))
    ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function processedCount() {
    return $this->getDatabase()->select($this->mapTableName())
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function importedCount() {
    return $this->getDatabase()->select($this->mapTableName())
      ->condition('source_row_status', array(MigrateIdMapInterface::STATUS_IMPORTED, MigrateIdMapInterface::STATUS_NEEDS_UPDATE), 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();
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
   * @param $status
   *   An integer for the source_row_status column.
   * @param $table
   *   The table to work
   * @return int
   *   The number of records.
   */
  protected function countHelper($status, $table = NULL) {
    $query = $this->getDatabase()->select($table ?: $this->mapTableName());
    if (isset($status)) {
      $query->condition('source_row_status', $status);
    }
    return $query->countQuery()->execute()->fetchField();
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
    }
    $message_query = $this->getDatabase()->delete($this->messageTableName());
    foreach ($this->sourceIdFields() as $field_name => $source_id) {
      if (!$messages_only) {
        $map_query->condition($source_id, $source_id_values[$field_name]);
      }
      $message_query->condition($source_id, $source_id_values[$field_name]);
    }

    if (!$messages_only) {
      // Notify anyone listening of the map row we're about to delete.
      $this->eventDispatcher->dispatch(MigrateEvents::MAP_DELETE, new MigrateMapDeleteEvent($this, $source_id_values));
      $map_query->execute();
    }
    $message_query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDestination(array $destination_id_values) {
    $map_query = $this->getDatabase()->delete($this->mapTableName());
    $message_query = $this->getDatabase()->delete($this->messageTableName());
    $source_id_values = $this->lookupSourceID($destination_id_values);
    if (!empty($source_id_values)) {
      foreach ($this->destinationIdFields() as $field_name => $destination_id) {
        $map_query->condition($destination_id, $destination_id_values[$field_name]);
      }
      // Notify anyone listening of the map row we're about to delete.
      $this->eventDispatcher->dispatch(MigrateEvents::MAP_DELETE, new MigrateMapDeleteEvent($this, $source_id_values));
      $map_query->execute();
      $count = 1;
      foreach ($this->sourceIdFields() as $field_name => $source_id) {
        $message_query->condition($source_id, $source_id_values[$field_name]);
        $count++;
      }
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
      ->fields(array('source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE));

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
   * Implementation of Iterator::rewind().
   *
   * This is called before beginning a foreach loop.
   */
  public function rewind() {
    $this->currentRow = NULL;
    $fields = array();
    foreach ($this->sourceIdFields() as $field) {
      $fields[] = $field;
    }
    foreach ($this->destinationIdFields() as $field) {
      $fields[] = $field;
    }
    $this->result = $this->getDatabase()->select($this->mapTableName(), 'map')
      ->fields('map', $fields)
      ->execute();
    $this->next();
  }

  /**
   * Implementation of Iterator::current().
   *
   * This is called when entering a loop iteration, returning the current row.
   */
  public function current() {
    return $this->currentRow;
  }

  /**
   * Implementation of Iterator::key().
   *
   * This is called when entering a loop iteration, returning the key of the
   * current row. It must be a scalar - we will serialize to fulfill the
   * requirement, but using getCurrentKey() is preferable.
   */
  public function key() {
    return serialize($this->currentKey);
  }

  /**
   * @inheritdoc
   */
  public function currentDestination() {
    if ($this->valid()) {
      $result = array();
      foreach ($this->destinationIdFields() as $destination_field_name => $idmap_field_name) {
        $result[$destination_field_name] = $this->currentRow[$idmap_field_name];
      }
      return $result;
    }
    else {
      return NULL;
    }
  }

  /**
   * Implementation of Iterator::next().
   *
   * This is called at the bottom of the loop implicitly, as well as explicitly
   * from rewind().
   */
  public function next() {
    $this->currentRow = $this->result->fetchAssoc();
    $this->currentKey = array();
    if ($this->currentRow) {
      foreach ($this->sourceIdFields() as $map_field) {
        $this->currentKey[$map_field] = $this->currentRow[$map_field];
        // Leave only destination fields.
        unset($this->currentRow[$map_field]);
      }
    }
  }

  /**
   * Implementation of Iterator::valid().
   *
   * This is called at the top of the loop, returning TRUE to process the loop
   * and FALSE to terminate it.
   */
  public function valid() {
    return $this->currentRow !== FALSE;
  }

}
