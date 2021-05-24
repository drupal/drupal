<?php

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LogMessageParser;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Logger\LogMessageParser
 * @group Logger
 */
class LogMessageParserTest extends UnitTestCase {

  /**
   * Tests for LogMessageParserTrait::parseMessagePlaceholders()
   *
   * @param array $value
   *   An array containing:
   *    - message: A string that contains a message with placeholders.
   *    - context: An array with placeholder values.
   * @param array $expected
   *   An array with the expected values after the test has run.
   *    - message: The expected parsed message.
   *    - context: The expected values of the placeholders.
   *
   * @dataProvider providerTestParseMessagePlaceholders
   * @covers ::parseMessagePlaceholders
   */
  public function testParseMessagePlaceholders(array $value, array $expected) {
    $parser = new LogMessageParser();
    $message_placeholders = $parser->parseMessagePlaceholders($value['message'], $value['context']);
    $this->assertEquals($expected['message'], $value['message']);
    $this->assertEquals($expected['context'], $message_placeholders);
  }

  /**
   * Data provider for testParseMessagePlaceholders().
   */
  public function providerTestParseMessagePlaceholders() {
    return [
      // PSR3 only message.
      [
        ['message' => 'User {username} created', 'context' => ['username' => 'Dries']],
        ['message' => 'User @username created', 'context' => ['@username' => 'Dries']],
      ],
      // PSR3 style mixed in a format_string style message.
      [
        ['message' => 'User {username} created @time', 'context' => ['username' => 'Dries', '@time' => 'now']],
        ['message' => 'User @username created @time', 'context' => ['@username' => 'Dries', '@time' => 'now']],
      ],
      // format_string style message only.
      [
        ['message' => 'User @username created', 'context' => ['@username' => 'Dries']],
        ['message' => 'User @username created', 'context' => ['@username' => 'Dries']],
      ],
      // Message without placeholders but wildcard characters.
      [
        ['message' => 'User W-\\};~{&! created @', 'context' => ['' => '']],
        ['message' => 'User W-\\};~{&! created @', 'context' => []],
      ],
      // Message with double PSR3 style messages.
      [
        ['message' => 'Test {with} two {encapsuled} strings', 'context' => ['with' => 'together', 'encapsuled' => 'awesome']],
        ['message' => 'Test @with two @encapsuled strings', 'context' => ['@with' => 'together', '@encapsuled' => 'awesome']],
      ],
    ];
  }

}
