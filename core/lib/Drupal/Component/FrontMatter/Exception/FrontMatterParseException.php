<?php

namespace Drupal\Component\FrontMatter\Exception;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;

/**
 * Defines a class for front matter parsing exceptions.
 */
class FrontMatterParseException extends InvalidDataTypeException {

  /**
   * The line number of where the parse error occurred.
   *
   * This line number is in relation to where the parse error occurred in the
   * source front matter content. It is different from \Exception::getLine()
   * which is populated with the line number of where this exception was
   * thrown in PHP.
   *
   * @var int
   */
  protected $sourceLine;

  /**
   * Constructs a new FrontMatterParseException instance.
   *
   * @param \Drupal\Component\Serialization\Exception\InvalidDataTypeException $exception
   *   The exception thrown when attempting to parse front matter data.
   */
  public function __construct(InvalidDataTypeException $exception) {
    $this->sourceLine = 1;

    // Attempt to extract the line number from the serializer error. This isn't
    // a very stable way to do this, however it is the only way given that
    // \Drupal\Component\Serialization\SerializationInterface does not have
    // methods for accessing this kind of information reliably.
    $message = 'An error occurred when attempting to parse front matter data';
    if ($exception) {
      preg_match('/line:?\s?(\d+)/i', $exception->getMessage(), $matches);
      if (!empty($matches[1])) {
        $message .= ' on line %d';
        // Add any matching line count to the existing source line so it
        // increases it by 1 to account for the front matter separator (---).
        $this->sourceLine += (int) $matches[1];
      }
    }
    parent::__construct(sprintf($message, $this->sourceLine), 0, $exception);
  }

  /**
   * Retrieves the line number where the parse error occurred.
   *
   * This line number is in relation to where the parse error occurred in the
   * source front matter content. It is different from \Exception::getLine()
   * which is populated with the line number of where this exception was
   * thrown in PHP.
   *
   * @return int
   *   The source line number.
   */
  public function getSourceLine(): int {
    return $this->sourceLine;
  }

}
