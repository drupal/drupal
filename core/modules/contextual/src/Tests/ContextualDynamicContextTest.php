<?php

namespace Drupal\contextual\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
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
   * A user with permission to access contextual links and edit content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * An authenticated user with permission to access contextual links.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * A simulated anonymous user with access only to node content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['contextual', 'node', 'views', 'views_ui', 'language', 'menu_test'];

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    ConfigurableLanguage::createFromLangcode('it')->save();
    $this->rebuildContainer();

    $this->editorUser = $this->drupalCreateUser(['access content', 'access contextual links', 'edit any article content']);
    $this->authenticatedUser = $this->drupalCreateUser(['access content', 'access contextual links']);
    $this->anonymousUser = $this->drupalCreateUser(['access content']);
  }

  /**
   * Tests contextual links with different permissions.
   *
   * Ensures that contextual link placeholders always exist, even if the user is
   * not allowed to use contextual links.
   */
  public function testDifferentPermissions() {
    $this->drupalLogin($this->editorUser);

    // Create three nodes in the following order:
    // - An article, which should be user-editable.
    // - A page, which should not be user-editable.
    // - A second article, which should also be user-editable.
    $node1 = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
    $node2 = $this->drupalCreateNode(['type' => 'page', 'promote' => 1]);
    $node3 = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);

    // Now, on the front page, all article nodes should have contextual links
    // placeholders, as should the view that contains them.
    $ids = [
      'node:node=' . $node1->id() . ':changed=' . $node1->getChangedTime() . '&langcode=en',
      'node:node=' . $node2->id() . ':changed=' . $node2->getChangedTime() . '&langcode=en',
      'node:node=' . $node3->id() . ':changed=' . $node3->getChangedTime() . '&langcode=en',
      'entity.view.edit_form:view=frontpage:location=page&name=frontpage&display_id=page_1&langcode=en',
    ];

    // Editor user: can access contextual links and can edit articles.
    $this->drupalGet('node');
    for ($i = 0; $i < count($ids); $i++) {
      $this->assertContextualLinkPlaceHolder($ids[$i]);
    }
    $this->renderContextualLinks([], 'node');
    $this->assertResponse(400);
    $this->assertRaw('No contextual ids specified.');
    $response = $this->renderContextualLinks($ids, 'node');
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->assertIdentical($json[$ids[0]], '<ul class="contextual-links"><li class="entitynodeedit-form"><a href="' . base_path() . 'node/1/edit">Edit</a></li></ul>');
    $this->assertIdentical($json[$ids[1]], '');
    $this->assertIdentical($json[$ids[2]], '<ul class="contextual-links"><li class="entitynodeedit-form"><a href="' . base_path() . 'node/3/edit">Edit</a></li></ul>');
    $this->assertIdentical($json[$ids[3]], '');

    // Verify that link language is properly handled.
    $node3->addTranslation('it')->set('title', $this->randomString())->save();
    $id = 'node:node=' . $node3->id() . ':changed=' . $node3->getChangedTime() . '&langcode=it';
    $this->drupalGet('node', ['language' => ConfigurableLanguage::createFromLangcode('it')]);
    $this->assertContextualLinkPlaceHolder($id);

    // Authenticated user: can access contextual links, cannot edit articles.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('node');
    for ($i = 0; $i < count($ids); $i++) {
      $this->assertContextualLinkPlaceHolder($ids[$i]);
    }
    $this->renderContextualLinks([], 'node');
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
    $this->drupalLogin($this->anonymousUser);
    $this->drupalGet('node');
    for ($i = 0; $i < count($ids); $i++) {
      $this->assertNoContextualLinkPlaceHolder($ids[$i]);
    }
    $this->renderContextualLinks([], 'node');
    $this->assertResponse(403);
    $this->renderContextualLinks($ids, 'node');
    $this->assertResponse(403);

    // Get a page where contextual links are directly rendered.
    $this->drupalGet(Url::fromRoute('menu_test.contextual_test'));
    $this->assertEscaped("<script>alert('Welcome to the jungle!')</script>");
    $this->assertLink('Edit menu - contextual');
  }

  /**
   * Asserts that a contextual link placeholder with the given id exists.
   *
   * @param string $id
   *   A contextual link id.
   *
   * @return bool
   *   The result of the assertion.
   */
  protected function assertContextualLinkPlaceHolder($id) {
    return $this->assertRaw('<div' . new Attribute(['data-contextual-id' => $id]) . '></div>', format_string('Contextual link placeholder with id @id exists.', ['@id' => $id]));
  }

  /**
   * Asserts that a contextual link placeholder with the given id does not exist.
   *
   * @param string $id
   *   A contextual link id.
   *
   * @return bool
   *   The result of the assertion.
   */
  protected function assertNoContextualLinkPlaceHolder($id) {
    return $this->assertNoRaw('<div' . new Attribute(['data-contextual-id' => $id]) . '></div>', format_string('Contextual link placeholder with id @id does not exist.', ['@id' => $id]));
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
    $post = [];
    for ($i = 0; $i < count($ids); $i++) {
      $post['ids[' . $i . ']'] = $ids[$i];
    }
    return $this->drupalPostWithFormat('contextual/render', 'json', $post, ['query' => ['destination' => $current_path]]);
  }

}
