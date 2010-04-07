<?php
// $Id$

/**
 * @file
 * Database schema code for SQLite databases.
 */


/**
 * @ingroup schemaapi
 * @{
 */

class DatabaseSchema_sqlite extends DatabaseSchema {

	/**
   * Override DatabaseSchema::$defaultSchema
   */
  protected $defaultSchema = 'main';

  public function tableExists($table) {
    // Don't use {} around sqlite_master table.
    return (bool) $this->connection->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name LIKE '{" . $table . "}'", array(), array())->fetchField();
  }

  public function fieldExists($table, $column) {
    $schema = $this->introspectSchema($table);
    return !empty($schema['fields'][$column]);
  }

  /**
   * Generate SQL to create a new table from a Drupal schema definition.
   *
   * @param $name
   *   The name of the table to create.
   * @param $table
   *   A Schema API table definition array.
   * @return
   *   An array of SQL statements to create the table.
   */
  public function createTableSql($name, $table) {
    $sql = array();
    $sql[] = "CREATE TABLE {" . $name . "} (\n" . $this->createColumsSql($name, $table) . "\n);\n";
    return array_merge($sql, $this->createIndexSql($name, $table));
  }

  /**
   * Build the SQL expression for indexes.
   */
  protected function createIndexSql($tablename, $schema) {
    $sql = array();
    $info = $this->getPrefixInfo($tablename);
    if (!empty($schema['unique keys'])) {
      foreach ($schema['unique keys'] as $key => $fields) {
        // Normally we don't escape double quotes (we use single quotes) but
        // describing the index name like this is faster and is readable.
        $index = "\"{$info['schema']}\".\"{$info['table']}_$key\"";
        $sql[] = 'CREATE UNIQUE INDEX ' . $index . ' ON ' . $info['table'] . ' (' . $this->createKeySql($fields) . "); \n";
      }
    }
    if (!empty($schema['indexes'])) {
      foreach ($schema['indexes'] as $key => $fields) {
        $index = "\"{$info['schema']}\".\"{$info['table']}_$key\"";
        $sql[] = 'CREATE INDEX ' . $index . ' ON ' . $info['table'] . ' (' . $this->createKeySql($fields) . "); \n";
      }
    }
    return $sql;
  }

  /**
   * Build the SQL expression for creating columns.
   */
  protected function createColumsSql($tablename, $schema) {
    $sql_array = array();

    // Add the SQL statement for each field.
    foreach ($schema['fields'] as $name => $field) {
      if ($field['type'] == 'serial') {
        if (isset($schema['primary key']) && ($key = array_search($name, $schema['primary key'])) !== FALSE) {
          unset($schema['primary key'][$key]);
        }
      }
      $sql_array[] = $this->createFieldSql($name, $this->processField($field));
    }

    // Process keys.
    if (!empty($schema['primary key'])) {
      $sql_array[] = " PRIMARY KEY (" . $this->createKeySql($schema['primary key']) . ")";
    }

    return implode(", \n", $sql_array);
  }

