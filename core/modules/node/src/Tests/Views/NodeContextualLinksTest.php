<?php

namespace Drupal\node\Tests\Views;

use Drupal\Component\Serialization\Json;
use Drupal\user\Entity\User;

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
    $this->drupalCreateContentType(array('type' => 'page'));
    $this->drupalCreateNode(array('promote' => 1));
    $this->drupalGet('node');

    $user = $this->drupalCreateUser(array('administer nodes', 'access contextual links'));
    $this->drupalLogin($user);

    $response = $this->renderContextualLinks(array('node:node=1:'), 'node');
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->setRawContent($json['node:node=1:']);

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
      CURLOPT_URL => \Drupal::url('contextual.render', [], ['absolute' => TRUE, 'query' => array('destination' => $current_path)]),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Tests if the node page works if Contextual Links is disabled.
   *
   * All views have Contextual links enabled by default, even with the
   * Contextual links module disabled. This tests if no calls are done to the
   * Contextual links module by views when it is disabled.
   *
   * @see https://www.drupal.org/node/2379811
   */
  public function testPageWithDisabledContextualModule() {
    \Drupal::service('module_installer')->uninstall(['contextual']);
    \Drupal::service('module_installer')->install(['views_ui']);

    // Ensure that contextual links don't get called for admin users.
    $admin_user = User::load(1);
    $admin_user->setPassword('new_password');
    $admin_user->pass_raw = 'new_password';
    $admin_user->save();

    $this->drupalCreateContentType(array('type' => 'page'));
    $this->drupalCreateNode(array('promote' => 1));

    $this->drupalLogin($admin_user);
    $this->drupalGet('node');
  }

}
