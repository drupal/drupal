<?php

/**
 * @file
 * Contains \Drupal\overlay\Tests\OverlayCloseTest.
 */

namespace Drupal\overlay\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that the overlay can be properly closed.
 */
class OverlayCloseTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'overlay');

  public static function getInfo() {
    return array(
      'name' => 'Overlay closing functionality',
      'description' => 'Test that the overlay can be correctly closed.',
      'group' => 'Overlay',
    );
  }

  /**
   * Tests that the overlay is correctly closed after creating a node.
   */
  function testNodeCreation() {
    // Make sure the node creation page is considered an administrative path
    // (which will appear in the overlay).
    variable_set('node_admin_theme', TRUE);

    // Create a content type and a user who has permission to create it inside
    // the overlay.
    $this->drupalCreateContentType(array('type' => 'test', 'name' => 'Test content type'));
    $admin_user = $this->drupalCreateUser(array('access content', 'access overlay', 'create test content'));
    $this->drupalLogin($admin_user);

    // Create a new node, with ?render=overlay in the query parameter to
    // simulate creating it inside the overlay.
    $this->drupalPost('node/add/test', array('title' => 'Test node title'), t('Save'), array('query' => array('render' => 'overlay')));

    // Make sure a bare minimum HTML page is displayed that contains the
    // JavaScript necessary to close the overlay.
    $this->assertRaw('<body class="overlay"></body>', 'An empty body tag is present on the page request after a node is created inside the overlay.');
    $this->assertRaw('"closeOverlay":true', 'The JavaScript setting for closing the overlay is present on the page request after a node is created inside the overlay.');

    // Visit another page and make sure that we now see the message saying the
    // node was created (i.e., that it does not appear inside the overlay where
    // no one would have time to read it before the overlay closes).
    $this->drupalGet('');
    $this->assertRaw(t('!post %title has been created.', array('!post' => 'Test content type', '%title' => 'Test node title')), 'Message about the node being created is displayed on the next page request after the overlay is closed.');
  }
}
