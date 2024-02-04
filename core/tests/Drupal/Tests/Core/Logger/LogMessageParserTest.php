<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Logger;

use Drupal\Component\Render\FormattableMarkup;
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
      'PSR3-style placeholder' => [
        ['message' => 'User {username} created', 'context' => ['username' => 'Dries']],
        ['message' => 'User @username created', 'context' => ['@username' => 'Dries']],
      ],
      'PSR3- and FormattableMarkup-style placeholders' => [
        ['message' => 'User {username} created @time', 'context' => ['username' => 'Dries', '@time' => 'now']],
        ['message' => 'User @username created @time', 'context' => ['@username' => 'Dries', '@time' => 'now']],
      ],
      'FormattableMarkup-style placeholder' => [
        ['message' => 'User @username created', 'context' => ['@username' => 'Dries']],
        ['message' => 'User @username created', 'context' => ['@username' => 'Dries']],
      ],
      'Wildcard characters' => [
        ['message' => 'User W-\\};~{&! created @', 'context' => ['' => '']],
        ['message' => 'User W-\\};~{&! created @', 'context' => []],
      ],
      'Multiple PSR3-style placeholders' => [
        ['message' => 'Test {with} two {{encapsuled}} strings', 'context' => ['with' => 'together', 'encapsuled' => 'awesome']],
        ['message' => 'Test @with two {@encapsuled} strings', 'context' => ['@with' => 'together', '@encapsuled' => 'awesome']],
      ],
      'Disallowed placeholder' => [
        ['message' => 'Test placeholder with :url and old !bang parameter', 'context' => [':url' => 'https://example.com', '!bang' => 'foo bar']],
        ['message' => 'Test placeholder with :url and old !bang parameter', 'context' => [':url' => 'https://example.com']],
      ],
      'Stringable object placeholder' => [
        ['message' => 'object @b', 'context' => ['@b' => new FormattableMarkup('convertible', [])]],
        ['message' => 'object @b', 'context' => ['@b' => 'convertible']],
      ],
      'Non-placeholder context value' => [
        ['message' => 'message', 'context' => ['not_a_placeholder' => new \stdClass()]],
        ['message' => 'message', 'context' => []],
      ],
      'Non-stringable array placeholder' => [
        ['message' => 'array @a', 'context' => ['@a' => []]],
        ['message' => 'array @a', 'context' => []],
      ],
      'Non-stringable object placeholder' => [
        ['message' => 'object @b', 'context' => ['@b' => new \stdClass()]],
        ['message' => 'object @b', 'context' => []],
      ],
      'Non-stringable closure placeholder' => [
        ['message' => 'closure @c', 'context' => ['@c' => function () {}]],
        ['message' => 'closure @c', 'context' => []],
      ],
      'Non-stringable resource placeholder' => [
        ['message' => 'resource @r', 'context' => ['@r' => fopen('php://memory', 'r+')]],
        ['message' => 'resource @r', 'context' => []],
      ],
      'Non-stringable placeholder is not the first placeholder' => [
        ['message' => 'mixed @a @b @c', 'context' => ['@a' => 123, '@b' => [1], '@c' => TRUE]],
        ['message' => 'mixed @a @b @c', 'context' => ['@a' => 123, '@c' => TRUE]],
      ],
      'NULL and Boolean placeholders are considered stringable' => [
        ['message' => 'mixed @a @b @c', 'context' => ['@a' => NULL, '@b' => TRUE, '@c' => FALSE]],
        ['message' => 'mixed @a @b @c', 'context' => ['@a' => NULL, '@b' => TRUE, '@c' => FALSE]],
      ],
    ];
  }

}
