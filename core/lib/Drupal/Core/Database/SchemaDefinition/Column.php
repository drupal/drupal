<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

use Drupal\Core\Database\Exception\SchemaDefinitionException;

/**
 * Describes a database table's column.
 */
final class Column implements SchemaDefinitionInterface {

  /**
   * Constructor.
   *
   * @param string $name
   *   The column name.
   * @param ?ColumnType $type
   *   (Optional) The column data type, generic. Each database will map this to
   *   its own definition. This argument is mandatory unless $dbSpecificExtra is
   *   specified. See
   *   \Drupal\Core\Database\SchemaDefinition\ColumnType for allowed values.
   * @param ?string $description
   *   (Optional) A string in non-markup plain text describing this field and
   *   its purpose. References to other tables should be enclosed in curly
   *   brackets. For example, the users_data table 'uid' field description
   *   might contain "The {users}.uid this record affects.".
   * @param ?bool $serialize
   *   (Optional) A boolean indicating whether the field will be stored as a
   *   serialized string. If NULL, it is not specified. Defaults to NULL.
   * @param ?ColumnSize $size
   *   (Optional) The column data size. This is a hint about the largest value
   *   the column will store. See
   *   \Drupal\Core\Database\SchemaDefinition\ColumnSize for allowed values.
   * @param ?bool $notNull
   *   (Optional)  If true, no NULL values will be allowed in this database
   *   column. If false, NULL values will be allowed. If NULL, it is not
   *   specified. Defaults to NULL.
   * @param StringValue|IntValue|FloatValue|NullValue|null $default
   *   (Optional) The field's default value.
   * @param ?int $length
   *   (Optional) The maximum length of a type 'char', 'varchar' or 'text'
   *   field. Ignored for other field types.
   * @param ?bool $unsigned
   *   (Optional) A boolean indicating whether a type 'int', 'float' and
   *   'numeric' only is signed or unsigned. If NULL, it is not specified.
   *    Defaults to NULL.
   * @param ?int $precision
   *   (Optional) Mandatory for type 'numeric' fields, indicates the precision
   *   (total number of significant digits). Ignored for other field types.
   * @param ?int $scale
   *   (Optional) Mandatory for type 'numeric' fields, indicates the scale
   *   (decimal digits right of the decimal point). Ignored for other field
   *   types.
   * @param ?bool $binary
   *   (Optional) A boolean indicating that MySQL should force 'char',
   *   'varchar' or 'text' fields to use case-sensitive binary collation. This
   *   has no effect on other database types for which case sensitivity is
   *   already the default behavior. If NULL, it is not specified. Defaults to
   *   NULL.
   * @param ?GeneratedColumnStorage $generatedStorage
   *   The storage strategy for a generated column (VIRTUAL or STORED).
   * @param ?GeneratedColumnExpression $generatedExpression
   *   The expression for a generated column.
   * @param array<string,array<string,mixed>>|null $dbSpecificExtra
   *   (Optional) If you need to use a column type not included in the
   *   officially supported list of types above, you can specify a type for
   *   each database backend. Specify this as an associative array having the
   *   database type ('mysql', 'sqlite', 'pgsql', 'oracle', etc.) as the key,
   *   and an array of extra information <string,mixed> as the value. If NULL,
   *   it is not specified. Defaults to NULL.
   *
   * @see \Drupal\Core\Database\SchemaDefinition\ColumnType
   * @see \Drupal\Core\Database\SchemaDefinition\ColumnSize
   * @see \Drupal\Core\Database\SchemaDefinition\GeneratedColumnExpression
   * @see \Drupal\Core\Database\SchemaDefinition\GeneratedColumnStorage
   */
  private function __construct(
    public readonly string $name,
    public readonly ?ColumnType $type = NULL,
    public readonly ?string $description = NULL,
    public readonly ?bool $serialize = NULL,
    public readonly ?ColumnSize $size = NULL,
    public readonly ?bool $notNull = NULL,
    public readonly StringValue|IntValue|FloatValue|NullValue|null $default = NULL,
    public readonly ?int $length = NULL,
    public readonly ?bool $unsigned = NULL,
    public readonly ?int $precision = NULL,
    public readonly ?int $scale = NULL,
    public readonly ?bool $binary = NULL,
    public readonly ?GeneratedColumnStorage $generatedStorage = NULL,
    public readonly ?GeneratedColumnExpression $generatedExpression = NULL,
    public readonly ?array $dbSpecificExtra = NULL,
  ) {
  }

