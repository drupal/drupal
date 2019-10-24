<?php

namespace Drupal\help_topics;

/**
 * Extracts Front Matter from the beginning of a source.
 *
 * @internal
 *   This front matter extractor only supports help topic discovery and is not
 *   part of the public API.
 */
final class FrontMatter {

  /**
   * The separator used to indicate front matter data.
   *
   * @var string
   */
  const FRONT_MATTER_SEPARATOR = '---';

  /**
   * The regular expression used to extract the YAML front matter content.
   *
   * @var string
   */
  const FRONT_MATTER_REGEXP = "{^(?:" . self::FRONT_MATTER_SEPARATOR . ")[\r\n|\n]*(.*?)[\r\n|\n]+(?:" . self::FRONT_MATTER_SEPARATOR . ")[\r\n|\n]*(.*)$}s";

  /**
   * The parsed source.
   *
   * @var array
   */
  protected $parsed;

  /**
   * A serializer class.
   *
   * @var string
   */
  protected $serializer;

  /**
   * The source.
   *
   * @var string
   */
  protected $source;

  /**
   * FrontMatter constructor.
   *
   * @param string $source
   *   A string source.
   * @param string $serializer
   *   A class that implements
   *   \Drupal\Component\Serialization\SerializationInterface.
   */
  public function __construct($source, $serializer = '\Drupal\Component\Serialization\Yaml') {
    assert(is_string($source), '$source must be a string');
    assert(is_string($serializer), '$serializer must be a string');
    if (!is_subclass_of($serializer, '\Drupal\Component\Serialization\SerializationInterface')) {
      throw new \InvalidArgumentException('The $serializer parameter must reference a class that implements \Drupal\Component\Serialization\SerializationInterface.');
    }
    $this->serializer = $serializer;
    $this->source = $source;
  }

  /**
   * Creates a new FrontMatter instance.
   *
   * @param string $source
   *   A string source.
   * @param string $serializer
   *   A class that implements
   *   \Drupal\Component\Serialization\SerializationInterface.
   *
   * @return static
   */
  public static function load($source, $serializer = '\Drupal\Component\Serialization\Yaml') {
    return new static($source, $serializer);
  }

  /**
   * Parses the source.
   *
   * @return array
   *   An associative array containing:
   *   - code: The real source code.
   *   - data: The front matter data extracted and decoded.
   *   - line: The line number where the real source code starts.
   *
   * @throws \Drupal\Component\Serialization\Exception\InvalidDataTypeException
   *   Exception thrown when the Front Matter cannot be parsed.
   */
  private function parse() {
    if (!$this->parsed) {
      $this->parsed = [
        'code' => $this->source,
        'data' => [],
        'line' => 1,
      ];

      // Check for front matter data.
      $len = strlen(static::FRONT_MATTER_SEPARATOR);
      $matches = [];
      if (substr($this->parsed['code'], 0, $len + 1) === static::FRONT_MATTER_SEPARATOR . "\n" || substr($this->parsed['code'], 0, $len + 2) === static::FRONT_MATTER_SEPARATOR . "\r\n") {
        preg_match(static::FRONT_MATTER_REGEXP, $this->parsed['code'], $matches);
        $matches = array_map('trim', $matches);
      }

      // Immediately return if the code doesn't contain front matter data.
      if (empty($matches)) {
        return $this->parsed;
      }

      // Set the extracted source code.
      $this->parsed['code'] = $matches[2];

      // Set the extracted front matter data. Do not catch any exceptions here
      // as doing so would only obfuscate any errors found in the front matter
      // data. Typecast to an array to ensure top level scalars are in an array.
      if ($matches[1]) {
        $this->parsed['data'] = (array) $this->serializer::decode($matches[1]);
      }

      // Determine the real source line by counting newlines from the data and
      // then adding 2 to account for the front matter separator (---) wrappers
      // and then adding 1 more for the actual line number after the data.
      $this->parsed['line'] = count(preg_split('/\r\n|\n/', $matches[1])) + 3;
    }
    return $this->parsed;
  }

  /**
   * Retrieves the extracted source code.
   *
   * @return string
   *   The extracted source code.
   *
   * @throws \Drupal\Component\Serialization\Exception\InvalidDataTypeException
   *   Exception thrown when the Front Matter cannot be parsed.
   */
  public function getCode() {
    return $this->parse()['code'];
  }

  /**
   * Retrieves the extracted front matter data.
   *
   * @return array
   *   The extracted front matter data.
   *
   * @throws \Drupal\Component\Serialization\Exception\InvalidDataTypeException
   *   Exception thrown when the Front Matter cannot be parsed.
   */
  public function getData() {
    return $this->parse()['data'];
  }

  /**
   * Retrieves the line where the source code starts, after any data.
   *
   * @return int
   *   The source code line.
   *
   * @throws \Drupal\Component\Serialization\Exception\InvalidDataTypeException
   *   Exception thrown when the Front Matter cannot be parsed.
   */
  public function getLine() {
    return $this->parse()['line'];
  }

}
