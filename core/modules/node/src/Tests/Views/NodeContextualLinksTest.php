<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\NodeContextualLinksTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests views contextual links on nodes.
 *
 * @group node
 */
class NodeContextualLinksTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contextual');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_contextual_links');

  /**
   * Tests contextual links.
   */
  public function testNodeContextualLinks() {
    $this->drupalCreateNode(array('promote' => 1));
    $this->drupalGet('node');

    $user = $this->drupalCreateUser(array('administer nodes', 'access contextual links'));
    $this->drupalLogin($user);

    $response = $this->renderContextualLinks(array('node:node=1:'), 'node');
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->drupalSetContent($json['node:node=1:']);

    // @todo Add these back when the functionality for making Views displays
    //   appear in contextual links is working again.
    // $this->assertLinkByHref('node/1/contextual-links', 0, 'The contextual link to the view was found.');
    // $this->assertLink('Test contextual link', 0, 'The contextual link to the view was found.');
  }

  /**
   * Get server-rendered contextual links for the given contextual link ids.
   *
   * Copied from \Drupal\contextual\Tests\ContextualDynamicContextTest::renderContextualLinks().
   *
   * @param array $ids
   *   An array of contextual link ids.
   * @param string $current_path
   *   The Drupal path for the page for which the contextual links are rendered.
   *
   * @return string
   *   The response body.
   */
  protected function renderContextualLinks($ids, $current_path) {
    // Build POST values.
    $post = array();
    for ($i = 0; $i < count($ids); $i++) {
      $post['ids[' . $i . ']'] = $ids[$i];
    }

    // Serialize POST values.
    foreach ($post as $key => $value) {
      // Encode according to application/x-www-form-urlencoded
      // Both names and values needs to be urlencoded, according to
      // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    $post = implode('&', $post);

    // Perform HTTP request.
    return $this->curlExec(array(
      CURLOPT_URL => url('contextual/render', array('absolute' => TRUE, 'query' => array('destination' => $current_path))),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

}
