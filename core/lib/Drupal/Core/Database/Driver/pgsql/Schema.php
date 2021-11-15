<?php

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Database\SchemaObjectDoesNotExistException;
use Drupal\Core\Database\Schema as DatabaseSchema;

// cSpell:ignore adbin adnum adrelid adsrc attisdropped attname attnum attrdef
// cSpell:ignore attrelid atttypid atttypmod bigserial conkey conname conrelid
// cSpell:ignore contype fillfactor indexname indexrelid indisprimary indkey
// cSpell:ignore indrelid nextval nspname regclass relkind relname relnamespace
// cSpell:ignore schemaname setval

/**
 * @addtogroup schemaapi
 * @{
 */

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Schema.
 */
class Schema extends DatabaseSchema {

  /**
   * A cache of information about blob columns and sequences of tables.
   *
   * This is collected by Schema::queryTableInformation(), by introspecting the
   * database.
   *
   * @see \Drupal\Core\Database\Driver\pgsql\Schema::queryTableInformation()
   * @var array
   */
  protected $tableInformation = [];

  /**
   * The maximum allowed length for index, primary key and constraint names.
   *
   * Value will usually be set to a 63 chars limit but PostgreSQL allows
   * to higher this value before compiling, so we need to check for that.
   *
   * @var int
   */
  protected $maxIdentifierLength;

  /**
   * PostgreSQL's temporary namespace name.
   *
   * @var string
   */
  protected $tempNamespaceName;

  /**
   * Make sure to limit identifiers according to PostgreSQL compiled in length.
   *
   * PostgreSQL allows in standard configuration identifiers no longer than 63
   * chars for table/relation names, indexes, primary keys, and constraints. So
   * we map all identifiers that are too long to drupal_base64hash_tag, where
   * tag is one of:
   *   - idx for indexes
   *   - key for constraints
   *   - pkey for primary keys
   *   - seq for sequences
   *
   * @param string $table_identifier_part
   *   The first argument used to build the identifier string. This usually
   *   refers to a table/relation name.
   * @param string $column_identifier_part
   *   The second argument used to build the identifier string. This usually
   *   refers to one or more column names.
   * @param string $tag
   *   The identifier tag. It can be one of 'idx', 'key', 'pkey' or 'seq'.
   * @param string $separator
   *   (optional) The separator used to glue together the aforementioned
   *   identifier parts. Defaults to '__'.
   *
   * @return string
   *   The index/constraint/pkey identifier.
   */
  protected function ensureIdentifiersLength($table_identifier_part, $column_identifier_part, $tag, $separator = '__') {
    $info = $this->getPrefixInfo($table_identifier_part);
    $table_identifier_part = $info['table'];
    $identifierName = implode($separator, [$table_identifier_part, $column_identifier_part, $tag]);

    // Retrieve the max identifier length which is usually 63 characters
    // but can be altered before PostgreSQL is compiled so we need to check.
    if (empty($this->maxIdentifierLength)) {
      $this->maxIdentifierLength = $this->connection->query("SHOW max_identifier_length")->fetchField();
    }

    if (strlen($identifierName) > $this->maxIdentifierLength) {
      $saveIdentifier = '"drupal_' . $this->hashBase64($identifierName) . '_' . $tag . '"';
    }
    else {
      $saveIdentifier = $identifierName;
    }
    return $saveIdentifier;
  }

