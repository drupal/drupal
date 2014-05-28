<?php

/**
 * @file
 * Definition of Drupal\xmlrpc\Tests\XmlRpcBasicTest.
 */

namespace Drupal\xmlrpc\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Perform basic XML-RPC tests that do not require addition callbacks.
 */
class XmlRpcBasicTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('xmlrpc');

  public static function getInfo() {
    return array(
      'name'  => 'XML-RPC basic',
      'description'  => 'Perform basic XML-RPC tests that do not require additional callbacks.',
      'group' => 'XML-RPC',
    );
  }

  /**
   * Ensure that a basic XML-RPC call with no parameters works.
   */
  protected function testListMethods() {
    // Minimum list of methods that should be included.
    $minimum = array(
      'system.multicall',
      'system.methodSignature',
      'system.getCapabilities',
      'system.listMethods',
      'system.methodHelp',
    );

    // Invoke XML-RPC call to get list of methods.
    $url = url('xmlrpc.php', array('absolute' => TRUE));
    $methods = xmlrpc($url, array('system.listMethods' => array()));

    // Ensure that the minimum methods were found.
    $count = 0;
    foreach ($methods as $method) {
      if (in_array($method, $minimum)) {
        $count++;
      }
    }

    $this->assertEqual($count, count($minimum), 'system.listMethods returned at least the minimum listing');
  }

  /**
   * Ensure that system.methodSignature returns an array of signatures.
   */
  protected function testMethodSignature() {
    $url = url('xmlrpc.php', array('absolute' => TRUE));
    $signature = xmlrpc($url, array('system.methodSignature' => array('system.listMethods')));
    $this->assert(is_array($signature) && !empty($signature) && is_array($signature[0]),
      'system.methodSignature returns an array of signature arrays.');
  }

  /**
   * Ensure that XML-RPC correctly handles invalid messages when parsing.
   */
  protected function testInvalidMessageParsing() {
    $invalid_messages = array(
      array(
        'message' => xmlrpc_message(''),
        'assertion' => 'Empty message correctly rejected during parsing.',
      ),
      array(
        'message' => xmlrpc_message('<?xml version="1.0" encoding="ISO-8859-1"?>'),
        'assertion' => 'Empty message with XML declaration correctly rejected during parsing.',
      ),
      array(
        'message' => xmlrpc_message('<?xml version="1.0"?><params><param><value><string>value</string></value></param></params>'),
        'assertion' => 'Non-empty message without a valid message type is rejected during parsing.',
      ),
      array(
        'message' => xmlrpc_message('<methodResponse><params><param><value><string>value</string></value></param></methodResponse>'),
        'assertion' => 'Non-empty malformed message is rejected during parsing.',
      ),
    );

    foreach ($invalid_messages as $assertion) {
      $this->assertFalse(xmlrpc_message_parse($assertion['message']), $assertion['assertion']);
    }
  }
}
