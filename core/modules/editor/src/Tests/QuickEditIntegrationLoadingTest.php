<?php

namespace Drupal\editor\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\simpletest\WebTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests Quick Edit module integration endpoints.
 *
 * @group editor
 */
class QuickEditIntegrationLoadingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['quickedit', 'filter', 'node', 'editor'];

  /**
   * The basic permissions necessary to view content and use in-place editing.
   *
   * @var array
   */
  protected static $basicPermissions = ['access content', 'create article content', 'use text format filtered_html', 'access contextual links'];

  protected function setUp() {
    parent::setUp();

    // Create a text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [
        'filter_caption' => [
          'status' => 1,
        ],
      ],
    ]);
    $filtered_html_format->save();

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Create one node of the above node type using the above text format.
    $this->drupalCreateNode([
      'type' => 'article',
      'body' => [
        0 => [
          'value' => '<p>Do you also love Drupal?</p><img src="druplicon.png" data-caption="Druplicon" />',
          'format' => 'filtered_html',
        ]
      ]
    ]);
  }

  /**
   * Test loading of untransformed text when a user doesn't have access to it.
   */
  public function testUsersWithoutPermission() {
    // Create 3 users, each with insufficient permissions, i.e. without either
    // or both of the following permissions:
    // - the 'access in-place editing' permission
    // - the 'edit any article content' permission (necessary to edit node 1)
    $users = [
      $this->drupalCreateUser(static::$basicPermissions),
      $this->drupalCreateUser(array_merge(static::$basicPermissions, ['edit any article content'])),
      $this->drupalCreateUser(array_merge(static::$basicPermissions, ['access in-place editing']))
    ];

    // Now test with each of the 3 users with insufficient permissions.
    foreach ($users as $user) {
      $this->drupalLogin($user);
      $this->drupalGet('node/1');

      // Ensure the text is transformed.
      $this->assertRaw('<p>Do you also love Drupal?</p><figure role="group" class="caption caption-img"><img src="druplicon.png" /><figcaption>Druplicon</figcaption></figure>');

      // Retrieving the untransformed text should result in an 403 response and
      // return a different error message depending of the missing permission.
      $response = $this->drupalPost('editor/' . 'node/1/body/en/full', '', [], ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $this->assertResponse(403);
      if (!$user->hasPermission('access in-place editing')) {
        $message = "The 'access in-place editing' permission is required.";
      }
      else {
        $message = '';
      }
      $this->assertIdentical(Json::encode(['message' => $message]), $response);
    }
  }

  /**
   * Test loading of untransformed text when a user does have access to it.
   */
  public function testUserWithPermission() {
    $user = $this->drupalCreateUser(array_merge(static::$basicPermissions, ['edit any article content', 'access in-place editing']));
    $this->drupalLogin($user);
    $this->drupalGet('node/1');

    // Ensure the text is transformed.
    $this->assertRaw('<p>Do you also love Drupal?</p><figure role="group" class="caption caption-img"><img src="druplicon.png" /><figcaption>Druplicon</figcaption></figure>');

    $response = $this->drupalPost('editor/' . 'node/1/body/en/full', '', [], ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $this->assertResponse(200);
    $ajax_commands = Json::decode($response);
    $this->assertIdentical(1, count($ajax_commands), 'The untransformed text POST request results in one AJAX command.');
    $this->assertIdentical('editorGetUntransformedText', $ajax_commands[0]['command'], 'The first AJAX command is an editorGetUntransformedText command.');
    $this->assertIdentical('<p>Do you also love Drupal?</p><img src="druplicon.png" data-caption="Druplicon" />', $ajax_commands[0]['data'], 'The editorGetUntransformedText command contains the expected data.');
  }

}
