<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeFeedTest.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the node_feed() functionality.
 */
class NodeFeedTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Node feed',
      'description' => 'Ensures that node_feed() functions correctly.',
      'group' => 'Node',
   );
  }

  /**
   * Ensure that node_feed accepts and prints extra channel elements.
   */
  function testNodeFeedExtraChannelElements() {
    ob_start();
    node_feed(array(), array('copyright' => 'Drupal is a registered trademark of Dries Buytaert.'));
    $output = ob_get_clean();

    $this->assertTrue(strpos($output, '<copyright>Drupal is a registered trademark of Dries Buytaert.</copyright>') !== FALSE);
  }
}
