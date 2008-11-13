<?php
// $Id$

/**
 * @file
 * Database schema code for MySQL database servers.
 */


/**
 * @ingroup schemaapi
 * @{
 */

class DatabaseSchema_mysql extends DatabaseSchema {

  public function tableExists($table) {
    return (bool) $this->connection->query("SHOW TABLES LIKE '{" . $table . "}'", array(), array())->fetchField();
  }

  public function columnExists($table, $column) {
    return (bool) $this->connection->query("SHOW COLUMNS FROM {" . $this->connection->escapeTable($table) . "} LIKE '" . $this->connection->escapeTable($column) . "'", array(), array())->fetchField();
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
    if (empty($table['mysql_suffix'])) {
      $table['mysql_suffix'] = "/*!40100 DEFAULT CHARACTER SET UTF8 */";
    }

    $sql = "CREATE TABLE {" . $name . "} (\n";

    // Add the SQL statement for each field.
    foreach ($table['fields'] as $field_name => $field) {
      $sql .= $this->createFieldSql($field_name, $this->processField($field)) . ", \n";
    }

    // Process keys & indexes.
    $keys = $this->createKeysSql($table);
    if (count($keys)) {
      $sql .= implode(", \n", $keys) . ", \n";
    }

    // Remove the last comma and space.
    $sql = substr($sql, 0, -3) . "\n) ";

    $sql .= $table['mysql_suffix'];

    return array($sql);
  }

