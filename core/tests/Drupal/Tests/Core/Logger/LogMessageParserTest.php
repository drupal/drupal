<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Logger\LogMessageParserTraitTest
 */

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LogMessageParser;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the log message parser.
 *
 * @see \Drupal\Core\Logger\LogMessageParser
 * @coversDefaultClass \Drupal\Core\Logger\LogMessageParser
 *
 * @group Drupal
 * @group Logger
 */
class LogMessageParserTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Log message parser',
      'description' => 'Unit tests for the log message parser.',
      'group' => 'Logger',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->parser = new LogMessageParser();
  }

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
    $message_placeholders = $this->parser->parseMessagePlaceholders($value['message'], $value['context']);
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
      // Messsage without placeholders but wildcard characters.
      array(
        array('message' => 'User W-\\};~{&! created @', 'context' => array('' => '')),
        array('message' => 'User W-\\};~{&! created @', 'context' => array()),
      ),
      // Messsage with double PSR3 style messages.
      array(
        array('message' => 'Test {with} two {encapsuled} strings', 'context' => array('with' => 'together', 'encapsuled' => 'awesome')),
        array('message' => 'Test @with two @encapsuled strings', 'context' => array('@with' => 'together', '@encapsuled' => 'awesome')),
      ),
    );
  }

}
