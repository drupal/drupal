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

  /**
   * Maximum length of a table comment in MySQL.
   */
  const COMMENT_MAX_TABLE = 60;

  /**
   * Maximum length of a column comment in MySQL.
   */
  const COMMENT_MAX_COLUMN = 255;

  /**
   * Build a condition to match a table name against a standard information_schema.
   *
   * MySQL uses databases like schemas rather than catalogs so when we build
   * a condition to query the information_schema.tables, we set the default
   * database as the schema unless specified otherwise, and exclude table_catalog
   * from the condition criteria.
   */
  protected function buildTableNameCondition($table_name, $operator = '=') {
    $info = Database::getConnectionInfo();

    if (strpos($table_name, '.')) {
      list($schema, $table_name) = explode('.', $table_name);
    }
    else {
      $schema = $info['default']['database'];
    }

    $condition = new DatabaseCondition('AND');
    $condition->condition('table_schema', $schema);
    $condition->condition('table_name', $table_name, $operator);
    return $condition;
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
    // Provide some defaults if needed
    $table += array(
      'mysql_engine' => 'InnoDB',
      'mysql_character_set' => 'UTF8',
    );

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

    $sql .= 'ENGINE = ' . $table['mysql_engine'] . ' DEFAULT CHARACTER SET ' . $table['mysql_character_set'];

    // Add table comment.
    if (!empty($table['description'])) {
      $sql .= ' COMMENT ' . $this->prepareComment($table['description'], self::COMMENT_MAX_TABLE);
    }

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

    if (in_array($spec['type'], array('varchar', 'char', 'text')) && isset($spec['length'])) {
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

    // $spec['default'] can be NULL, so we explicitly check for the key here.
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

    // Add column comment.
    if (!empty($spec['description'])) {
      $sql .= ' COMMENT ' . $this->prepareComment($spec['description'], self::COMMENT_MAX_COLUMN);
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
    // Put :normal last so it gets preserved by array_flip. This makes
    // it much easier for modules (such as schema.module) to map
    // database types back into schema types.
    // $map does not use drupal_static as its value never changes.
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

      'date:normal'     => 'DATE',

      'datetime:normal' => 'DATETIME',

      'time:normal'     => 'TIME',
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
        $keys[] = 'UNIQUE KEY `' . $key . '` (' . $this->createKeysSqlHelper($fields) . ')';
      }
    }
    if (!empty($spec['indexes'])) {
      foreach ($spec['indexes'] as $index => $fields) {
        $keys[] = 'INDEX `' . $index . '` (' . $this->createKeysSqlHelper($fields) . ')';
      }
    }

    return $keys;
  }

  protected function createKeySql($fields) {
    $return = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $return[] = '`' . $field[0] . '`(' . $field[1] . ')';
      }
      else {
        $return[] = '`' . $field . '`';
      }
    }
    return implode(', ', $return);
  }

  protected function createKeysSqlHelper($fields) {
    $return = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $return[] = '`' . $field[0] . '`(' . $field[1] . ')';
      }
      else {
        $return[] = '`' . $field . '`';
      }
    }
    return implode(', ', $return);
  }

  public function renameTable($table, $new_name) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot rename %table to %table_new: table %table doesn't exist.", array('%table' => $table, '%table_new' => $new_name)));
    }
    if ($this->tableExists($new_name)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot rename %table to %table_new: table %table_new already exists.", array('%table' => $table, '%table_new' => $new_name)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} RENAME TO {' . $new_name . '}');
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
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add field %table.%field: table doesn't exists.", array('%field' => $field, '%table' => $table)));
    }
    if ($this->columnExists($table, $field)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add field %table.%field: field already exists.", array('%field' => $field, '%table' => $table)));
    }

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
    $this->connection->query($query);
    if (isset($spec['initial'])) {
      $this->connection->update($table)
        ->fields(array($field, $spec['initial']))
        ->execute();
    }
    if ($fixnull) {
      $spec['not null'] = TRUE;
      $this->changeField($table, $field, $field, $spec);
    }
  }

  public function dropField($table, $field) {
    if (!$this->columnExists($table, $field)) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP `' . $field . '`');
    return TRUE;
  }

  public function fieldSetDefault($table, $field, $default) {
    if (!$this->columnExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot set default value of field %table.%field: field doesn't exists.", array('%table' => $table, '%field' => $field)));
    }

    if (is_null($default)) {
      $default = 'NULL';
    }
    else {
      $default = is_string($default) ? "'$default'" : $default;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN `' . $field . '` SET DEFAULT ' . $default);
  }

  public function fieldSetNoDefault($table, $field) {
    if (!$this->columnExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot remove default value of field %table.%field: field doesn't exists.", array('%table' => $table, '%field' => $field)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN `' . $field . '` DROP DEFAULT');
  }

  public function indexExists($table, $name) {
    return $this->connection->query('SHOW INDEX FROM {' . $table . "} WHERE key_name = '$name'")->fetchField();
  }

  public function addPrimaryKey($table, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add primary key to table %table: table doesn't exists.", array('%table' => $table)));
    }
    if ($this->indexExists($table, 'PRIMARY')) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add primary key to table %table: primary key already exists.", array('%table' => $table)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD PRIMARY KEY (' . $this->createKeySql($fields) . ')');
  }

  public function dropPrimaryKey($table) {
    if (!$this->indexExists($table, 'PRIMARY')) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP PRIMARY KEY');
    return TRUE;
  }

  public function addUniqueKey($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add unique key %name to table %table: table doesn't exists.", array('%table' => $table, '%name' => $name)));
    }
    if ($this->indexExists($table, $name)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add unique key %name to table %table: unique key already exists.", array('%table' => $table, '%name' => $name)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD UNIQUE KEY `' . $name . '` (' . $this->createKeySql($fields) . ')');
  }

  public function dropUniqueKey($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP KEY `' . $name . '`');
    return TRUE;
  }

  public function addIndex($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot add index %name to table %table: table doesn't exists.", array('%table' => $table, '%name' => $name)));
    }
    if ($this->indexExists($table, $name)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot add index %name to table %table: index already exists.", array('%table' => $table, '%name' => $name)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD INDEX `' . $name . '` (' . $this->createKeySql($fields) . ')');
  }

  public function dropIndex($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP INDEX `' . $name . '`');
    return TRUE;
  }

  public function changeField($table, $field, $field_new, $spec, $keys_new = array()) {
    if (!$this->columnExists($table, $field)) {
      throw new DatabaseSchemaObjectDoesNotExistException(t("Cannot change the definition of field %table.%name: field doesn't exists.", array('%table' => $table, '%name' => $field)));
    }
    if (($field != $field_new) && $this->columnExists($table, $field_new)) {
      throw new DatabaseSchemaObjectExistsException(t("Cannot rename field %table.%name to %name_new: target field already exists.", array('%table' => $table, '%name' => $name, '%name_new' => $field_new)));
    }

    $sql = 'ALTER TABLE {' . $table . '} CHANGE `' . $field . '` ' . $this->createFieldSql($field_new, $this->processField($spec));
    if (count($keys_new)) {
      $sql .= ', ADD ' . implode(', ADD ', $this->createKeysSql($keys_new));
    }
    $this->connection->query($sql);
  }

  public function prepareComment($comment, $length = NULL) {
    // Work around a bug in some versions of PDO, see http://bugs.php.net/bug.php?id=41125
    $comment = str_replace("'", '’', $comment);

    // Truncate comment to maximum comment length.
    if (isset($length)) {
      // Add table prefixes before truncating.
      $comment = truncate_utf8($this->connection->prefixTables($comment), $length, TRUE, TRUE);
    }

    return $this->connection->quote($comment);
  }

  /**
   * Retrieve a table or column comment.
   */
  public function getComment($table, $column = NULL) {
    $condition = $this->buildTableNameCondition($this->connection->prefixTables('{' . $table . '}'));
    if (isset($column)) {
      $condition->condition('column_name', $column);
      $condition->compile($this->connection, $this);
      // Don't use {} around information_schema.columns table.
      return $this->connection->query("SELECT column_comment FROM information_schema.columns WHERE " . (string) $condition, $condition->arguments())->fetchField();
    }
    $condition->compile($this->connection, $this);
    // Don't use {} around information_schema.tables table.
    $comment = $this->connection->query("SELECT table_comment FROM information_schema.tables WHERE " . (string) $condition, $condition->arguments())->fetchField();
    // Work-around for MySQL 5.0 bug http://bugs.mysql.com/bug.php?id=11379
    return preg_replace('/; InnoDB free:.*$/', '', $comment);
  }

}

/**
 * @} End of "ingroup schemaapi".
 */
