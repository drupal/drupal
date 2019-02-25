<?php

namespace Drupal\Tests\quickedit\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests accessing the Quick Edit endpoints.
 *
 * @group quickedit
 */
class QuickEditEndPointsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'quickedit',
    'node',
  ];

  /**
   * Tests that Quick Edit endpoints are protected from anonymous requests.
   */
  public function testEndPoints() {
    // Quick Edit's JavaScript would never hit these endpoints if the metadata
    // was empty as above, but we need to make sure that malicious users aren't
    // able to use any of the other endpoints either.
    $url = $this->buildUrl('/quickedit/attachments');
    $post = ['editors[0]' => 'form'];
    $this->checkAccessIsBlocked($url, $post);

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node = $this->createNode(['type' => 'article']);
    $url = $this->buildUrl('quickedit/form/node/' . $node->id() . '/body/en/full');
    $post = ['nocssjs' => 'true'];
    $this->checkAccessIsBlocked($url, $post);

    $edit = [];
    $edit['form_id'] = 'quickedit_field_form';
    $edit['form_token'] = 'xIOzMjuc-PULKsRn_KxFn7xzNk5Bx7XKXLfQfw1qOnA';
    $edit['form_build_id'] = 'form-kVmovBpyX-SJfTT5kY0pjTV35TV-znor--a64dEnMR8';
    $edit['body[0][summary]'] = '';
    $edit['body[0][value]'] = '<p>Malicious content.</p>';
    $edit['body[0][format]'] = 'filtered_html';
    $edit['op'] = t('Save');
    $this->checkAccessIsBlocked($url, $edit);

    $post = ['nocssjs' => 'true'];
    $url = $this->buildUrl('quickedit/entity/node/' . $node->id());
    $this->checkAccessIsBlocked($url, $post);
  }

  /**
   * Checks that access to the passed URL is blocked.
   *
   * @param string $url
   *   The URL to check.
   * @param $edit
   *   The payload to send with the request.
   */
  protected function checkAccessIsBlocked($url, $edit) {
    $client = $this->getHttpClient();
    $message = ['message' => "The 'access in-place editing' permission is required."];

    $response = $client->post($url, [
      'body' => http_build_query($edit),
      'query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax'],
      'cookies' => $this->getSessionCookies(),
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ]);

    $this->assertEquals(403, $response->getStatusCode());

    $response_message = Json::decode($response->getBody());
    $this->assertSame($message, $response_message);
  }

}