  /**
   * Build the SQL expression for keys.
   */
  protected function createKeySql($fields) {
    $return = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $return[] = $field[0];
      }
      else {
        $return[] = $field;
      }
    }
    return implode(', ', $return);
  }

  /**
   * Set database-engine specific properties for a field.
   *
   * @param $field
   *   A field description array, as specified in the schema documentation.
   */
  protected function processField($field) {
    if (!isset($field['size'])) {
      $field['size'] = 'normal';
    }
    // Set the correct database-engine specific datatype.
    if (!isset($field['sqlite_type'])) {
      $map = $this->getFieldTypeMap();
      $field['sqlite_type'] = $map[$field['type'] . ':' . $field['size']];
    }

    if ($field['type'] == 'serial') {
      $field['auto_increment'] = TRUE;
    }

    return $field;
  }

  /**
   * Create an SQL string for a field to be used in table creation or alteration.
   *
   * Before passing a field out of a schema definition into this function it has
   * to be processed by db_processField().
   *
   * @param $name
   *    Name of the field.
   * @param $spec
   *    The field specification, as per the schema data structure format.
   */
  protected function createFieldSql($name, $spec) {
    if (!empty($spec['auto_increment'])) {
      $sql = $name . " INTEGER PRIMARY KEY AUTOINCREMENT";
      if (!empty($spec['unsigned'])) {
        $sql .= ' CHECK (' . $name . '>= 0)';
      }
    }
    else {
      $sql = $name . ' ' . $spec['sqlite_type'];

      if (in_array($spec['type'], array('varchar', 'char', 'text')) && isset($spec['length'])) {
        $sql .= '(' . $spec['length'] . ')';
      }

      if (!empty($spec['not null'])) {
        $sql .= ' NOT NULL';
      }

      if (!empty($spec['unsigned'])) {
        $sql .= ' CHECK (' . $name . '>= 0)';
      }

      if (isset($spec['default'])) {
        if (is_string($spec['default'])) {
          $spec['default'] = "'" . $spec['default'] . "'";
        }
        $sql .= ' DEFAULT ' . $spec['default'];
      }

      if (empty($spec['not null']) && !isset($spec['default'])) {
        $sql .= ' DEFAULT NULL';
      }
    }
    return $sql;
  }

  /**
   * This maps a generic data type in combination with its data size
   * to the engine-specific data type.
   */
  public function getFieldTypeMap() {
    // Put :normal last so it gets preserved by array_flip. This makes
    // it much easier for modules (such as schema.module) to map
    // database types back into schema types.
    // $map does not use drupal_static as its value never changes.
    static $map = array(
      'varchar:normal'  => 'VARCHAR',

      'text:tiny'       => 'TEXT',
      'text:small'      => 'TEXT',
      'text:medium'     => 'TEXT',
      'text:big'        => 'TEXT',
      'text:normal'     => 'TEXT',

      'serial:tiny'     => 'INTEGER',
      'serial:small'    => 'INTEGER',
      'serial:medium'   => 'INTEGER',
      'serial:big'      => 'INTEGER',
      'serial:normal'   => 'INTEGER',

      'int:tiny'        => 'INTEGER',
      'int:small'       => 'INTEGER',
      'int:medium'      => 'INTEGER',
      'int:big'         => 'INTEGER',
      'int:normal'      => 'INTEGER',

      'float:tiny'      => 'FLOAT',
      'float:small'     => 'FLOAT',
      'float:medium'    => 'FLOAT',
      'float:big'       => 'FLOAT',
      'float:normal'    => 'FLOAT',

      'numeric:normal'  => 'NUMERIC',

      'blob:big'        => 'BLOB',
      'blob:normal'     => 'BLOB',

      'date:normal'     => 'DATE',

      'time:normal'   => 'TIME',

      'datetime:normal' => 'TIMESTAMP',
    );
    return $map;
  }

  public function renameTable($table, $new_name) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot rename %table to %table_new: table %table doesn't exist.", array('%table' => $table, '%table_new' => $new_name)));
    }
    if ($this->tableExists($new_name)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot rename %table to %table_new: table %table_new already exists.", array('%table' => $table, '%table_new' => $new_name)));
    }

    $schema = $this->introspectSchema($table);

    // SQLite doesn't allow you to rename tables outside of the current
    // database. So the syntax '...RENAME TO database.table' would fail.
    // So we must determine the full table name here rather than surrounding
    // the table with curly braces incase the db_prefix contains a reference
    // to a database outside of our existsing database.
    $info = $this->getPrefixInfo($new_name);
    $this->connection->query('ALTER TABLE {' . $table . '} RENAME TO ' . $info['table']);

    // Drop the indexes, there is no RENAME INDEX command in SQLite.
    if (!empty($schema['unique keys'])) {
      foreach ($schema['unique keys'] as $key => $fields) {
        $this->dropIndex($table, $key);
      }
    }
    if (!empty($schema['indexes'])) {
      foreach ($schema['indexes'] as $index => $fields) {
        $this->dropIndex($table, $index);
      }
    }

    // Recreate the indexes.
    $statements = $this->createIndexSql($new_name, $schema);
    foreach ($statements as $statement) {
      $this->connection->query($statement);
    }
  }

  public function dropTable($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }

    $this->connection->query('DROP TABLE {' . $table . '}');
    return TRUE;
  }

  public function addField($table, $field, $spec, $keys_new = array()) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add field %table.%field: table doesn't exist.", array('%field' => $field, '%table' => $table)));
    }
    if ($this->fieldExists($table, $field)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add field %table.%field: field already exists.", array('%field' => $field, '%table' => $table)));
    }

    // TODO: $keys_new is not supported yet.
    $query = 'ALTER TABLE {' . $table . '} ADD ';
    $query .= $this->createFieldSql($field, $this->processField($spec));
    $this->connection->query($query);
  }

  /**
   * Create a table with a new schema containing the old content.
   *
   * As SQLite does not support ALTER TABLE (with a few exceptions) it is
   * necessary to create a new table and copy over the old content.
   *
   * @param $table
   *   Name of the table to be altered.
   * @param $new_schema
   *   The new schema array for the table.
   */
  protected function alterTable($table, $new_schema) {
    $i = 0;
    do {
      $new_table = $table . '_' . $i++;
    } while ($this->tableExists($new_table));

    $this->createTable($new_table, $new_schema);

    $select = $this->connection->select($table)->fields($table, array_keys($new_schema['fields']));
    $this->connection->insert($new_table)
      ->from($select)
      ->execute();
    $old_count = $this->connection->query('SELECT COUNT(*) FROM {' . $table . '}')->fetchField();
    $new_count = $this->connection->query('SELECT COUNT(*) FROM {' . $new_table . '}')->fetchField();
    if ($old_count == $new_count) {
      do {
        $temp_table = $table . '_' . $i++;
      } while ($this->tableExists($temp_table));
      $this->renameTable($table, $temp_table);
      $this->renameTable($new_table, $table);
      $this->dropTable($temp_table);
    }
  }

  /**
   * Find out the schema of a table.
   *
   * This function uses introspection methods provided by the database to
   * create a schema array. This is useful, for example, during update when
   * the old schema is not available.
   *
   * @param $table
   *   Name of the table.
   * @return
   *   An array representing the schema, from drupal_get_schema().
   * @see drupal_get_schema()
   */
  protected function introspectSchema($table) {
    $mapped_fields = array_flip($this->getFieldTypeMap());
    $schema = array();
    $result = $this->connection->query("PRAGMA table_info('{" . $table . "}')");
    foreach ($result as $row) {
      if (preg_match('/^([^(]+)\((.*)\)$/', $row->type, $matches)) {
        $type = $matches[1];
        $length = $matches[2];
      }
      else {
        $type = $row->type;
        $length = NULL;
      }
      if (isset($mapped_fields[$type])) {
        list($type, $size) = explode(':', $mapped_fields[$type]);
        $schema['fields'][$row->name] = array(
          'type' => $type,
          'size' => $size,
          'not null' => !empty($row->notnull),
          'default' => trim($row->dflt_value, "'"),
        );
        if ($length) {
          $schema['fields'][$row->name]['length'] = $length;
        }
        if ($row->pk) {
          $schema['primary key'][] = $row->name;
        }
      }
      else {
        new Exception("Unable to parse the column type " . $row->type);
      }
    }
    $indexes = array();
    $result = $this->connection->query("PRAGMA index_list('{" . $table . "}')");
    foreach ($result as $row) {
      if (strpos($row->name, 'sqlite_autoindex_') !== 0) {
        $indexes[] = array(
          'schema_key' => $row->unique ? 'unique keys' : 'indexes',
          'name' => $row->name,
        );
      }
    }
    // Get length of table name and underscore.
    $n = strlen($this->connection->prefixTables('{' . $table . '}')) + 1;
    foreach ($indexes as $index) {
      $name = $index['name'];
      // Get index name without prefix.
      $index_name = substr($name, $n);
      $result = $this->connection->query("PRAGMA index_info('$name')");
      foreach ($result as $row) {
        $schema[$index['schema_key']][$index_name][] = $row->name;
      }
    }
    return $schema;
  }

  public function dropField($table, $field) {
    if ($this->fieldExists($table, $field)) {
      return FALSE;
    }

    $new_schema = $this->introspectSchema($table);
    unset($new_schema['fields'][$field]);
    foreach ($new_schema['indexes'] as $index => $fields) {
      foreach ($fields as $key => $field_name) {
        if ($field_name == $field) {
          unset($new_schema['indexes'][$index][$key]);
        }
      }
      // If this index has no more fields then remove it.
      if (empty($new_schema['indexes'][$index])) {
        unset($new_schema['indexes'][$index]);
      }
    }
    $this->alterTable($table, $new_schema);
    return TRUE;
  }

  public function changeField($table, $field, $field_new, $spec, $keys_new = array()) {
    if (!$this->fieldExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot change the definition of field %table.%name: field doesn't exist.", array('%table' => $table, '%name' => $field)));
    }
    if (($field != $field_new) && $this->fieldExists($table, $field_new)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot rename field %table.%name to %name_new: target field already exists.", array('%table' => $table, '%name' => $field, '%name_new' => $field_new)));
    }

    $new_schema = $this->introspectSchema($table);
    unset($new_schema['fields'][$field]);
    $new_schema['fields'][$field_new] = $spec;
    if (isset($keys_new['primary keys'])) {
      $new_schema['primary keys'] = $keys_new['primary keys'];
      $keys_new['primary keys'];
    }
    foreach (array('unique keys', 'indexes') as $k) {
      if (!empty($keys_new[$k])) {
        $new_schema[$k] = $keys_new[$k] + $new_schema[$k];
      }
    }
    $this->alterTable($table, $new_schema);
  }

  public function addIndex($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add index %name to table %table: table doesn't exist.", array('%table' => $table, '%name' => $name)));
    }
    if ($this->indexExists($table, $name)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add index %name to table %table: index already exists.", array('%table' => $table, '%name' => $name)));
    }

    $schema['indexes'][$name] = $fields;
    $statements = $this->createIndexSql($table, $schema);
    foreach ($statements as $statement) {
      $this->connection->query($statement);
    }
  }

  public function indexExists($table, $name) {
    return $this->connection->query('PRAGMA index_info({' . $table . '}_' . $name . ')')->fetchField() != '';
  }

  public function dropIndex($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('DROP INDEX ' . $this->prefixNonTable($table, $name));
    return TRUE;
  }

  public function addUniqueKey($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add unique key %name to table %table: table doesn't exist.", array('%table' => $table, '%name' => $name)));
    }
    if ($this->indexExists($table, $name)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add unique key %name to table %table: unique key already exists.", array('%table' => $table, '%name' => $name)));
    }

    $schema['unique keys'][$name] = $fields;
    $statements = $this->createIndexSql($table, $schema);
    foreach ($statements as $statement) {
      $this->connection->query($statement);
    }
  }

  public function dropUniqueKey($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('DROP INDEX ' . $this->prefixNonTable($table, $name));
    return TRUE;
  }

  public function addPrimaryKey($table, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add primary key to table %table: table doesn't exist.", array('%table' => $table)));
    }

    $new_schema = $this->introspectSchema($table);
    if (!empty($new_schema['primary key'])) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add primary key to table %table: primary key already exists.", array('%table' => $table)));
    }

    $new_schema['primary key'] = $fields;
    $this->alterTable($table, $new_schema);
  }

  public function dropPrimaryKey($table) {
    $new_schema = $this->introspectSchema($table);
    if (empty($new_schema['primary key'])) {
      return FALSE;
    }

    unset($new_schema['primary key']);
    $this->alterTable($table, $new_schema);
    return TRUE;
  }

  public function fieldSetDefault($table, $field, $default) {
    if (!$this->fieldExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot set default value of field %table.%field: field doesn't exist.", array('%table' => $table, '%field' => $field)));
    }

    $new_schema = $this->introspectSchema($table);
    $new_schema['fields'][$field]['default'] = $default;
    $this->alterTable($table, $new_schema);
  }

  public function fieldSetNoDefault($table, $field) {
    if (!$this->fieldExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot remove default value of field %table.%field: field doesn't exist.", array('%table' => $table, '%field' => $field)));
    }

    $new_schema = $this->introspectSchema($table);
    unset($new_schema['fields'][$field]['default']);
    $this->alterTable($table, $new_schema);
  }

  public function findTables($table_expression) {
    // Don't use {} around sqlite_master table.
    $result = db_query("SELECT name FROM sqlite_master WHERE name LIKE :table_name", array(
      ':table_name' => $table_expression,
    ));
    return $result->fetchAllKeyed(0, 0);
  }
}
