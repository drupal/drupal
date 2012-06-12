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
  public static function getInfo() {
    return array(
      'name'  => 'XML-RPC message and alteration',
      'description' => 'Test large messages and method alterations.',
      'group' => 'XML-RPC',
    );
  }

  function setUp() {
    parent::setUp('xmlrpc', 'xmlrpc_test');
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

      $this->assertEqual($xml_message_l, $xml_message_r, t('XML-RPC messages.messageSizedInKB of %s Kb size received', array('%s' => $size)));
    }
  }

  /**
   * Ensure that hook_xmlrpc_alter() can hide even builtin methods.
   */
  protected function testAlterListMethods() {
    // Ensure xmlrpc_test_xmlrpc_alter() is disabled and retrieve regular list of methods.
    variable_set('xmlrpc_test_xmlrpc_alter', FALSE);
    $url = url('xmlrpc.php', array('absolute' => TRUE));
    $methods1 = xmlrpc($url, array('system.listMethods' => array()));

    // Enable the alter hook and retrieve the list of methods again.
    variable_set('xmlrpc_test_xmlrpc_alter', TRUE);
    $methods2 = xmlrpc($url, array('system.listMethods' => array()));

    $diff = array_diff($methods1, $methods2);
    $this->assertTrue(is_array($diff) && !empty($diff), t('Method list is altered by hook_xmlrpc_alter'));
    $removed = reset($diff);
    $this->assertEqual($removed, 'system.methodSignature', t('Hiding builting system.methodSignature with hook_xmlrpc_alter works'));
  }

}
