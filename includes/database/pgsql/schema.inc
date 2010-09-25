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
   * @see DatabaseConnection_pgsql->queryTableInformation()
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
      list($schema, $table_name) = explode('.', $key);
      $table_information = (object) array(
        'blob_fields' => array(),
        'sequences' => array(),
      );
      // Don't use {} around information_schema.columns table.
      $result = $this->connection->query("SELECT column_name, data_type, column_default FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table AND (data_type = 'bytea' OR (numeric_precision IS NOT NULL AND column_default LIKE :default))", array(
        ':schema' => $schema,
        ':table' => $table_name,
        ':default' => '%nextval%',
      ));
      foreach ($result as $column) {
        if ($column->data_type == 'bytea') {
          $table_information->blob_fields[$column->column_name] = TRUE;
        }
        elseif (preg_match("/nextval\('([^']+)'/", $column->column_default, $matches)) {
          // We must know of any sequences in the table structure to help us
          // return the last insert id. If there is more than 1 sequences the
          // first one (index 0 of the sequences array) will be used.
          $table_information->sequences[] = $matches[1];
          $table_information->serial_fields[] = $column->column_name;
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
        $sql_keys[] = 'CONSTRAINT ' . $this->prefixNonTable($name, $key_name, 'key') . ' UNIQUE (' . implode(', ', $key) . ')';
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

    if (in_array($spec['type'], array('varchar', 'char', 'text')) && isset($spec['length'])) {
      $sql .= '(' . $spec['length'] . ')';
    }
    elseif (isset($spec['precision']) && isset($spec['scale'])) {
      $sql .= '(' . $spec['precision'] . ', ' . $spec['scale'] . ')';
    }

    if (!empty($spec['unsigned'])) {
      $sql .= " CHECK ($name >= 0)";
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
    // $map does not use drupal_static as its value never changes.
    static $map = array(
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

      'serial:tiny' => 'serial',
      'serial:small' => 'serial',
      'serial:medium' => 'serial',
      'serial:big' => 'bigserial',
      'serial:normal' => 'serial',
      );
    return $map;
  }

  protected function _createKeySql($fields) {
    $return = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $return[] = 'substr(' . $field[0] . ', 1, ' . $field[1] . ')';
      }
      else {
        $return[] = '"' . $field . '"';
      }
    }
    return implode(', ', $return);
  }

  function renameTable($table, $new_name) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot rename %table to %table_new: table %table doesn't exist.", array('%table' => $table, '%table_new' => $new_name)));
    }
    if ($this->tableExists($new_name)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot rename %table to %table_new: table %table_new already exists.", array('%table' => $table, '%table_new' => $new_name)));
    }

    // Get the schema and tablename for the old table.
    $old_full_name = $this->connection->prefixTables('{' . $table . '}');
    list($old_schema, $old_table_name) = strpos($old_full_name, '.') ? explode('.', $old_full_name) : array('public', $old_full_name);

    // Index names and constraint names are global in PostgreSQL, so we need to
    // rename them when renaming the table.
    $indexes = $this->connection->query('SELECT indexname FROM pg_indexes WHERE schemaname = :schema AND tablename = :table', array(':schema' => $old_schema, ':table' => $old_table_name));
    foreach ($indexes as $index) {
      if (preg_match('/^' . preg_quote($old_full_name) . '_(.*)_idx$/', $index->indexname, $matches)) {
        $index_name = $matches[1];
        $this->connection->query('ALTER INDEX ' . $index->indexname . ' RENAME TO {' . $new_name . '}_' . $index_name . '_idx');
      }
    }

    // Now rename the table.
    // Ensure the new table name does not include schema syntax.
    $prefixInfo = $this->getPrefixInfo($new_name);
    $this->connection->query('ALTER TABLE {' . $table . '} RENAME TO ' . $prefixInfo['table']);
  }

  public function dropTable($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }

    $this->connection->query('DROP TABLE {' . $table . '}');
    return TRUE;
  }

  public function addField($table, $field, $spec, $new_keys = array()) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add field %table.%field: table doesn't exist.", array('%field' => $field, '%table' => $table)));
    }
    if ($this->fieldExists($table, $field)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add field %table.%field: field already exists.", array('%field' => $field, '%table' => $table)));
    }

    $fixnull = FALSE;
    if (!empty($spec['not null']) && !isset($spec['default'])) {
      $fixnull = TRUE;
      $spec['not null'] = FALSE;
    }
    $query = 'ALTER TABLE {' . $table . '} ADD COLUMN ';
    $query .= $this->createFieldSql($field, $this->processField($spec));
    $this->connection->query($query);
    if (isset($spec['initial'])) {
      $this->connection->update($table)
        ->fields(array($field, $spec['initial']))
        ->execute();
    }
    if ($fixnull) {
      $this->connection->query("ALTER TABLE {" . $table . "} ALTER $field SET NOT NULL");
    }
    if (isset($new_keys)) {
      $this->_createKeys($table, $new_keys);
    }
    // Add column comment.
    if (!empty($spec['description'])) {
      $this->connection->query('COMMENT ON COLUMN {' . $table . '}.' . $field . ' IS ' . $this->prepareComment($spec['description']));
    }
  }

  public function dropField($table, $field) {
    if (!$this->fieldExists($table, $field)) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP COLUMN "' . $field . '"');
    return TRUE;
  }

  public function fieldSetDefault($table, $field, $default) {
    if (!$this->fieldExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot set default value of field %table.%field: field doesn't exist.", array('%table' => $table, '%field' => $field)));
    }

    if (!isset($default)) {
      $default = 'NULL';
    }
    else {
      $default = is_string($default) ? "'$default'" : $default;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN "' . $field . '" SET DEFAULT ' . $default);
  }

  public function fieldSetNoDefault($table, $field) {
    if (!$this->fieldExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot remove default value of field %table.%field: field doesn't exist.", array('%table' => $table, '%field' => $field)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN "' . $field . '" DROP DEFAULT');
  }

  public function indexExists($table, $name) {
    // Details http://www.postgresql.org/docs/8.3/interactive/view-pg-indexes.html
    $index_name = '{' . $table . '}_' . $name . '_idx';
    return (bool) $this->connection->query("SELECT 1 FROM pg_indexes WHERE indexname = '$index_name'")->fetchField();
  }

  /**
   * Helper function: check if a constraint (PK, FK, UK) exists.
   *
   * @param $table
   *   The name of the table.
   * @param $name
   *   The name of the constraint (typically 'pkey' or '[constraint]_key').
   */
  protected function constraintExists($table, $name) {
    $constraint_name = '{' . $table . '}_' . $name;
    return (bool) $this->connection->query("SELECT 1 FROM pg_constraint WHERE conname = '$constraint_name'")->fetchField();
  }

  public function addPrimaryKey($table, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add primary key to table %table: table doesn't exist.", array('%table' => $table)));
    }
    if ($this->constraintExists($table, 'pkey')) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add primary key to table %table: primary key already exists.", array('%table' => $table)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD PRIMARY KEY (' . implode(',', $fields) . ')');
  }

  public function dropPrimaryKey($table) {
    if (!$this->constraintExists($table, 'pkey')) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP CONSTRAINT ' . $this->prefixNonTable($table, 'pkey'));
    return TRUE;
  }

  function addUniqueKey($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add unique key %name to table %table: table doesn't exist.", array('%table' => $table, '%name' => $name)));
    }
    if ($this->constraintExists($table, $name . '_key')) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add unique key %name to table %table: unique key already exists.", array('%table' => $table, '%name' => $name)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD CONSTRAINT "' . $this->prefixNonTable($table, $name, 'key') . '" UNIQUE (' . implode(',', $fields) . ')');
  }

  public function dropUniqueKey($table, $name) {
    if (!$this->constraintExists($table, $name . '_key')) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP CONSTRAINT "' . $this->prefixNonTable($table, $name, 'key') . '"');
    return TRUE;
  }

  public function addIndex($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add index %name to table %table: table doesn't exist.", array('%table' => $table, '%name' => $name)));
    }
    if ($this->indexExists($table, $name)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add index %name to table %table: index already exists.", array('%table' => $table, '%name' => $name)));
    }

    $this->connection->query($this->_createIndexSql($table, $name, $fields));
  }

  public function dropIndex($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('DROP INDEX ' . $this->prefixNonTable($table, $name, 'idx'));
    return TRUE;
  }

  public function changeField($table, $field, $field_new, $spec, $new_keys = array()) {
    if (!$this->fieldExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot change the definition of field %table.%name: field doesn't exist.", array('%table' => $table, '%name' => $field)));
    }
    if (($field != $field_new) && $this->fieldExists($table, $field_new)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot rename field %table.%name to %name_new: target field already exists.", array('%table' => $table, '%name' => $field, '%name_new' => $field_new)));
    }

    if (!array_key_exists('size', $spec)) {
      $spec['size'] = 'normal';
    }

    // We need to typecast the new column to best be able to transfer the data
    // Schema_pgsql::getFieldTypeMap() will return possibilities that are not
    // 'cast-able' such as 'serial' - so they need to be casted int instead.
    $map = $this->getFieldTypeMap();
    $typecast = $map[$spec['type'] . ':' . $spec['size']];
    if (in_array($typecast, array('serial', 'bigserial', 'numeric'))) {
      $typecast = 'int';
    }
    $this->connection->query('ALTER TABLE {' . $table . '} ALTER "' . $field . '" TYPE ' . $typecast . ' USING "' . $field . '"::' . $typecast);

    // Map type definition to the PostgreSQL type.
    $pgsql_type = $map[$spec['type'] . ':' . $spec['size']];
    if (in_array($pgsql_type, array('serial', 'bigserial'))) {
      // Type "serial" is known to PostgreSQL, but *only* during table creation,
      // not when altering. Because of that, the sequence needs to be created
      // and initialized by hand.
      $seq = "{" . $table . "}_" . $field_new . "_seq";
      $this->connection->query("CREATE SEQUENCE " . $seq);
      // Set sequence to maximal field value to not conflict with existing
      // entries.
      $this->connection->query("SELECT setval('" . $seq . "', MAX(\"" . $field . '")) FROM {' . $table . "}");
      $this->connection->query('ALTER TABLE {' . $table . '} ALTER "' . $field . '" SET DEFAULT nextval(\'' . $seq . '\')');
    }

    // Rename the column if necessary.
    if ($field != $field_new) {
      $this->connection->query('ALTER TABLE {' . $table . '} RENAME "' . $field . '" TO "' . $field_new . '"');
    }

    // Change description if necessary.
    if (!empty($spec['description'])) {
      $this->connection->query('COMMENT ON COLUMN {' . $table . '}."' . $field_new . '" IS ' . $this->prepareComment($spec['description']));
    }

    if (isset($new_keys)) {
      $this->_createKeys($table, $new_keys);
    }
  }

  protected function _createIndexSql($table, $name, $fields) {
    $query = 'CREATE INDEX "' . $this->prefixNonTable($table, $name, 'idx') . '" ON {' . $table . '} (';
    $query .= $this->_createKeySql($fields) . ')';
    return $query;
  }

  protected function _createKeys($table, $new_keys) {
    if (isset($new_keys['primary key'])) {
      $this->addPrimaryKey($table, $new_keys['primary key']);
    }
    if (isset($new_keys['unique keys'])) {
      foreach ($new_keys['unique keys'] as $name => $fields) {
        $this->addUniqueKey($table, $name, $fields);
      }
    }
    if (isset($new_keys['indexes'])) {
      foreach ($new_keys['indexes'] as $name => $fields) {
        $this->addIndex($table, $name, $fields);
      }
    }
  }

  /**
   * Retrieve a table or column comment.
   */
  public function getComment($table, $column = NULL) {
    $info = $this->getPrefixInfo($table);
    // Don't use {} around pg_class, pg_attribute tables.
    if (isset($column)) {
      return $this->connection->query('SELECT col_description(oid, attnum) FROM pg_class, pg_attribute WHERE attrelid = oid AND relname = ? AND attname = ?', array($info['table'], $column))->fetchField();
    }
    else {
      return $this->connection->query('SELECT obj_description(oid, ?) FROM pg_class WHERE relname = ?', array('pg_class', $info['table']))->fetchField();
    }
  }
}