  /**
   * Returns new a Column object.
   */
  public static function create(
    string $name,
    ?ColumnType $type = NULL,
    ?string $description = NULL,
    ?bool $serialize = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    StringValue|IntValue|FloatValue|NullValue|null $default = NULL,
    ?int $length = NULL,
    ?bool $unsigned = NULL,
    ?int $precision = NULL,
    ?int $scale = NULL,
    ?bool $binary = NULL,
    ?array $dbSpecificExtra = NULL,
  ): self {
    $instance = new self(
      name: $name,
      type: $type,
      description: $description,
      serialize: $serialize,
      size: $size,
      notNull: $notNull,
      default: $default,
      length: $length,
      unsigned: $unsigned,
      precision: $precision,
      scale: $scale,
      binary: $binary,
      dbSpecificExtra: $dbSpecificExtra,
    );
    $instance->validate();
    return $instance;
  }

  /**
   * Validates the properties of the value object.
   *
   * @throws \Drupal\Core\Database\Exception\SchemaDefinitionException
   */
  private function validate(): void {

    // If no column type specified, a db specific one should be set.
    if ($this->type === NULL && $this->dbSpecificExtra === NULL) {
      throw new SchemaDefinitionException("Neither 'type' nor 'dbSpecificExtra' for column '{$this->name}'");
    }

    // Cannot set notNull for serial columns.
    if ($this->notNull !== NULL && $this->type === ColumnType::Serial) {
      throw new SchemaDefinitionException("Cannot set 'notNull' for {$this->type->value} column '{$this->name}'");
    }

    // A serial column takes its value from a sequence, so it cannot also be
    // computed from an expression.
    if ($this->generatedExpression !== NULL && $this->type === ColumnType::Serial) {
      throw new SchemaDefinitionException("Cannot set 'generatedExpression' for {$this->type->value} column '{$this->name}'");
    }

    // Can only set unsigned for some column types.
    if ($this->unsigned !== NULL && $this->type !== NULL && !in_array($this->type, [
      ColumnType::Int,
      ColumnType::Float,
      ColumnType::Numeric,
    ], TRUE)) {
      throw new SchemaDefinitionException("Cannot set 'unsigned' for {$this->type->value} column '{$this->name}'");
    }

    // Can only set length for some column types.
    if ($this->length !== NULL && $this->type !== NULL && !in_array($this->type, [
      ColumnType::Char,
      ColumnType::Varchar,
      ColumnType::VarcharAscii,
      ColumnType::Text,
    ], TRUE)) {
      throw new SchemaDefinitionException("Cannot set 'length' for {$this->type->value} column '{$this->name}'");
    }

    // Can only set binary for some column types.
    if ($this->binary !== NULL && $this->type !== NULL && !in_array($this->type, [
      ColumnType::Char,
      ColumnType::Varchar,
      ColumnType::VarcharAscii,
      ColumnType::Text,
    ], TRUE)) {
      throw new SchemaDefinitionException("Cannot set 'binary' for {$this->type->value} column '{$this->name}'");
    }

    // Can only set scale of a numeric column.
    if ($this->precision !== NULL && $this->type !== NULL && $this->type !== ColumnType::Numeric) {
      throw new SchemaDefinitionException("Cannot set 'precision' for {$this->type->value} column '{$this->name}'");
    }

    // Can only set precision of a numeric column.
    if ($this->scale !== NULL && $this->type !== NULL && $this->type !== ColumnType::Numeric) {
      throw new SchemaDefinitionException("Cannot set 'scale' for {$this->type->value} column '{$this->name}'");
    }

    // Can only set serialize on a text/blob column.
    if ($this->serialize !== NULL && $this->type !== NULL &&  !in_array($this->type, [
      ColumnType::Text,
      ColumnType::Blob,
    ], TRUE)) {
      throw new SchemaDefinitionException("Cannot set 'serialize' for {$this->type->value} column '{$this->name}'");
    }

    // Check validity of default type.
    if ($this->type !== NULL && $this->default !== NULL && !$this->type->isValidDefaultValue($this->default)) {
      throw new SchemaDefinitionException("Cannot set a {$this->default->name} default for column '{$this->name}'");
    }

    // Cannot set a null value if notNull is true.
    if ($this->notNull && $this->default && $this->default->value === NULL) {
      throw new SchemaDefinitionException("Cannot set a null default for column '{$this->name}': notNull is true");
    }

  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    $spec = [];
    if ($this->type) {
      $spec['type'] = $this->type->value;
      if ($this->type === ColumnType::Serial) {
        $spec['not null'] = TRUE;
        $spec['unsigned'] = TRUE;
      }
    }
    if ($this->description !== NULL) {
      $spec['description'] = $this->description;
    }
    if ($this->generatedStorage) {
      $spec['generated'] = $this;
    }
    if ($this->serialize !== NULL) {
      $spec['serialize'] = $this->serialize;
    }
    if ($this->size) {
      $spec['size'] = $this->size->value;
    }
    if ($this->notNull !== NULL) {
      $spec['not null'] = $this->notNull;
    }
    if ($this->default) {
      $spec['default'] = $this->default->value;
    }
    if ($this->length !== NULL) {
      $spec['length'] = $this->length;
    }
    if ($this->unsigned !== NULL) {
      $spec['unsigned'] = $this->unsigned;
    }
    if ($this->precision !== NULL) {
      $spec['precision'] = $this->precision;
    }
    if ($this->scale !== NULL) {
      $spec['scale'] = $this->scale;
    }
    if ($this->binary !== NULL) {
      $spec['binary'] = $this->binary;
    }
    if ($this->dbSpecificExtra !== NULL) {
      foreach ($this->dbSpecificExtra as $extra) {
        foreach ($extra as $key => $value) {
          $spec[$key] = $value;
        }
      }
    }
    return $spec;
  }

