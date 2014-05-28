<?php

/**
 * @file
 * Definition of Drupal\xmlrpc\Tests\XmlRpcMessagesTest.
 */

namespace Drupal\xmlrpc\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * XML-RPC message and alteration tests.
 */
class XmlRpcMessagesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('xmlrpc', 'xmlrpc_test');

  public static function getInfo() {
    return array(
      'name'  => 'XML-RPC message and alteration',
      'description' => 'Test large messages and method alterations.',
      'group' => 'XML-RPC',
    );
  }

  /**
   * Make sure that XML-RPC can transfer large messages.
   */
  function testSizedMessages() {
    $xml_url = url('xmlrpc.php', array('absolute' => TRUE));
    $sizes = array(8, 80, 160);
    foreach ($sizes as $size) {
      $xml_message_l = xmlrpc_test_message_sized_in_kb($size);
      $xml_message_r = xmlrpc($xml_url, array('messages.messageSizedInKB' => array($size)));

      $this->assertEqual($xml_message_l, $xml_message_r, format_string('XML-RPC messages.messageSizedInKB of %s Kb size received', array('%s' => $size)));
    }
  }

  /**
   * Ensure that hook_xmlrpc_alter() can hide even builtin methods.
   */
  protected function testAlterListMethods() {
    // Ensure xmlrpc_test.alter() is disabled and retrieve regular list of methods.
    \Drupal::state()->set('xmlrpc_test.alter', FALSE);
    $url = url('xmlrpc.php', array('absolute' => TRUE));
    $methods1 = xmlrpc($url, array('system.listMethods' => array()));

    // Enable the alter hook and retrieve the list of methods again.
    \Drupal::state()->set('xmlrpc_test.alter', TRUE);
    $methods2 = xmlrpc($url, array('system.listMethods' => array()));

    $diff = array_diff($methods1, $methods2);
    $this->assertTrue(is_array($diff) && !empty($diff), 'Method list is altered by hook_xmlrpc_alter');
    $removed = reset($diff);
    $this->assertEqual($removed, 'system.methodSignature', 'Hiding builting system.methodSignature with hook_xmlrpc_alter works');
  }

}
