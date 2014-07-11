<?php

/**
 * @file
 * Contains \Drupal\contextual\Tests\ContextualDynamicContextTest.
 */

namespace Drupal\contextual\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Template\Attribute;

/**
 * Tests if contextual links are showing on the front page depending on
 * permissions.
 *
 * @group contextual
 */
class ContextualDynamicContextTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contextual', 'node', 'views', 'views_ui');

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    $this->editor_user = $this->drupalCreateUser(array('access content', 'access contextual links', 'edit any article content'));
    $this->authenticated_user = $this->drupalCreateUser(array('access content', 'access contextual links'));
    $this->anonymous_user = $this->drupalCreateUser(array('access content'));
  }

  /**
   * Tests contextual links with different permissions.
   *
   * Ensures that contextual link placeholders always exist, even if the user is
   * not allowed to use contextual links.
   */
  function testDifferentPermissions() {
    $this->drupalLogin($this->editor_user);

    // Create three nodes in the following order:
    // - An article, which should be user-editable.
    // - A page, which should not be user-editable.
    // - A second article, which should also be user-editable.
    $node1 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));
    $node2 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 1));
    $node3 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));

    // Now, on the front page, all article nodes should have contextual links
    // placeholders, as should the view that contains them.
    $ids = array(
      'node:node=' . $node1->id() . ':changed=' . $node1->getChangedTime(),
      'node:node=' . $node2->id() . ':changed=' . $node2->getChangedTime(),
      'node:node=' . $node3->id() . ':changed=' . $node3->getChangedTime(),
      'views_ui_edit:view=frontpage:location=page&name=frontpage&display_id=page_1',
    );

    // Editor user: can access contextual links and can edit articles.
    $this->drupalGet('node');
    for ($i = 0; $i < count($ids); $i++) {
      $this->assertContextualLinkPlaceHolder($ids[$i]);
    }
    $this->renderContextualLinks(array(), 'node');
    $this->assertResponse(400);
    $this->assertRaw('No contextual ids specified.');
    $response = $this->renderContextualLinks($ids, 'node');
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->assertIdentical($json[$ids[0]], '<ul class="contextual-links"><li class="nodepage-edit"><a href="' . base_path() . 'node/1/edit">Edit</a></li></ul>');
    $this->assertIdentical($json[$ids[1]], '');
    $this->assertIdentical($json[$ids[2]], '<ul class="contextual-links"><li class="nodepage-edit"><a href="' . base_path() . 'node/3/edit">Edit</a></li></ul>');
    $this->assertIdentical($json[$ids[3]], '');

    // Authenticated user: can access contextual links, cannot edit articles.
    $this->drupalLogin($this->authenticated_user);
    $this->drupalGet('node');
    for ($i = 0; $i < count($ids); $i++) {
      $this->assertContextualLinkPlaceHolder($ids[$i]);
    }
    $this->renderContextualLinks(array(), 'node');
    $this->assertResponse(400);
    $this->assertRaw('No contextual ids specified.');
    $response = $this->renderContextualLinks($ids, 'node');
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->assertIdentical($json[$ids[0]], '');
    $this->assertIdentical($json[$ids[1]], '');
    $this->assertIdentical($json[$ids[2]], '');
    $this->assertIdentical($json[$ids[3]], '');

    // Anonymous user: cannot access contextual links.
    $this->drupalLogin($this->anonymous_user);
    $this->drupalGet('node');
    for ($i = 0; $i < count($ids); $i++) {
      $this->assertContextualLinkPlaceHolder($ids[$i]);
    }
    $this->renderContextualLinks(array(), 'node');
    $this->assertResponse(403);
    $this->renderContextualLinks($ids, 'node');
    $this->assertResponse(403);
  }

  /**
   * Asserts that a contextual link placeholder with the given id exists.
   *
   * @param string $id
   *   A contextual link id.
   *
   * @return bool
   */
  protected function assertContextualLinkPlaceHolder($id) {
    $this->assertRaw('<div' . new Attribute(array('data-contextual-id' => $id)) . '></div>', format_string('Contextual link placeholder with id @id exists.', array('@id' => $id)));
  }

  /**
   * Asserts that a contextual link placeholder with the given id does not exist.
   *
   * @param string $id
   *   A contextual link id.
   *
   * @return bool
   */
  protected function assertNoContextualLinkPlaceHolder($id) {
    $this->assertNoRaw('<div' . new Attribute(array('data-contextual-id' => $id)) . '></div>', format_string('Contextual link placeholder with id @id does not exist.', array('@id' => $id)));
  }

  /**
   * Get server-rendered contextual links for the given contextual link ids.
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
    $post = array();
    for ($i = 0; $i < count($ids); $i++) {
      $post['ids[' . $i . ']'] = $ids[$i];
    }
    return $this->drupalPost('contextual/render', 'application/json', $post, array('query' => array('destination' => $current_path)));
  }
}