  /**
   * Returns a Column object for a char column.
   */
  public static function char(
    string $name,
    ?string $description = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    StringValue|NullValue|null $default = NULL,
    ?int $length = NULL,
    ?bool $binary = NULL,
  ): self {
    return self::create(
      type: ColumnType::Char,
      name: $name,
      description: $description,
      size: $size,
      notNull: $notNull,
      default: $default,
      length: $length,
      binary: $binary,
    );
  }

  /**
   * Returns a Column object for a varchar column.
   */
  public static function varchar(
    string $name,
    ?string $description = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    StringValue|NullValue|null $default = NULL,
    ?int $length = NULL,
    ?bool $binary = NULL,
  ): self {
    return self::create(
      type: ColumnType::Varchar,
      name: $name,
      description: $description,
      size: $size,
      notNull: $notNull,
      default: $default,
      length: $length,
      binary: $binary,
    );
  }

  /**
   * Returns a Column object for a varcharAscii column.
   */
  public static function varcharAscii(
    string $name,
    ?string $description = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    StringValue|NullValue|null $default = NULL,
    ?int $length = NULL,
    ?bool $binary = NULL,
  ): self {
    return self::create(
      type: ColumnType::VarcharAscii,
      name: $name,
      description: $description,
      size: $size,
      notNull: $notNull,
      default: $default,
      length: $length,
      binary: $binary,
    );
  }

