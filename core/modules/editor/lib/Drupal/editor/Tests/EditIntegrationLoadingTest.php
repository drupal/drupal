<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\EditIntegrationLoadingTest.
 */

namespace Drupal\editor\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Edit module integration endpoints.
 */
class EditIntegrationLoadingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('edit', 'filter', 'node', 'editor');

  /**
   * The basic permissions necessary to view content and use in-place editing.
   *
   * @var array
   */
  protected static $basic_permissions = array('access content', 'create article content', 'use text format filtered_html', 'access contextual links');

  public static function getInfo() {
    return array(
      'name' => 'In-place text editor loading',
      'description' => 'Tests Edit module integration endpoints.',
      'group' => 'Text Editor',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create a text format.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(
        'filter_caption' => array(
          'status' => 1,
        ),
      ),
    ));
    $filtered_html_format->save();

    // Create a node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    // Create one node of the above node type using the above text format.
    $this->drupalCreateNode(array(
      'type' => 'article',
      'body' => array(
        0 => array(
          'value' => '<p>Do you also love Drupal?</p><img src="druplicon.png" data-caption="Druplicon" />',
          'format' => 'filtered_html',
        )
      )
    ));
  }

  /**
   * Test loading of untransformed text when a user doesn't have access to it.
   */
  public function testUsersWithoutPermission() {
    // Create 3 users, each with insufficient permissions, i.e. without either
    // or both of the following permissions:
    // - the 'access in-place editing' permission
    // - the 'edit any article content' permission (necessary to edit node 1)
    $users = array(
      $this->drupalCreateUser(static::$basic_permissions),
      $this->drupalCreateUser(array_merge(static::$basic_permissions, array('edit any article content'))),
      $this->drupalCreateUser(array_merge(static::$basic_permissions, array('access in-place editing')))
    );

    // Now test with each of the 3 users with insufficient permissions.
    foreach ($users as $user) {
      $this->drupalLogin($user);
      $this->drupalGet('node/1');

      // Ensure the text is transformed.
      $this->assertRaw('<p>Do you also love Drupal?</p><figure class="caption caption-img"><img src="druplicon.png" /><figcaption>Druplicon</figcaption></figure>');

      // Retrieving the untransformed text should result in an empty 403 response.
      $response = $this->drupalPost('editor/' . 'node/1/body/und/full', 'application/vnd.drupal-ajax', array());
      $this->assertResponse(403);
      // @todo Uncomment the below once https://drupal.org/node/2063303 is fixed.
      // $this->assertIdentical('[]', $response);
    }
  }

  /**
   * Test loading of untransformed text when a user does have access to it.
   */
  public function testUserWithPermission() {
    $user = $this->drupalCreateUser(array_merge(static::$basic_permissions, array('edit any article content', 'access in-place editing')));
    $this->drupalLogin($user);
    $this->drupalGet('node/1');

    // Ensure the text is transformed.
    $this->assertRaw('<p>Do you also love Drupal?</p><figure class="caption caption-img"><img src="druplicon.png" /><figcaption>Druplicon</figcaption></figure>');

    $response = $this->drupalPost('editor/' . 'node/1/body/und/full', 'application/vnd.drupal-ajax', array());
    $this->assertResponse(200);
    $ajax_commands = drupal_json_decode($response);
    $this->assertIdentical(1, count($ajax_commands), 'The untransformed text POST request results in one AJAX command.');
    $this->assertIdentical('editorGetUntransformedText', $ajax_commands[0]['command'], 'The first AJAX command is an editorGetUntransformedText command.');
    $this->assertIdentical('<p>Do you also love Drupal?</p><img src="druplicon.png" data-caption="Druplicon" />', $ajax_commands[0]['data'], 'The editorGetUntransformedText command contains the expected data.');
  }

}
