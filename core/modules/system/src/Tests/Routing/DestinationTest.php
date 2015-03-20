<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Routing\DestinationTest.
 */

namespace Drupal\system\Tests\Routing;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for $_GET['destination'] and $_REQUEST['destination'] validation.
 *
 * Note: This tests basically the same as
 * \Drupal\Tests\Core\EventSubscriber\RedirectResponseSubscriberTest::testSanitizeDestinationForGet
 * \Drupal\Tests\Core\EventSubscriber\RedirectResponseSubscriberTest::testSanitizeDestinationForPost
 * but we want to be absolutely sure it works.
 *
 * @group Routing
 */
class DestinationTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system_test'];

  /**
   * Tests that $_GET/$_REQUEST['destination'] only contain internal URLs.
   */
  public function testDestination() {
    $test_cases = [
      [
        'input' => 'node',
        'output' => 'node',
        'message' => "Standard internal example node path is present in the 'destination' parameter.",
      ],
      [
        'input' => '/example.com',
        'output' => '/example.com',
        'message' => 'Internal path with one leading slash is allowed.',
      ],
      [
        'input' => '//example.com/test',
        'output' => '',
        'message' => 'External URL without scheme is not allowed.',
      ],
      [
        'input' => 'example:test',
        'output' => 'example:test',
        'message' => 'Internal URL using a colon is allowed.',
      ],
      [
        'input' => 'http://example.com',
        'output' => '',
        'message' => 'External URL is not allowed.',
      ],
      [
        'input' => 'javascript:alert(0)',
        'output' => 'javascript:alert(0)',
        'message' => 'Javascript URL is allowed because it is treated as an internal URL.',
      ],
    ];
    foreach ($test_cases as $test_case) {
      // Test $_GET['destination'].
      $this->drupalGet('system-test/get-destination', ['query' => ['destination' => $test_case['input']]]);
      $this->assertIdentical($test_case['output'], $this->getRawContent(), $test_case['message']);
      // Test $_REQUEST['destination'].
      $post_output = $this->drupalPost('system-test/request-destination', '*', ['destination' => $test_case['input']]);
      $this->assertIdentical($test_case['output'], $post_output, $test_case['message']);
    }

    // Make sure that 404 pages do not populate $_GET['destination'] with
    // external URLs.
    \Drupal::configFactory()->getEditable('system.site')->set('page.404', 'system-test/get-destination')->save();
    $this->drupalGet('http://example.com', ['external' => FALSE]);
    $this->assertResponse(404);
    $this->assertIdentical(Url::fromRoute('<front>')->toString(), $this->getRawContent(), 'External URL is not allowed on 404 pages.');
  }

}
