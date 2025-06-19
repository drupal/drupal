<?php

declare(strict_types=1);

namespace Drupal\mysqli\Driver\Database\mysqli;

// cspell:ignore DBAL MULTICHAR

/**
 * A class to convert a SQL statement with named placeholders to positional.
 *
 * The parsing logic and the implementation is inspired by the PHP PDO parser,
 * and a simplified copy of the parser implementation done by the Doctrine DBAL
 * project.
 *
 * This class is a near-copy of Doctrine\DBAL\SQL\Parser, which is part of the
 * Doctrine project: <http://www.doctrine-project.org>. It was copied from
 * version 4.0.0.
 *
 * Original copyright:
 *
 * Copyright (c) 2006-2018 Doctrine Project
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * @see https://github.com/doctrine/dbal/blob/4.0.0/src/SQL/Parser.php
 *
 * @internal
 */
final class NamedPlaceholderConverter {
  /**
   * A list of regex patterns for parsing.
   */
  private const string SPECIAL_CHARS = ':\?\'"`\\[\\-\\/';
  private const string BACKTICK_IDENTIFIER = '`[^`]*`';
  private const string BRACKET_IDENTIFIER = '(?<!\b(?i:ARRAY))\[(?:[^\]])*\]';
  private const string MULTICHAR = ':{2,}';
  private const string NAMED_PARAMETER = ':[a-zA-Z0-9_]+';
  private const string POSITIONAL_PARAMETER = '(?<!\\?)\\?(?!\\?)';
  private const string ONE_LINE_COMMENT = '--[^\r\n]*';
  private const string MULTI_LINE_COMMENT = '/\*([^*]+|\*+[^/*])*\**\*/';
  private const string SPECIAL = '[' . self::SPECIAL_CHARS . ']';
  private const string OTHER = '[^' . self::SPECIAL_CHARS . ']+';

  /**
   * The combined regex pattern for parsing.
   */
  private string $sqlPattern;

  /**
   * The list of original named arguments.
   *
   * The initial placeholder colon is removed.
   *
   * @var array<string|int, mixed>
   */
  private array $originalParameters = [];

  /**
   * The maximum positional placeholder parsed.
   *
   * Normally Drupal does not produce SQL with positional placeholders, but
   * this is to manage the edge case.
   */
  private int $originalParameterIndex = 0;

  /**
   * The converted SQL statement in its parts.
   *
   * @var list<string>
   */
  private array $convertedSQL = [];

  /**
   * The list of converted arguments.
   *
   * @var list<mixed>
   */
  private array $convertedParameters = [];

  public function __construct() {
    // Builds the combined regex pattern for parsing.
    $this->sqlPattern = sprintf('(%s)', implode('|', [
      $this->getAnsiSQLStringLiteralPattern("'"),
      $this->getAnsiSQLStringLiteralPattern('"'),
      self::BACKTICK_IDENTIFIER,
      self::BRACKET_IDENTIFIER,
      self::MULTICHAR,
      self::ONE_LINE_COMMENT,
      self::MULTI_LINE_COMMENT,
      self::OTHER,
    ]));
  }

  /**
   * Parses an SQL statement with named placeholders.
   *
   * This method explodes the SQL statement in parts that can be reassembled
   * into a string with positional placeholders.
   *
   * @param string $sql
   *   The SQL statement with named placeholders.
   * @param array<string|int, mixed> $args
   *   The statement arguments.
   */
  public function parse(string $sql, array $args): void {
    // Reset the object state.
    $this->originalParameters = [];
    $this->originalParameterIndex = 0;
    $this->convertedSQL = [];
    $this->convertedParameters = [];

    foreach ($args as $key => $value) {
      if (is_int($key)) {
        // Positional placeholder; edge case.
        $this->originalParameters[$key] = $value;
      }
      else {
        // Named placeholder like ':placeholder'; remove the initial colon.
        $parameter = $key[0] === ':' ? substr($key, 1) : $key;
        $this->originalParameters[$parameter] = $value;
      }
    }

    /** @var array<string,callable> $patterns */
    $patterns = [
      self::NAMED_PARAMETER => function (string $sql): void {
        $this->addNamedParameter($sql);
      },
      self::POSITIONAL_PARAMETER => function (string $sql): void {
        $this->addPositionalParameter($sql);
      },
      $this->sqlPattern => function (string $sql): void {
        $this->addOther($sql);
      },
      self::SPECIAL => function (string $sql): void {
        $this->addOther($sql);
      },
    ];

    $offset = 0;

    while (($handler = current($patterns)) !== FALSE) {
      if (preg_match('~\G' . key($patterns) . '~s', $sql, $matches, 0, $offset) === 1) {
        $handler($matches[0]);
        reset($patterns);
        $offset += strlen($matches[0]);
      }
      elseif (preg_last_error() !== PREG_NO_ERROR) {
        throw new \RuntimeException('Regular expression error');
      }
      else {
        next($patterns);
      }
    }

    assert($offset === strlen($sql));
  }

  /**
   * Helper to return a regex pattern from a delimiter character.
   *
   * @param string $delimiter
   *   A delimiter character.
   *
   * @return string
   *   The regex pattern.
   */
  private function getAnsiSQLStringLiteralPattern(string $delimiter): string {
    return $delimiter . '[^' . $delimiter . ']*' . $delimiter;
  }

  /**
   * Adds a positional placeholder to the converted parts.
   *
   * Normally Drupal does not produce SQL with positional placeholders, but
   * this is to manage the edge case.
   *
   * @param string $sql
   *   The SQL part.
   */
  private function addPositionalParameter(string $sql): void {
    $index = $this->originalParameterIndex;

    if (!array_key_exists($index, $this->originalParameters)) {
      throw new \RuntimeException('Missing Positional Parameter ' . $index);
    }

    $this->convertedSQL[] = '?';
    $this->convertedParameters[] = $this->originalParameters[$index];

    $this->originalParameterIndex++;
  }

  /**
   * Adds a named placeholder to the converted parts.
   *
   * @param string $sql
   *   The SQL part.
   */
  private function addNamedParameter(string $sql): void {
    $name = substr($sql, 1);

    if (!array_key_exists($name, $this->originalParameters)) {
      throw new \RuntimeException('Missing Named Parameter ' . $name);
    }

    $this->convertedSQL[] = '?';
    $this->convertedParameters[] = $this->originalParameters[$name];
  }

  /**
   * Adds a generic SQL string fragment to the converted parts.
   *
   * @param string $sql
   *   The SQL part.
   */
  private function addOther(string $sql): void {
    $this->convertedSQL[] = $sql;
  }

  /**
   * Returns the converted SQL statement with positional placeholders.
   *
   * @return string
   *   The converted SQL statement with positional placeholders.
   */
  public function getConvertedSQL(): string {
    return implode('', $this->convertedSQL);
  }

  /**
   * Returns the array of arguments for use with positional placeholders.
   *
   * @return list<mixed>
   *   The array of arguments for use with positional placeholders.
   */
  public function getConvertedParameters(): array {
    return $this->convertedParameters;
  }

}
