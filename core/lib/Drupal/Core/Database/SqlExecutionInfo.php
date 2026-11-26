<?php

namespace Drupal\Core\Database;

/**
 * Value object carrying information pertaining to a SQL execution.
 */
class SqlExecutionInfo {

  /**
   * Constructor.
   *
   * @param string $sqlQuery
   *   The SQL query string.
   * @param array<int|string,mixed> $placeholders
   *   (optional) The placeholder values, keyed by placeholder name for a
   *   string containing named placeholders, 0-indexed for a string containing
   *   positional placeholders.
   * @param array<string,mixed> $options
   *   An associative array of options to control how the database operation is
   *   run.
   */
  public function __construct(
    public readonly string $sqlQuery,
    public readonly array $placeholders = [],
    public readonly array $options = [],
  ) {
  }

  /**
   * Returns a compact single line representation of the SQL execution.
   *
   * This is meant for the exception message, which ends up in logs, in
   * error_log() output and in CLI applications rendering the throwable
   * themselves. Runs of whitespace in the query are collapsed and the
   * placeholder values are escaped, so that the result stays on one line.
   * The options are not included; consumers can read them from this object.
   *
   * @return string
   *   The SQL query followed by its placeholder values.
   */
  public function toCompactString(): string {
    $placeholders = [];
    foreach ($this->placeholders as $key => $value) {
      $placeholders[] = $key . '=' . self::formatValue($value);
    }
    return preg_replace('/\s+/', ' ', trim($this->sqlQuery)) . '; [' . implode(', ', $placeholders) . ']';
  }

  /**
   * Appends the compact representation to an exception message.
   *
   * @param string $message
   *   The exception message.
   *
   * @return string
   *   The message, suffixed with ::toCompactString().
   */
  public function appendToMessage(string $message): string {
    return $message . ': ' . $this->toCompactString();
  }

  /**
   * Formats a single placeholder value for ::toCompactString().
   *
   * @param mixed $value
   *   The placeholder value.
   *
   * @return string
   *   The value as a single line string. Values that are not scalars are
   *   represented by their type only.
   */
  private static function formatValue(mixed $value): string {
    return match (TRUE) {
      $value === NULL => 'NULL',
      is_bool($value) => $value ? 'TRUE' : 'FALSE',
      is_string($value) => "'" . strtr($value, ["\n" => '\n', "\r" => '\r']) . "'",
      is_int($value) || is_float($value) => (string) $value,
      default => get_debug_type($value),
    };
  }

}
