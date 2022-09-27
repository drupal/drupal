<?php

namespace Drupal\Tests\quickedit\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Quick Edit module integration endpoints.
 *
 * @group quickedit
 * @group legacy
 */
class EditorIntegrationLoadingTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['quickedit', 'filter', 'node', 'editor'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * The basic permissions necessary to view content and use in-place editing.
   *
   * @var array
   */
  protected static $basicPermissions = ['access content', 'create article content', 'use text format filtered_html', 'access contextual links'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
        ],
      ],
    ]);
  }

  /**
   * Tests loading of untransformed text when a user doesn't have access to it.
   */
  public function testUsersWithoutPermission() {
    // Create 3 users, each with insufficient permissions, i.e. without either
    // or both of the following permissions:
    // - the 'access in-place editing' permission
    // - the 'edit any article content' permission (necessary to edit node 1)
    $users = [
      $this->drupalCreateUser(static::$basicPermissions),
      $this->drupalCreateUser(array_merge(static::$basicPermissions, ['edit any article content'])),
      $this->drupalCreateUser(array_merge(static::$basicPermissions, ['access in-place editing'])),
    ];

    // Now test with each of the 3 users with insufficient permissions.
    foreach ($users as $user) {
      $this->drupalLogin($user);
      $this->drupalGet('node/1');

      // Ensure the text is transformed.
      $this->assertSession()->responseContains('<p>Do you also love Drupal?</p><figure role="group" class="caption caption-img"><img src="druplicon.png" /><figcaption>Druplicon</figcaption></figure>');

      $client = $this->getHttpClient();

      // Retrieving the untransformed text should result in a 403 response and
      // return a different error message depending of the missing permission.
      $response = $client->post($this->buildUrl('quickedit/node/1/body/en/full'), [
        'query' => http_build_query([MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']),
        'cookies' => $this->getSessionCookies(),
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'http_errors' => FALSE,
      ]);

      $this->assertEquals(403, $response->getStatusCode());
      if (!$user->hasPermission('access in-place editing')) {
        $message = "The 'access in-place editing' permission is required.";
      }
      else {
        $message = "The 'edit any article content' permission is required.";
      }

      $body = Json::decode($response->getBody());
      $this->assertSame($message, $body['message']);
    }
  }

  /**
   * Tests loading of untransformed text when a user does have access to it.
   */
  public function testUserWithPermission() {
    $user = $this->drupalCreateUser(array_merge(static::$basicPermissions, ['edit any article content', 'access in-place editing']));
    $this->drupalLogin($user);
    $this->drupalGet('node/1');

    // Ensure the text is transformed.
    $this->assertSession()->responseContains('<p>Do you also love Drupal?</p><figure role="group" class="caption caption-img"><img src="druplicon.png" /><figcaption>Druplicon</figcaption></figure>');
    $client = $this->getHttpClient();
    $response = $client->post($this->buildUrl('quickedit/node/1/body/en/full'), [
      'query' => http_build_query([MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']),
      'cookies' => $this->getSessionCookies(),
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ]);

    $this->assertEquals(200, $response->getStatusCode());
    $ajax_commands = Json::decode($response->getBody());
    $this->assertCount(1, $ajax_commands, 'The untransformed text POST request results in one AJAX command.');
    $this->assertSame('editorGetUntransformedText', $ajax_commands[0]['command'], 'The first AJAX command is an editorGetUntransformedText command.');
    $this->assertSame('<p>Do you also love Drupal?</p><img src="druplicon.png" data-caption="Druplicon" />', $ajax_commands[0]['data'], 'The editorGetUntransformedText command contains the expected data.');
  }

}
