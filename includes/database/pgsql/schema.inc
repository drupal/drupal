<?php
// $Id$

/**
 * @file
 * Database schema code for PostgreSQL database servers.
 */

/**
 * @ingroup schemaapi
 * @{
 */

class DatabaseSchema_pgsql extends DatabaseSchema {

  /**
   * A cache of information about blob columns and sequences of tables.
   *
   * This is collected by DatabaseConnection_pgsql->queryTableInformation(),
   * by introspecting the database.
   *
   * @see DatabaseConnection_pgsql->queryTableInformation().
   * @var array
   */
  protected $tableInformation = array();

  /**
   * Fetch the list of blobs and sequences used on a table.
   *
   * We introspect the database to collect the information required by insert
   * and update queries.
   *
   * @param $table_name
   *   The non-prefixed name of the table.
   * @return
   *   An object with two member variables:
   *     - 'blob_fields' that lists all the blob fields in the table.
   *     - 'sequences' that lists the sequences used in that table.
   */
  public function queryTableInformation($table) {
    // Generate a key to reference this table's information on.
    $key = $this->connection->prefixTables('{' . $table . '}');
    if (!strpos($key, '.')) {
      $key = 'public.' . $key;
    }

    if (!isset($this->tableInformation[$key])) {
      // Split the key into schema and table for querying.
      list($schema,$table_name) = explode('.', $key);
      $table_information = (object) array(
        'blob_fields' => array(),
        'sequences' => array(),
      );
      $result = db_query("SELECT column_name, data_type, column_default FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table AND (data_type = 'bytea' OR (numeric_precision IS NOT NULL AND column_default LIKE :default))", array(':schema' => $schema, ':table' => $table_name, ':default' => '%nextval%'));
      foreach ($result as $column) {
        if ($column->data_type == 'bytea') {
          $table_information->blob_fields[$column->column_name] = TRUE;
        }
        else if (preg_match("/nextval\('([^']+)'/", $column->column_default, $matches)) {
          // We must know of any sequences in the table structure to help us
          // return the last insert id. If there is more than 1 sequences the
          // first one (index 0 of the sequences array) will be used.
          $table_information->sequences[] = $matches[1];
        }
      }
      $this->tableInformation[$key] = $table_information;
    }
    return $this->tableInformation[$key];
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
  protected function createTableSql($name, $table) {
    $sql_fields = array();
    foreach ($table['fields'] as $field_name => $field) {
      $sql_fields[] = $this->createFieldSql($field_name, $this->processField($field));
    }

    $sql_keys = array();
    if (isset($table['primary key']) && is_array($table['primary key'])) {
      $sql_keys[] = 'PRIMARY KEY (' . implode(', ', $table['primary key']) . ')';
    }
    if (isset($table['unique keys']) && is_array($table['unique keys'])) {
      foreach ($table['unique keys'] as $key_name => $key) {
        $sql_keys[] = 'CONSTRAINT {' . $name . '}_' . $key_name . '_key UNIQUE (' . implode(', ', $key) . ')';
      }
    }

    $sql = "CREATE TABLE {" . $name . "} (\n\t";
    $sql .= implode(",\n\t", $sql_fields);
    if (count($sql_keys) > 0) {
      $sql .= ",\n\t";
    }
    $sql .= implode(",\n\t", $sql_keys);
    $sql .= "\n)";
    $statements[] = $sql;

    if (isset($table['indexes']) && is_array($table['indexes'])) {
      foreach ($table['indexes'] as $key_name => $key) {
        $statements[] = $this->_createIndexSql($name, $key_name, $key);
      }
    }

    // Add table comment.
    if (!empty($table['description'])) {
      $statements[] = 'COMMENT ON TABLE {' . $name . '} IS ' . $this->prepareComment($table['description']);
    }

    // Add column comments.
    foreach ($table['fields'] as $field_name => $field) {
      if (!empty($field['description'])) {
        $statements[] = 'COMMENT ON COLUMN {' . $name . '}.' . $field_name . ' IS ' . $this->prepareComment($field['description']);
      }
    }

    return $statements;
  }

  /**
   * Create an SQL string for a field to be used in table creation or
   * alteration.
   *
   * Before passing a field out of a schema definition into this
   * function it has to be processed by _db_process_field().
   *
   * @param $name
   *    Name of the field.
   * @param $spec
   *    The field specification, as per the schema data structure format.
   */
  protected function createFieldSql($name, $spec) {
    $sql = $name . ' ' . $spec['pgsql_type'];

    if ($spec['type'] == 'serial') {
      unset($spec['not null']);
    }
    if (!empty($spec['unsigned'])) {
      $sql .= " CHECK ($name >= 0)";
    }

    if (!empty($spec['length'])) {
      $sql .= '(' . $spec['length'] . ')';
    }
    elseif (isset($spec['precision']) && isset($spec['scale'])) {
      $sql .= '(' . $spec['precision'] . ', ' . $spec['scale'] . ')';
    }

    if (isset($spec['not null']) && $spec['not null']) {
      $sql .= ' NOT NULL';
    }
    if (isset($spec['default'])) {
      $default = is_string($spec['default']) ? "'" . $spec['default'] . "'" : $spec['default'];
      $sql .= " default $default";
    }

    return $sql;
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
    if (!isset($field['pgsql_type'])) {
      $map = $this->getFieldTypeMap();
      $field['pgsql_type'] = $map[$field['type'] . ':' . $field['size']];
    }
    if (!empty($field['unsigned'])) {
      // Unsigned datatypes are not supported in PostgreSQL 8.3. In MySQL,
      // they are used to ensure a positive number is inserted and it also
      // doubles the maximum integer size that can be stored in a field.
      // The PostgreSQL schema in Drupal creates a check constraint
      // to ensure that a value inserted is >= 0. To provide the extra
      // integer capacity, here, we bump up the column field size.
      if (!isset($map)) {
        $map = $this->getFieldTypeMap();
      }
      switch ($field['pgsql_type']) {
        case 'smallint':
          $field['pgsql_type'] = $map['int:medium'];
          break;
        case 'int' :
          $field['pgsql_type'] = $map['int:big'];
          break;
      }
    }
    if ($field['type'] == 'serial') {
      unset($field['not null']);
    }
    return $field;
  }


  /**
   * This maps a generic data type in combination with its data size
   * to the engine-specific data type.
   */
  function getFieldTypeMap() {
    // Put :normal last so it gets preserved by array_flip. This makes
    // it much easier for modules (such as schema.module) to map
    // database types back into schema types.
    $map = array(
      'varchar:normal' => 'varchar',
      'char:normal' => 'character',

      'text:tiny' => 'text',
      'text:small' => 'text',
      'text:medium' => 'text',
      'text:big' => 'text',
      'text:normal' => 'text',

      'int:tiny' => 'smallint',
      'int:small' => 'smallint',
      'int:medium' => 'int',
      'int:big' => 'bigint',
      'int:normal' => 'int',

      'float:tiny' => 'real',
      'float:small' => 'real',
      'float:medium' => 'real',
      'float:big' => 'double precision',
      'float:normal' => 'real',

      'numeric:normal' => 'numeric',

      'blob:big' => 'bytea',
      'blob:normal' => 'bytea',

      'datetime:normal' => 'timestamp without time zone',

      'serial:tiny' => 'serial',
      'serial:small' => 'serial',
      'serial:medium' => 'serial',
      'serial:big' => 'bigserial',
      'serial:normal' => 'serial',
      );
    return $map;
  }

  protected function _createKeySql($fields) {
    $ret = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $ret[] = 'substr(' . $field[0] . ', 1, ' . $field[1] . ')';
      }
      else {
        $ret[] = $field;
      }
    }
    return implode(', ', $ret);
  }

  /**
   * Rename a table.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be renamed.
   * @param $new_name
   *   The new name for the table.
   */
  function renameTable(&$ret, $table, $new_name) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} RENAME TO {' . $new_name . '}');
  }

  /**
   * Drop a table.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be dropped.
   */
  public function dropTable(&$ret, $table) {
    $ret[] = update_sql('DROP TABLE {' . $table . '}');
  }

  /**
   * Add a new field to a table.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   Name of the table to be altered.
   * @param $field
   *   Name of the field to be added.
   * @param $spec
   *   The field specification array, as taken from a schema definition.
   *   The specification may also contain the key 'initial', the newly
   *   created field will be set to the value of the key in all rows.
   *   This is most useful for creating NOT NULL columns with no default
   *   value in existing tables.
   * @param $keys_new
   *   Optional keys and indexes specification to be created on the
   *   table along with adding the field. The format is the same as a
   *   table specification but without the 'fields' element. If you are
   *   adding a type 'serial' field, you MUST specify at least one key
   *   or index including it in this array. @see db_change_field for more
   *   explanation why.
   */
  public function addField(&$ret, $table, $field, $spec, $new_keys = array()) {
    $fixnull = FALSE;
    if (!empty($spec['not null']) && !isset($spec['default'])) {
      $fixnull = TRUE;
      $spec['not null'] = FALSE;
    }
    $query = 'ALTER TABLE {' . $table . '} ADD COLUMN ';
    $query .= $this->createFieldSql($field, $this->processField($spec));
    $ret[] = update_sql($query);
    if (isset($spec['initial'])) {
      // All this because update_sql does not support %-placeholders.
      $sql = 'UPDATE {' . $table . '} SET ' . $field . ' = ' . db_type_placeholder($spec['type']);
      $result = db_query($sql, $spec['initial']);
      $ret[] = array('success' => $result !== FALSE, 'query' => check_plain($sql . ' (' . $spec['initial'] . ')'));
    }
    if ($fixnull) {
      $ret[] = update_sql("ALTER TABLE {" . $table . "} ALTER $field SET NOT NULL");
    }
    if (isset($new_keys)) {
      $this->_createKeys($ret, $table, $new_keys);
    }
    // Add column comment.
    if (!empty($spec['description'])) {
      $ret[] = update_sql('COMMENT ON COLUMN {' . $table . '}.' . $field . ' IS ' . $this->prepareComment($spec['description']));
    }
  }

  /**
   * Drop a field.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   * @param $field
   *   The field to be dropped.
   */
  public function dropField(&$ret, $table, $field) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} DROP COLUMN ' . $field);
  }

  /**
   * Set the default value for a field.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   * @param $field
   *   The field to be altered.
   * @param $default
   *   Default value to be set. NULL for 'default NULL'.
   */
  public function fieldSetDefault(&$ret, $table, $field, $default) {
    if (is_null($default)) {
      $default = 'NULL';
    }
    else {
      $default = is_string($default) ? "'$default'" : $default;
    }

    $ret[] = update_sql('ALTER TABLE {' . $table . '} ALTER COLUMN ' . $field . ' SET DEFAULT ' . $default);
  }

  /**
   * Set a field to have no default value.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   * @param $field
   *   The field to be altered.
   */
  public function fieldSetNoDefault(&$ret, $table, $field) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} ALTER COLUMN ' . $field . ' DROP DEFAULT');
  }

  /**
   * Add a primary key.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   * @param $fields
   *   Fields for the primary key.
   */
  public function addPrimaryKey(&$ret, $table, $fields) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} ADD PRIMARY KEY (' . implode(',', $fields) . ')');
  }

  /**
   * Drop the primary key.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   */
  public function dropPrimaryKey(&$ret, $table) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} DROP CONSTRAINT {' . $table . '}_pkey');
  }

  /**
   * Add a unique key.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the key.
   * @param $fields
   *   An array of field names.
   */
  function addUniqueKey(&$ret, $table, $name, $fields) {
    $name = '{' . $table . '}_' . $name . '_key';
    $ret[] = update_sql('ALTER TABLE {' . $table . '} ADD CONSTRAINT ' . $name . ' UNIQUE (' . implode(',', $fields) . ')');
  }

  /**
   * Drop a unique key.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the key.
   */
  public function dropUniqueKey(&$ret, $table, $name) {
    $name = '{' . $table . '}_' . $name . '_key';
    $ret[] = update_sql('ALTER TABLE {' . $table . '} DROP CONSTRAINT ' . $name);
  }

  /**
   * Add an index.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the index.
   * @param $fields
   *   An array of field names.
   */
  public function addIndex(&$ret, $table, $name, $fields) {
    $ret[] = update_sql($this->_createIndexSql($table, $name, $fields));
  }

  /**
   * Drop an index.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the index.
   */
  public function dropIndex(&$ret, $table, $name) {
    $name = '{' . $table . '}_' . $name . '_idx';
    $ret[] = update_sql('DROP INDEX ' . $name);
  }

  /**
   * Change a field definition.
   *
   * IMPORTANT NOTE: To maintain database portability, you have to explicitly
   * recreate all indices and primary keys that are using the changed field.
   *
   * That means that you have to drop all affected keys and indexes with
   * db_drop_{primary_key,unique_key,index}() before calling db_change_field().
   * To recreate the keys and indices, pass the key definitions as the
   * optional $new_keys argument directly to db_change_field().
   *
   * For example, suppose you have:
   * @code
   * $schema['foo'] = array(
   *   'fields' => array(
   *     'bar' => array('type' => 'int', 'not null' => TRUE)
   *   ),
   *   'primary key' => array('bar')
   * );
   * @endcode
   * and you want to change foo.bar to be type serial, leaving it as the
   * primary key. The correct sequence is:
   * @code
   * db_drop_primary_key($ret, 'foo');
   * db_change_field($ret, 'foo', 'bar', 'bar',
   *   array('type' => 'serial', 'not null' => TRUE),
   *   array('primary key' => array('bar')));
   * @endcode
   *
   * The reasons for this are due to the different database engines:
   *
   * On PostgreSQL, changing a field definition involves adding a new field
   * and dropping an old one which* causes any indices, primary keys and
   * sequences (from serial-type fields) that use the changed field to be dropped.
   *
   * On MySQL, all type 'serial' fields must be part of at least one key
   * or index as soon as they are created. You cannot use
   * db_add_{primary_key,unique_key,index}() for this purpose because
   * the ALTER TABLE command will fail to add the column without a key
   * or index specification. The solution is to use the optional
   * $new_keys argument to create the key or index at the same time as
   * field.
   *
   * You could use db_add_{primary_key,unique_key,index}() in all cases
   * unless you are converting a field to be type serial. You can use
   * the $new_keys argument in all cases.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   Name of the table.
   * @param $field
   *   Name of the field to change.
   * @param $field_new
   *   New name for the field (set to the same as $field if you don't want to change the name).
   * @param $spec
   *   The field specification for the new field.
   * @param $new_keys
   *   Optional keys and indexes specification to be created on the
   *   table along with changing the field. The format is the same as a
   *   table specification but without the 'fields' element.
   */
  public function changeField(&$ret, $table, $field, $field_new, $spec, $new_keys = array()) {
    $ret[] = update_sql("ALTER TABLE {" . $table . "} RENAME $field TO " . $field . "_old");
    $not_null = isset($spec['not null']) ? $spec['not null'] : FALSE;
    unset($spec['not null']);

    if (!array_key_exists('size', $spec)) {
      $spec['size'] = 'normal';
    }
    $this->addField($ret, $table, "$field_new", $spec);

    // We need to typecast the new column to best be able to transfer the data
    // Schema_pgsql::getFieldTypeMap() will return possibilities that are not
    // 'cast-able' such as 'serial' - so they need to be casted int instead.
    $map = $this->getFieldTypeMap();
    $typecast = $map[$spec['type'] . ':' . $spec['size']];
    if (in_array($typecast, array('serial', 'bigserial', 'numeric'))) {
      $typecast = 'int';
    }
    $ret[] = update_sql("UPDATE {" . $table . "} SET $field_new = CAST(" . $field . "_old as " . $typecast . ")");

    if ($not_null) {
      $ret[] = update_sql("ALTER TABLE {" . $table . "} ALTER $field_new SET NOT NULL");
    }

    $this->dropField($ret, $table, $field . '_old');

    if (isset($new_keys)) {
      $this->_createKeys($ret, $table, $new_keys);
    }
  }

  protected function _createIndexSql($table, $name, $fields) {
    $query = 'CREATE INDEX {' . $table . '}_' . $name . '_idx ON {' . $table . '} (';
    $query .= $this->_createKeySql($fields) . ')';
    return $query;
  }

  protected function _createKeys(&$ret, $table, $new_keys) {
    if (isset($new_keys['primary key'])) {
      $this->addPrimaryKey($ret, $table, $new_keys['primary key']);
    }
    if (isset($new_keys['unique keys'])) {
      foreach ($new_keys['unique keys'] as $name => $fields) {
        $this->addUniqueKey($ret, $table, $name, $fields);
      }
    }
    if (isset($new_keys['indexes'])) {
      foreach ($new_keys['indexes'] as $name => $fields) {
        $this->addIndex($ret, $table, $name, $fields);
      }
    }
  }

  /**
   * Retrieve a table or column comment.
   */
  public function getComment($table, $column = NULL) {
    $table = $this->connection->prefixTables('{' . $table . '}');
    if (isset($column)) {
      return db_query('SELECT col_description(oid, attnum) FROM pg_class, pg_attribute WHERE attrelid = oid AND relname = ? AND attname = ?', array($table, $column))->fetchField();
    }
    return db_query('SELECT obj_description(oid, ?) FROM pg_class WHERE relname = ?', array('pg_class', $table))->fetchField();
  }
}
