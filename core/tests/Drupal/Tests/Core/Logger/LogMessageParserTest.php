<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Logger\LogMessageParserTest.
 */

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LogMessageParser;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Logger\LogMessageParser
 * @group Logger
 */
class LogMessageParserTest extends UnitTestCase {

  /**
   * Test for LogMessageParserTrait::parseMessagePlaceholders()
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
    return array(
      // PSR3 only message.
      array(
        array('message' => 'User {username} created', 'context' => array('username' => 'Dries')),
        array('message' => 'User @username created', 'context' => array('@username' => 'Dries')),
      ),
      // PSR3 style mixed in a format_string style message.
      array(
        array('message' => 'User {username} created @time', 'context' => array('username' => 'Dries', '@time' => 'now')),
        array('message' => 'User @username created @time', 'context' => array('@username' => 'Dries', '@time' => 'now')),
      ),
      // format_string style message only.
      array(
        array('message' => 'User @username created', 'context' => array('@username' => 'Dries')),
        array('message' => 'User @username created', 'context' => array('@username' => 'Dries')),
      ),
      // Message without placeholders but wildcard characters.
      array(
        array('message' => 'User W-\\};~{&! created @', 'context' => array('' => '')),
        array('message' => 'User W-\\};~{&! created @', 'context' => array()),
      ),
      // Message with double PSR3 style messages.
      array(
        array('message' => 'Test {with} two {encapsuled} strings', 'context' => array('with' => 'together', 'encapsuled' => 'awesome')),
        array('message' => 'Test @with two @encapsuled strings', 'context' => array('@with' => 'together', '@encapsuled' => 'awesome')),
      ),
    );
  }

}