  /**
   * Create an SQL string for a field to be used in table creation or alteration.
   *
   * Before passing a field out of a schema definition into this function it has
   * to be processed by _db_process_field().
   *
   * @param $name
   *   Name of the field.
   * @param $spec
   *   The field specification, as per the schema data structure format.
   */
  protected function createFieldSql($name, $spec) {
    $sql = "`" . $name . "` " . $spec['mysql_type'];

    if (isset($spec['length'])) {
      $sql .= '(' . $spec['length'] . ')';
    }
    elseif (isset($spec['precision']) && isset($spec['scale'])) {
      $sql .= '(' . $spec['precision'] . ', ' . $spec['scale'] . ')';
    }

    if (!empty($spec['unsigned'])) {
      $sql .= ' unsigned';
    }

    if (!empty($spec['not null'])) {
      $sql .= ' NOT NULL';
    }

    if (!empty($spec['auto_increment'])) {
      $sql .= ' auto_increment';
    }

    // $spec['default'] can be NULL, so we explicitely check for the key here.
    if (array_key_exists('default', $spec)) {
      if (is_string($spec['default'])) {
        $spec['default'] = "'" . $spec['default'] . "'";
      }
      elseif (is_null($spec['default'])) {
        $spec['default'] = 'NULL';
      }
      $sql .= ' DEFAULT ' . $spec['default'];
    }

    if (empty($spec['not null']) && !isset($spec['default'])) {
      $sql .= ' DEFAULT NULL';
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
    if (!isset($field['mysql_type'])) {
      $map = db_type_map();
      $field['mysql_type'] = $map[$field['type'] . ':' . $field['size']];
    }

    if ($field['type'] == 'serial') {
      $field['auto_increment'] = TRUE;
    }

    return $field;
  }

  public function getFieldTypeMap() {
    // Put :normal last so it gets preserved by array_flip.  This makes
    // it much easier for modules (such as schema.module) to map
    // database types back into schema types.
    static $map = array(
      'varchar:normal'  => 'VARCHAR',
      'char:normal'     => 'CHAR',

      'text:tiny'       => 'TINYTEXT',
      'text:small'      => 'TINYTEXT',
      'text:medium'     => 'MEDIUMTEXT',
      'text:big'        => 'LONGTEXT',
      'text:normal'     => 'TEXT',

      'serial:tiny'     => 'TINYINT',
      'serial:small'    => 'SMALLINT',
      'serial:medium'   => 'MEDIUMINT',
      'serial:big'      => 'BIGINT',
      'serial:normal'   => 'INT',

      'int:tiny'        => 'TINYINT',
      'int:small'       => 'SMALLINT',
      'int:medium'      => 'MEDIUMINT',
      'int:big'         => 'BIGINT',
      'int:normal'      => 'INT',

      'float:tiny'      => 'FLOAT',
      'float:small'     => 'FLOAT',
      'float:medium'    => 'FLOAT',
      'float:big'       => 'DOUBLE',
      'float:normal'    => 'FLOAT',

      'numeric:normal'  => 'DECIMAL',

      'blob:big'        => 'LONGBLOB',
      'blob:normal'     => 'BLOB',

      'datetime:normal' => 'DATETIME',
    );
    return $map;
  }




  protected function createKeysSql($spec) {
    $keys = array();

    if (!empty($spec['primary key'])) {
      $keys[] = 'PRIMARY KEY (' . $this->createKeysSqlHelper($spec['primary key']) . ')';
    }
    if (!empty($spec['unique keys'])) {
      foreach ($spec['unique keys'] as $key => $fields) {
        $keys[] = 'UNIQUE KEY ' . $key .' ('. $this->createKeysSqlHelper($fields) . ')';
      }
    }
    if (!empty($spec['indexes'])) {
      foreach ($spec['indexes'] as $index => $fields) {
        $keys[] = 'INDEX ' . $index . ' (' . $this->createKeysSqlHelper($fields) . ')';
      }
    }

    return $keys;
  }

  protected function createKeySql($fields) {
    $ret = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $ret[] = $field[0] . '(' . $field[1] . ')';
      }
      else {
        $ret[] = $field;
      }
    }
    return implode(', ', $ret);
  }

  protected function createKeysSqlHelper($fields) {
    $ret = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $ret[] = $field[0] . '(' . $field[1] . ')';
      }
      else {
        $ret[] = $field;
      }
    }
    return implode(', ', $ret);
  }

  public function renameTable(&$ret, $table, $new_name) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} RENAME TO {' . $new_name . '}');
  }

  public function dropTable(&$ret, $table) {
    $ret[] = update_sql('DROP TABLE {' . $table . '}');
  }

  public function addField(&$ret, $table, $field, $spec, $keys_new = array()) {
    $fixnull = FALSE;
    if (!empty($spec['not null']) && !isset($spec['default'])) {
      $fixnull = TRUE;
      $spec['not null'] = FALSE;
    }
    $query = 'ALTER TABLE {' . $table . '} ADD ';
    $query .= $this->createFieldSql($field, $this->processField($spec));
    if (count($keys_new)) {
      $query .= ', ADD ' . implode(', ADD ', $this->createKeysSql($keys_new));
    }
    $ret[] = update_sql($query);
    if (isset($spec['initial'])) {
      // All this because update_sql does not support %-placeholders.
      $sql = 'UPDATE {' . $table . '} SET ' . $field . ' = ' . db_type_placeholder($spec['type']);
      $result = db_query($sql, $spec['initial']);
      $ret[] = array('success' => $result !== FALSE, 'query' => check_plain($sql . ' (' . $spec['initial'] . ')'));
    }
    if ($fixnull) {
      $spec['not null'] = TRUE;
      $this->changeField($ret, $table, $field, $field, $spec);
    }
  }

  public function dropField(&$ret, $table, $field) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} DROP ' . $field);
  }

  public function fieldSetDefault(&$ret, $table, $field, $default) {
    if (is_null($default)) {
      $default = 'NULL';
    }
    else {
      $default = is_string($default) ? "'$default'" : $default;
    }

    $ret[] = update_sql('ALTER TABLE {' . $table . '} ALTER COLUMN ' . $field . ' SET DEFAULT ' . $default);
  }

  public function fieldSetNoDefault(&$ret, $table, $field) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} ALTER COLUMN ' . $field . ' DROP DEFAULT');
  }

  public function addPrimaryKey(&$ret, $table, $fields) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} ADD PRIMARY KEY (' . $this->createKeySql($fields) . ')');
  }

  public function dropPrimaryKey(&$ret, $table) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} DROP PRIMARY KEY');
  }

  public function addUniqueKey(&$ret, $table, $name, $fields) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} ADD UNIQUE KEY ' . $name . ' (' . $this->createKeySql($fields) . ')');
  }

  public function dropUniqueKey(&$ret, $table, $name) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} DROP KEY ' . $name);
  }

  public function addIndex(&$ret, $table, $name, $fields) {
    $query = 'ALTER TABLE {' . $table . '} ADD INDEX ' . $name . ' (' . $this->createKeySql($fields) . ')';
    $ret[] = update_sql($query);
  }

  public function dropIndex(&$ret, $table, $name) {
    $ret[] = update_sql('ALTER TABLE {' . $table . '} DROP INDEX ' . $name);
  }

  public function changeField(&$ret, $table, $field, $field_new, $spec, $keys_new = array()) {
    $sql = 'ALTER TABLE {' . $table . '} CHANGE `' . $field . '` ' . $this->createFieldSql($field_new, $this->processField($spec));
    if (count($keys_new)) {
      $sql .= ', ADD ' . implode(', ADD ', $this->createKeysSql($keys_new));
    }
    $ret[] = update_sql($sql);
  }

}

/**
 * @} End of "ingroup schemaapi".
 */