  /**
   * Returns a Column object for a text column.
   */
  public static function text(
    string $name,
    ?string $description = NULL,
    ?bool $serialize = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    StringValue|NullValue|null $default = NULL,
    ?int $length = NULL,
    ?bool $binary = NULL,
  ): self {
    return self::create(
      type: ColumnType::Text,
      name: $name,
      description: $description,
      serialize: $serialize,
      size: $size,
      notNull: $notNull,
      default: $default,
      length: $length,
      binary: $binary,
    );
  }

  /**
   * Returns a Column object for an int column.
   */
  public static function int(
    string $name,
    ?string $description = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    IntValue|NullValue|null $default = NULL,
    ?bool $unsigned = NULL,
  ): self {
    return self::create(
      type: ColumnType::Int,
      name: $name,
      description: $description,
      size: $size,
      notNull: $notNull,
      default: $default,
      unsigned: $unsigned,
    );
  }

  /**
   * Returns a Column object for a serial column.
   */
  public static function serial(
    string $name,
    ?string $description = NULL,
    ?ColumnSize $size = NULL,
  ): self {
    return self::create(
      type: ColumnType::Serial,
      name: $name,
      description: $description,
      size: $size,
    );
  }

  /**
   * Returns a Column object for an float column.
   */
  public static function float(
    string $name,
    ?string $description = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    FloatValue|NullValue|null $default = NULL,
    ?bool $unsigned = NULL,
  ): self {
    return self::create(
      type: ColumnType::Float,
      name: $name,
      description: $description,
      size: $size,
      notNull: $notNull,
      default: $default,
      unsigned: $unsigned,
    );
  }

  /**
   * Returns a Column object for an numeric column.
   */
  public static function numeric(
    string $name,
    ?string $description = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    FloatValue|NullValue|null $default = NULL,
    ?bool $unsigned = NULL,
    ?int $precision = NULL,
    ?int $scale = NULL,
  ): self {
    return self::create(
      type: ColumnType::Numeric,
      name: $name,
      description: $description,
      size: $size,
      notNull: $notNull,
      default: $default,
      unsigned: $unsigned,
      precision: $precision,
      scale: $scale,
    );
  }

  /**
   * Returns a Column object for a blob column.
   */
  public static function blob(
    string $name,
    ?string $description = NULL,
    ?bool $serialize = NULL,
    ?ColumnSize $size = NULL,
    ?bool $notNull = NULL,
    StringValue|NullValue|null $default = NULL,
  ): self {
    return self::create(
      type: ColumnType::Blob,
      name: $name,
      description: $description,
      serialize: $serialize,
      size: $size,
      notNull: $notNull,
      default: $default,
    );
  }

  /**
   * Returns a Column object for a generated column.
   *
   * @see https://dev.mysql.com/doc/refman/9.7/en/create-table-generated-columns.html
   * @see https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns
   * @see https://www.postgresql.org/docs/current/ddl-generated-columns.html
   * @see https://sqlite.org/gencol.html
   */
  public static function generated(
    string $name,
    ColumnType $type,
    GeneratedColumnExpression $expression,
    GeneratedColumnStorage $storage,
    ?string $description = NULL,
    ?ColumnSize $size = NULL,
    ?int $length = NULL,
    ?bool $unsigned = NULL,
    ?int $precision = NULL,
    ?int $scale = NULL,
    ?bool $binary = NULL,
    ?array $dbSpecificExtra = NULL,
  ): self {
    $column = new self(
      type: $type,
      name: $name,
      description: $description,
      size: $size,
      length: $length,
      unsigned: $unsigned,
      precision: $precision,
      scale: $scale,
      binary: $binary,
      generatedStorage: $storage,
      generatedExpression: $expression,
      dbSpecificExtra: $dbSpecificExtra,
    );
    $column->validate();
    return $column;
  }

}