  /**
   * Fetch the list of blobs and sequences used on a table.
   *
   * We introspect the database to collect the information required by insert
   * and update queries.
   *
   * @param string $table
   *   The non-prefixed name of the table.
   *
   * @return mixed|object
   *   An object with two member variables:
   *   - 'blob_fields' that lists all the blob fields in the table.
   *   - 'sequences' that lists the sequences used in that table.
   *
   * @throws \Exception
   *   Exception thrown when the query for the table information fails.
   */
  public function queryTableInformation($table) {
    // Generate a key to reference this table's information on.
    $key = $this->connection->prefixTables('{' . $table . '}');

    // Take into account that temporary tables are stored in a different schema.
    // \Drupal\Core\Database\Connection::generateTemporaryTableName() sets the
    // 'db_temporary_' prefix to all temporary tables.
    if (strpos($key, '.') === FALSE && strpos($table, 'db_temporary_') === FALSE) {
      $key = 'public.' . $key;
    }
    else {
      $key = $this->getTempNamespaceName() . '.' . $key;
    }

    if (!isset($this->tableInformation[$key])) {
      $table_information = (object) [
        'blob_fields' => [],
        'sequences' => [],
      ];
      $this->connection->addSavepoint();

      try {
        // The bytea columns and sequences for a table can be found in
        // pg_attribute, which is significantly faster than querying the
        // information_schema. The data type of a field can be found by lookup
        // of the attribute ID, and the default value must be extracted from the
        // node tree for the attribute definition instead of the historical
        // human-readable column, adsrc.
        $sql = <<<'EOD'
SELECT pg_attribute.attname AS column_name, format_type(pg_attribute.atttypid, pg_attribute.atttypmod) AS data_type, pg_get_expr(pg_attrdef.adbin, pg_attribute.attrelid) AS column_default
FROM pg_attribute
LEFT JOIN pg_attrdef ON pg_attrdef.adrelid = pg_attribute.attrelid AND pg_attrdef.adnum = pg_attribute.attnum
WHERE pg_attribute.attnum > 0
AND NOT pg_attribute.attisdropped
AND pg_attribute.attrelid = :key::regclass
AND (format_type(pg_attribute.atttypid, pg_attribute.atttypmod) = 'bytea'
OR pg_get_expr(pg_attrdef.adbin, pg_attribute.attrelid) LIKE 'nextval%')
EOD;
        $result = $this->connection->query($sql, [
          ':key' => $key,
        ]);
      }
      catch (\Exception $e) {
        $this->connection->rollbackSavepoint();
        throw $e;
      }
      $this->connection->releaseSavepoint();

      // If the table information does not yet exist in the PostgreSQL
      // metadata, then return the default table information here, so that it
      // will not be cached.
      if (empty($result)) {
        return $table_information;
      }

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
   * Gets PostgreSQL's temporary namespace name.
   *
   * @return string
   *   PostgreSQL's temporary namespace name.
   */
  protected function getTempNamespaceName() {
    if (!isset($this->tempNamespaceName)) {
      $this->tempNamespaceName = $this->connection->query('SELECT nspname FROM pg_namespace WHERE oid = pg_my_temp_schema()')->fetchField();
    }
    return $this->tempNamespaceName;
  }

  /**
   * Resets information about table blobs, sequences and serial fields.
   *
   * @param $table
   *   The non-prefixed name of the table.
   */
  protected function resetTableInformation($table) {
    $key = $this->connection->prefixTables('{' . $table . '}');
    if (strpos($key, '.') === FALSE) {
      $key = 'public.' . $key;
    }
    unset($this->tableInformation[$key]);
  }

  /**
   * Fetches the list of constraints used on a field.
   *
   * We introspect the database to collect the information required by field
   * alteration.
   *
   * @param string $table
   *   The non-prefixed name of the table.
   * @param string $field
   *   The name of the field.
   * @param string $constraint_type
   *   (optional) The type of the constraint. This can be one of the following:
   *   - c: check constraint;
   *   - f: foreign key constraint;
   *   - p: primary key constraint;
   *   - u: unique constraint;
   *   - t: constraint trigger;
   *   - x: exclusion constraint.
   *   Defaults to 'c' for a CHECK constraint.
   *   @see https://www.postgresql.org/docs/current/catalog-pg-constraint.html
   *
   * @return array
   *   An array containing all the constraint names for the field.
   *
   * @throws \Exception
   *   Exception thrown when the query for the table information fails.
   */
  public function queryFieldInformation($table, $field, $constraint_type = 'c') {
    assert(in_array($constraint_type, ['c', 'f', 'p', 'u', 't', 'x']));
    $prefixInfo = $this->getPrefixInfo($table, TRUE);

    // Split the key into schema and table for querying.
    $schema = $prefixInfo['schema'];
    $table_name = $prefixInfo['table'];

    $this->connection->addSavepoint();

    try {
      $checks = $this->connection->query("SELECT conname FROM pg_class cl INNER JOIN pg_constraint co ON co.conrelid = cl.oid INNER JOIN pg_attribute attr ON attr.attrelid = cl.oid AND attr.attnum = ANY (co.conkey) INNER JOIN pg_namespace ns ON cl.relnamespace = ns.oid WHERE co.contype = :constraint_type AND ns.nspname = :schema AND cl.relname = :table AND attr.attname = :column", [
        ':constraint_type' => $constraint_type,
        ':schema' => $schema,
        ':table' => $table_name,
        ':column' => $field,
      ]);
    }
    catch (\Exception $e) {
      $this->connection->rollbackSavepoint();
      throw $e;
    }

    $this->connection->releaseSavepoint();

    $field_information = $checks->fetchCol();

    return $field_information;
  }

  /**
   * Generate SQL to create a new table from a Drupal schema definition.
   *
   * @param string $name
   *   The name of the table to create.
   * @param array $table
   *   A Schema API table definition array.
   *
   * @return array
   *   An array of SQL statements to create the table.
   */
  protected function createTableSql($name, $table) {
    $sql_fields = [];
    foreach ($table['fields'] as $field_name => $field) {
      $sql_fields[] = $this->createFieldSql($field_name, $this->processField($field));
    }

    $sql_keys = [];
    if (!empty($table['primary key']) && is_array($table['primary key'])) {
      $this->ensureNotNullPrimaryKey($table['primary key'], $table['fields']);
      $sql_keys[] = 'CONSTRAINT ' . $this->ensureIdentifiersLength($name, '', 'pkey') . ' PRIMARY KEY (' . $this->createPrimaryKeySql($table['primary key']) . ')';
    }
    if (isset($table['unique keys']) && is_array($table['unique keys'])) {
      foreach ($table['unique keys'] as $key_name => $key) {
        $sql_keys[] = 'CONSTRAINT ' . $this->ensureIdentifiersLength($name, $key_name, 'key') . ' UNIQUE (' . implode(', ', $key) . ')';
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
   * @param $name
   *   Name of the field.
   * @param $spec
   *   The field specification, as per the schema data structure format.
   */
  protected function createFieldSql($name, $spec) {
    // The PostgreSQL server converts names into lowercase, unless quoted.
    $sql = '"' . $name . '" ' . $spec['pgsql_type'];

    if (isset($spec['type']) && $spec['type'] == 'serial') {
      unset($spec['not null']);
    }

    if (in_array($spec['pgsql_type'], ['varchar', 'character']) && isset($spec['length'])) {
      $sql .= '(' . $spec['length'] . ')';
    }
    elseif (isset($spec['precision']) && isset($spec['scale'])) {
      $sql .= '(' . $spec['precision'] . ', ' . $spec['scale'] . ')';
    }

    if (!empty($spec['unsigned'])) {
      $sql .= " CHECK ($name >= 0)";
    }

    if (isset($spec['not null'])) {
      if ($spec['not null']) {
        $sql .= ' NOT NULL';
      }
      else {
        $sql .= ' NULL';
      }
    }
    if (array_key_exists('default', $spec)) {
      $default = $this->escapeDefaultValue($spec['default']);
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
    // In case one is already provided, force it to lowercase.
    if (isset($field['pgsql_type'])) {
      $field['pgsql_type'] = mb_strtolower($field['pgsql_type']);
    }
    else {
      $map = $this->getFieldTypeMap();
      $field['pgsql_type'] = $map[$field['type'] . ':' . $field['size']];
    }

    if (!empty($field['unsigned'])) {
      // Unsigned data types are not supported in PostgreSQL 10. In MySQL,
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

        case 'int':
          $field['pgsql_type'] = $map['int:big'];
          break;
      }
    }
    if (isset($field['type']) && $field['type'] == 'serial') {
      unset($field['not null']);
    }
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeMap() {
    // Put :normal last so it gets preserved by array_flip. This makes
    // it much easier for modules (such as schema.module) to map
    // database types back into schema types.
    // $map does not use drupal_static as its value never changes.
    static $map = [
      'varchar_ascii:normal' => 'varchar',

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
      ];
    return $map;
  }

  protected function _createKeySql($fields) {
    $return = [];
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

  /**
   * Create the SQL expression for primary keys.
   *
   * Postgresql does not support key length. It does support fillfactor, but
   * that requires a separate database lookup for each column in the key. The
   * key length defined in the schema is ignored.
   */
  protected function createPrimaryKeySql($fields) {
    $return = [];
    foreach ($fields as $field) {
      if (is_array($field)) {
        $return[] = '"' . $field[0] . '"';
      }
      else {
        $return[] = '"' . $field . '"';
      }
    }
    return implode(', ', $return);
  }

  /**
   * {@inheritdoc}
   */
  public function tableExists($table) {
    $prefixInfo = $this->getPrefixInfo($table, TRUE);

    return (bool) $this->connection->query("SELECT 1 FROM pg_tables WHERE schemaname = :schema AND tablename = :table", [':schema' => $prefixInfo['schema'], ':table' => $prefixInfo['table']])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function findTables($table_expression) {
    $individually_prefixed_tables = $this->connection->getUnprefixedTablesMap();
    $default_prefix = $this->connection->tablePrefix();
    $default_prefix_length = strlen($default_prefix);
    $tables = [];

    // Load all the tables up front in order to take into account per-table
    // prefixes. The actual matching is done at the bottom of the method.
    $results = $this->connection->query("SELECT tablename FROM pg_tables WHERE schemaname = :schema", [':schema' => $this->defaultSchema]);
    foreach ($results as $table) {
      // Take into account tables that have an individual prefix.
      if (isset($individually_prefixed_tables[$table->tablename])) {
        $prefix_length = strlen($this->connection->tablePrefix($individually_prefixed_tables[$table->tablename]));
      }
      elseif ($default_prefix && substr($table->tablename, 0, $default_prefix_length) !== $default_prefix) {
        // This table name does not start the default prefix, which means that
        // it is not managed by Drupal so it should be excluded from the result.
        continue;
      }
      else {
        $prefix_length = $default_prefix_length;
      }

      // Remove the prefix from the returned tables.
      $unprefixed_table_name = substr($table->tablename, $prefix_length);

      // The pattern can match a table which is the same as the prefix. That
      // will become an empty string when we remove the prefix, which will
      // probably surprise the caller, besides not being a prefixed table. So
      // remove it.
      if (!empty($unprefixed_table_name)) {
        $tables[$unprefixed_table_name] = $unprefixed_table_name;
      }
    }

    // Convert the table expression from its SQL LIKE syntax to a regular
    // expression and escape the delimiter that will be used for matching.
    $table_expression = str_replace(['%', '_'], ['.*?', '.'], preg_quote($table_expression, '/'));
    $tables = preg_grep('/^' . $table_expression . '$/i', $tables);

    return $tables;
  }

  /**
   * {@inheritdoc}
   */
  public function renameTable($table, $new_name) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot rename '$table' to '$new_name': table '$table' doesn't exist.");
    }
    if ($this->tableExists($new_name)) {
      throw new SchemaObjectExistsException("Cannot rename '$table' to '$new_name': table '$new_name' already exists.");
    }

    // Get the schema and tablename for the old table.
    $old_full_name = str_replace('"', '', $this->connection->prefixTables('{' . $table . '}'));
    [$old_schema, $old_table_name] = strpos($old_full_name, '.') ? explode('.', $old_full_name) : ['public', $old_full_name];

    // Index names and constraint names are global in PostgreSQL, so we need to
    // rename them when renaming the table.
    $indexes = $this->connection->query('SELECT indexname FROM pg_indexes WHERE schemaname = :schema AND tablename = :table', [':schema' => $old_schema, ':table' => $old_table_name]);

    foreach ($indexes as $index) {
      // Get the index type by suffix, e.g. idx/key/pkey
      $index_type = substr($index->indexname, strrpos($index->indexname, '_') + 1);

      // If the index is already rewritten by ensureIdentifiersLength() to not
      // exceed the 63 chars limit of PostgreSQL, we need to take care of that.
      // cSpell:disable-next-line
      // Example (drupal_Gk7Su_T1jcBHVuvSPeP22_I3Ni4GrVEgTYlIYnBJkro_idx).
      if (strpos($index->indexname, 'drupal_') !== FALSE) {
        preg_match('/^drupal_(.*)_' . preg_quote($index_type) . '/', $index->indexname, $matches);
        $index_name = $matches[1];
      }
      else {
        // Make sure to remove the suffix from index names, because
        // $this->ensureIdentifiersLength() will add the suffix again and thus
        // would result in a wrong index name.
        preg_match('/^' . preg_quote($old_full_name) . '__(.*)__' . preg_quote($index_type) . '/', $index->indexname, $matches);
        $index_name = $matches[1];
      }
      $this->connection->query('ALTER INDEX "' . $index->indexname . '" RENAME TO ' . $this->ensureIdentifiersLength($new_name, $index_name, $index_type) . '');
    }

    // Ensure the new table name does not include schema syntax.
    $prefixInfo = $this->getPrefixInfo($new_name);

    // Rename sequences if the table contains serial fields.
    $info = $this->queryTableInformation($table);
    if (!empty($info->serial_fields)) {
      foreach ($info->serial_fields as $field) {
        // The initial name of the sequence is generated automatically by
        // PostgreSQL when the table is created, so we need to use
        // pg_get_serial_sequence() to retrieve it.
        $old_sequence = $this->connection->query("SELECT pg_get_serial_sequence('" . $old_full_name . "', '" . $field . "')")->fetchField();

        // If the new sequence name exceeds the maximum identifier length limit,
        // it will not match the pattern that is automatically applied by
        // PostgreSQL on table creation, but that's ok because
        // pg_get_serial_sequence() will return our non-standard name on
        // subsequent table renames.
        $new_sequence = $this->ensureIdentifiersLength($new_name, $field, 'seq', '_');

        $this->connection->query('ALTER SEQUENCE ' . $old_sequence . ' RENAME TO ' . $new_sequence);
      }
    }
    // Now rename the table.
    $this->connection->query('ALTER TABLE {' . $table . '} RENAME TO ' . $prefixInfo['table']);
    $this->resetTableInformation($table);
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }

    $this->connection->query('DROP TABLE {' . $table . '}');
    $this->resetTableInformation($table);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $spec, $new_keys = []) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add field '$table.$field': table doesn't exist.");
    }
    if ($this->fieldExists($table, $field)) {
      throw new SchemaObjectExistsException("Cannot add field '$table.$field': field already exists.");
    }

    // Fields that are part of a PRIMARY KEY must be added as NOT NULL.
    $is_primary_key = isset($new_keys['primary key']) && in_array($field, $new_keys['primary key'], TRUE);
    if ($is_primary_key) {
      $this->ensureNotNullPrimaryKey($new_keys['primary key'], [$field => $spec]);
    }

    $fixnull = FALSE;
    if (!empty($spec['not null']) && !isset($spec['default']) && !$is_primary_key) {
      $fixnull = TRUE;
      $spec['not null'] = FALSE;
    }
    $query = 'ALTER TABLE {' . $table . '} ADD COLUMN ';
    $query .= $this->createFieldSql($field, $this->processField($spec));
    $this->connection->query($query);
    if (isset($spec['initial_from_field'])) {
      if (isset($spec['initial'])) {
        $expression = 'COALESCE(' . $spec['initial_from_field'] . ', :default_initial_value)';
        $arguments = [':default_initial_value' => $spec['initial']];
      }
      else {
        $expression = $spec['initial_from_field'];
        $arguments = [];
      }
      $this->connection->update($table)
        ->expression($field, $expression, $arguments)
        ->execute();
    }
    elseif (isset($spec['initial'])) {
      $this->connection->update($table)
        ->fields([$field => $spec['initial']])
        ->execute();
    }
    if ($fixnull) {
      $this->connection->query("ALTER TABLE {" . $table . "} ALTER $field SET NOT NULL");
    }
    if (isset($new_keys)) {
      // Make sure to drop the existing primary key before adding a new one.
      // This is only needed when adding a field because this method, unlike
      // changeField(), is supposed to handle primary keys automatically.
      if (isset($new_keys['primary key']) && $this->constraintExists($table, 'pkey')) {
        $this->dropPrimaryKey($table);
      }
      $this->_createKeys($table, $new_keys);
    }
    // Add column comment.
    if (!empty($spec['description'])) {
      $this->connection->query('COMMENT ON COLUMN {' . $table . '}.' . $field . ' IS ' . $this->prepareComment($spec['description']));
    }
    $this->resetTableInformation($table);
  }

  /**
   * {@inheritdoc}
   */
  public function dropField($table, $field) {
    if (!$this->fieldExists($table, $field)) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP COLUMN "' . $field . '"');
    $this->resetTableInformation($table);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldExists($table, $column) {
    $prefixInfo = $this->getPrefixInfo($table);

    return (bool) $this->connection->query("SELECT 1 FROM pg_attribute WHERE attrelid = :key::regclass AND attname = :column AND NOT attisdropped AND attnum > 0", [':key' => $prefixInfo['schema'] . '.' . $prefixInfo['table'], ':column' => $column])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function indexExists($table, $name) {
    // Details https://www.postgresql.org/docs/10/view-pg-indexes.html
    $index_name = $this->ensureIdentifiersLength($table, $name, 'idx');
    // Remove leading and trailing quotes because the index name is in a WHERE
    // clause and not used as an identifier.
    $index_name = str_replace('"', '', $index_name);
    return (bool) $this->connection->query("SELECT 1 FROM pg_indexes WHERE indexname = '$index_name'")->fetchField();
  }

  /**
   * Helper function: check if a constraint (PK, FK, UK) exists.
   *
   * @param string $table
   *   The name of the table.
   * @param string $name
   *   The name of the constraint (typically 'pkey' or '[constraint]__key').
   *
   * @return bool
   *   TRUE if the constraint exists, FALSE otherwise.
   */
  public function constraintExists($table, $name) {
    // ::ensureIdentifiersLength() expects three parameters, although not
    // explicitly stated in its signature, thus we split our constraint name in
    // a proper name and a suffix.
    if ($name == 'pkey') {
      $suffix = $name;
      $name = '';
    }
    else {
      $pos = strrpos($name, '__');
      $suffix = substr($name, $pos + 2);
      $name = substr($name, 0, $pos);
    }
    $constraint_name = $this->ensureIdentifiersLength($table, $name, $suffix);
    // Remove leading and trailing quotes because the index name is in a WHERE
    // clause and not used as an identifier.
    $constraint_name = str_replace('"', '', $constraint_name);
    return (bool) $this->connection->query("SELECT 1 FROM pg_constraint WHERE conname = '$constraint_name'")->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function addPrimaryKey($table, $fields) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add primary key to table '$table': table doesn't exist.");
    }
    if ($this->constraintExists($table, 'pkey')) {
      throw new SchemaObjectExistsException("Cannot add primary key to table '$table': primary key already exists.");
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD CONSTRAINT ' . $this->ensureIdentifiersLength($table, '', 'pkey') . ' PRIMARY KEY (' . $this->createPrimaryKeySql($fields) . ')');
    $this->resetTableInformation($table);
  }

  /**
   * {@inheritdoc}
   */
  public function dropPrimaryKey($table) {
    if (!$this->constraintExists($table, 'pkey')) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP CONSTRAINT ' . $this->ensureIdentifiersLength($table, '', 'pkey'));
    $this->resetTableInformation($table);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function findPrimaryKeyColumns($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }
    return $this->connection->query("SELECT array_position(i.indkey, a.attnum) AS position, a.attname FROM pg_index i JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey) WHERE i.indrelid = '{" . $table . "}'::regclass AND i.indisprimary ORDER BY position")->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function addUniqueKey($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add unique key '$name' to table '$table': table doesn't exist.");
    }
    if ($this->constraintExists($table, $name . '__key')) {
      throw new SchemaObjectExistsException("Cannot add unique key '$name' to table '$table': unique key already exists.");
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD CONSTRAINT ' . $this->ensureIdentifiersLength($table, $name, 'key') . ' UNIQUE (' . implode(',', $fields) . ')');
    $this->resetTableInformation($table);
  }

  /**
   * {@inheritdoc}
   */
  public function dropUniqueKey($table, $name) {
    if (!$this->constraintExists($table, $name . '__key')) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP CONSTRAINT ' . $this->ensureIdentifiersLength($table, $name, 'key'));
    $this->resetTableInformation($table);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex($table, $name, $fields, array $spec) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add index '$name' to table '$table': table doesn't exist.");
    }
    if ($this->indexExists($table, $name)) {
      throw new SchemaObjectExistsException("Cannot add index '$name' to table '$table': index already exists.");
    }

    $this->connection->query($this->_createIndexSql($table, $name, $fields));
    $this->resetTableInformation($table);
  }

  /**
   * {@inheritdoc}
   */
  public function dropIndex($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('DROP INDEX ' . $this->ensureIdentifiersLength($table, $name, 'idx'));
    $this->resetTableInformation($table);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function introspectIndexSchema($table) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("The table $table doesn't exist.");
    }

    $index_schema = [
      'primary key' => [],
      'unique keys' => [],
      'indexes' => [],
    ];

    // Get the schema and tablename for the table without identifier quotes.
    $full_name = str_replace('"', '', $this->connection->prefixTables('{' . $table . '}'));
    $result = $this->connection->query("SELECT i.relname AS index_name, a.attname AS column_name FROM pg_class t, pg_class i, pg_index ix, pg_attribute a WHERE t.oid = ix.indrelid AND i.oid = ix.indexrelid AND a.attrelid = t.oid AND a.attnum = ANY(ix.indkey) AND t.relkind = 'r' AND t.relname = :table_name ORDER BY index_name ASC, column_name ASC", [
      ':table_name' => $full_name,
    ])->fetchAll();
    foreach ($result as $row) {
      if (preg_match('/_pkey$/', $row->index_name)) {
        $index_schema['primary key'][] = $row->column_name;
      }
      elseif (preg_match('/_key$/', $row->index_name)) {
        $index_schema['unique keys'][$row->index_name][] = $row->column_name;
      }
      elseif (preg_match('/_idx$/', $row->index_name)) {
        $index_schema['indexes'][$row->index_name][] = $row->column_name;
      }
    }

    return $index_schema;
  }

  /**
   * {@inheritdoc}
   */
  public function changeField($table, $field, $field_new, $spec, $new_keys = []) {
    if (!$this->fieldExists($table, $field)) {
      throw new SchemaObjectDoesNotExistException("Cannot change the definition of field '$table.$field': field doesn't exist.");
    }
    if (($field != $field_new) && $this->fieldExists($table, $field_new)) {
      throw new SchemaObjectExistsException("Cannot rename field '$table.$field' to '$field_new': target field already exists.");
    }
    if (isset($new_keys['primary key']) && in_array($field_new, $new_keys['primary key'], TRUE)) {
      $this->ensureNotNullPrimaryKey($new_keys['primary key'], [$field_new => $spec]);
    }

    $spec = $this->processField($spec);

    // Type 'serial' is known to PostgreSQL, but only during table creation,
    // not when altering. Because of that, we create it here as an 'int'. After
    // we create it we manually re-apply the sequence.
    if (in_array($spec['pgsql_type'], ['serial', 'bigserial'])) {
      $field_def = 'int';
    }
    else {
      $field_def = $spec['pgsql_type'];
    }

    if (in_array($spec['pgsql_type'], ['varchar', 'character', 'text']) && isset($spec['length'])) {
      $field_def .= '(' . $spec['length'] . ')';
    }
    elseif (isset($spec['precision']) && isset($spec['scale'])) {
      $field_def .= '(' . $spec['precision'] . ', ' . $spec['scale'] . ')';
    }

    // Remove old check constraints.
    $field_info = $this->queryFieldInformation($table, $field);

    foreach ($field_info as $check) {
      $this->connection->query('ALTER TABLE {' . $table . '} DROP CONSTRAINT "' . $check . '"');
    }

    // Remove old default.
    $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN "' . $field . '" DROP DEFAULT');

    // Convert field type.
    // Usually, we do this via a simple typecast 'USING fieldname::type'. But
    // the typecast does not work for conversions to bytea.
    // @see http://www.postgresql.org/docs/current/static/datatype-binary.html
    $table_information = $this->queryTableInformation($table);
    $is_bytea = !empty($table_information->blob_fields[$field]);
    if ($spec['pgsql_type'] != 'bytea') {
      if ($is_bytea) {
        $this->connection->query('ALTER TABLE {' . $table . '} ALTER "' . $field . '" TYPE ' . $field_def . ' USING convert_from("' . $field . '"' . ", 'UTF8')");
      }
      else {
        $this->connection->query('ALTER TABLE {' . $table . '} ALTER "' . $field . '" TYPE ' . $field_def . ' USING "' . $field . '"::' . $field_def);
      }
    }
    else {
      // Do not attempt to convert a field that is bytea already.
      if (!$is_bytea) {
        // Convert to a bytea type by using the SQL replace() function to
        // convert any single backslashes in the field content to double
        // backslashes ('\' to '\\').
        $this->connection->query('ALTER TABLE {' . $table . '} ALTER "' . $field . '" TYPE ' . $field_def . ' USING decode(replace("' . $field . '"' . ", E'\\\\', E'\\\\\\\\'), 'escape');");
      }
    }

    if (isset($spec['not null'])) {
      if ($spec['not null']) {
        $null_action = 'SET NOT NULL';
      }
      else {
        $null_action = 'DROP NOT NULL';
      }
      $this->connection->query('ALTER TABLE {' . $table . '} ALTER "' . $field . '" ' . $null_action);
    }

    if (in_array($spec['pgsql_type'], ['serial', 'bigserial'])) {
      // Type "serial" is known to PostgreSQL, but *only* during table creation,
      // not when altering. Because of that, the sequence needs to be created
      // and initialized by hand.
      $seq = $this->connection->makeSequenceName($table, $field_new);
      $this->connection->query("CREATE SEQUENCE " . $seq);
      // Set sequence to maximal field value to not conflict with existing
      // entries.
      $this->connection->query("SELECT setval('" . $seq . "', MAX(\"" . $field . '")) FROM {' . $table . "}");
      $this->connection->query('ALTER TABLE {' . $table . '} ALTER ' . $field . ' SET DEFAULT nextval(' . $this->connection->quote($seq) . ')');
    }

    // Rename the column if necessary.
    if ($field != $field_new) {
      $this->connection->query('ALTER TABLE {' . $table . '} RENAME "' . $field . '" TO "' . $field_new . '"');
    }

    // Add unsigned check if necessary.
    if (!empty($spec['unsigned'])) {
      $this->connection->query('ALTER TABLE {' . $table . '} ADD CHECK ("' . $field_new . '" >= 0)');
    }

    // Add default if necessary.
    if (isset($spec['default'])) {
      $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN "' . $field_new . '" SET DEFAULT ' . $this->escapeDefaultValue($spec['default']));
    }

    // Change description if necessary.
    if (!empty($spec['description'])) {
      $this->connection->query('COMMENT ON COLUMN {' . $table . '}."' . $field_new . '" IS ' . $this->prepareComment($spec['description']));
    }

    if (isset($new_keys)) {
      $this->_createKeys($table, $new_keys);
    }
    $this->resetTableInformation($table);
  }

  protected function _createIndexSql($table, $name, $fields) {
    $query = 'CREATE INDEX ' . $this->ensureIdentifiersLength($table, $name, 'idx') . ' ON {' . $table . '} (';
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
        // Even though $new_keys is not a full schema it still has 'indexes' and
        // so is a partial schema. Technically addIndex() doesn't do anything
        // with it so passing an empty array would work as well.
        $this->addIndex($table, $name, $fields, $new_keys);
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
      return $this->connection->query('SELECT col_description(oid, attnum) FROM pg_class, pg_attribute WHERE attrelid = oid AND relname = ? AND attname = ?', [$info['table'], $column])->fetchField();
    }
    else {
      return $this->connection->query('SELECT obj_description(oid, ?) FROM pg_class WHERE relname = ?', ['pg_class', $info['table']])->fetchField();
    }
  }

  /**
   * Calculates a base-64 encoded, PostgreSQL-safe sha-256 hash per PostgreSQL
   * documentation: 4.1. Lexical Structure.
   *
   * @param $data
   *   String to be hashed.
   *
   * @return string
   *   A base-64 encoded sha-256 hash, with + and / replaced with _ and any =
   *   padding characters removed.
   */
  protected function hashBase64($data) {
    $hash = base64_encode(hash('sha256', $data, TRUE));
    // Modify the hash so it's safe to use in PostgreSQL identifiers.
    return strtr($hash, ['+' => '_', '/' => '_', '=' => '']);
  }

  /**
   * Determines whether the PostgreSQL extension is created.
   *
   * @param string $name
   *   The name of the extension.
   *
   * @return bool
   *   Return TRUE when the extension is created, FALSE otherwise.
   *
   * @internal
   */
  public function extensionExists($name): bool {
    return (bool) $this->connection->query('SELECT installed_version FROM pg_available_extensions WHERE name = :name', [
      ':name' => $name,
    ])->fetchField();
  }

}

/**
 * @} End of "addtogroup schemaapi".
 */
