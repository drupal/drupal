<?php

namespace Drupal\Tests\contextual\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests contextual link display on the front page based on permissions.
 *
 * @group contextual
 */
class ContextualDynamicContextTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $modules = [
    'contextual',
    'node',
    'views',
    'views_ui',
    'language',
    'menu_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    ConfigurableLanguage::createFromLangcode('it')->save();
    $this->rebuildContainer();

    $this->editorUser = $this->drupalCreateUser([
      'access content',
      'access contextual links',
      'edit any article content',
    ]);
    $this->authenticatedUser = $this->drupalCreateUser([
      'access content',
      'access contextual links',
    ]);
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
    $response = $this->renderContextualLinks([], 'node');
    $this->assertSame(400, $response->getStatusCode());
    $this->assertStringContainsString('No contextual ids specified.', (string) $response->getBody());
    $response = $this->renderContextualLinks($ids, 'node');
    $this->assertSame(200, $response->getStatusCode());
    $json = Json::decode((string) $response->getBody());
    $this->assertSame('<ul class="contextual-links"><li><a href="' . base_path() . 'node/1/edit">Edit</a></li></ul>', $json[$ids[0]]);
    $this->assertSame('', $json[$ids[1]]);
    $this->assertSame('<ul class="contextual-links"><li><a href="' . base_path() . 'node/3/edit">Edit</a></li></ul>', $json[$ids[2]]);
    $this->assertSame('', $json[$ids[3]]);

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
    $response = $this->renderContextualLinks([], 'node');
    $this->assertSame(400, $response->getStatusCode());
    $this->assertStringContainsString('No contextual ids specified.', (string) $response->getBody());
    $response = $this->renderContextualLinks($ids, 'node');
    $this->assertSame(200, $response->getStatusCode());
    $json = Json::decode((string) $response->getBody());
    $this->assertSame('', $json[$ids[0]]);
    $this->assertSame('', $json[$ids[1]]);
    $this->assertSame('', $json[$ids[2]]);
    $this->assertSame('', $json[$ids[3]]);

    // Anonymous user: cannot access contextual links.
    $this->drupalLogin($this->anonymousUser);
    $this->drupalGet('node');
    for ($i = 0; $i < count($ids); $i++) {
      $this->assertNoContextualLinkPlaceHolder($ids[$i]);
    }
    $response = $this->renderContextualLinks([], 'node');
    $this->assertSame(403, $response->getStatusCode());
    $this->renderContextualLinks($ids, 'node');
    $this->assertSame(403, $response->getStatusCode());

    // Get a page where contextual links are directly rendered.
    $this->drupalGet(Url::fromRoute('menu_test.contextual_test'));
    $this->assertSession()->assertEscaped("<script>alert('Welcome to the jungle!')</script>");
    $this->assertSession()->responseContains('<li><a href="' . base_path() . 'menu-test-contextual/1/edit" class="use-ajax" data-dialog-type="modal" data-is-something>Edit menu - contextual</a></li>');
    // Test contextual links respects the weight set in *.links.contextual.yml.
    $firstLink = $this->assertSession()->elementExists('css', 'ul.contextual-links li:nth-of-type(1) a');
    $secondLink = $this->assertSession()->elementExists('css', 'ul.contextual-links li:nth-of-type(2) a');
    $this->assertEquals(base_path() . 'menu-test-contextual/1/edit', $firstLink->getAttribute('href'));
    $this->assertEquals(base_path() . 'menu-test-contextual/1', $secondLink->getAttribute('href'));
  }

  /**
   * Tests the contextual placeholder content is protected by a token.
   */
  public function testTokenProtection() {
    $this->drupalLogin($this->editorUser);

    // Create a node that will have a contextual link.
    $node1 = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);

    // Now, on the front page, all article nodes should have contextual links
    // placeholders, as should the view that contains them.
    $id = 'node:node=' . $node1->id() . ':changed=' . $node1->getChangedTime() . '&langcode=en';

    // Editor user: can access contextual links and can edit articles.
    $this->drupalGet('node');
    $this->assertContextualLinkPlaceHolder($id);

    $http_client = $this->getHttpClient();
    $url = Url::fromRoute('contextual.render', [], [
      'query' => [
        '_format' => 'json',
        'destination' => 'node',
      ],
    ])->setAbsolute()->toString();

    $response = $http_client->request('POST', $url, [
      'cookies' => $this->getSessionCookies(),
      'form_params' => ['ids' => [$id], 'tokens' => []],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals('400', $response->getStatusCode());
    $this->assertStringContainsString('No contextual ID tokens specified.', (string) $response->getBody());

    $response = $http_client->request('POST', $url, [
      'cookies' => $this->getSessionCookies(),
      'form_params' => ['ids' => [$id], 'tokens' => ['wrong_token']],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals('400', $response->getStatusCode());
    $this->assertStringContainsString('Invalid contextual ID specified.', (string) $response->getBody());

    $response = $http_client->request('POST', $url, [
      'cookies' => $this->getSessionCookies(),
      'form_params' => ['ids' => [$id], 'tokens' => ['wrong_key' => $this->createContextualIdToken($id)]],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals('400', $response->getStatusCode());
    $this->assertStringContainsString('Invalid contextual ID specified.', (string) $response->getBody());

    $response = $http_client->request('POST', $url, [
      'cookies' => $this->getSessionCookies(),
      'form_params' => ['ids' => [$id], 'tokens' => [$this->createContextualIdToken($id)]],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals('200', $response->getStatusCode());
  }

  /**
   * Asserts that a contextual link placeholder with the given id exists.
   *
   * @param string $id
   *   A contextual link id.
   *
   * @internal
   */
  protected function assertContextualLinkPlaceHolder(string $id): void {
    $this->assertSession()->elementAttributeContains(
      'css',
      'div[data-contextual-id="' . $id . '"]',
      'data-contextual-token',
      $this->createContextualIdToken($id)
    );
  }

  /**
   * Asserts that a contextual link placeholder with the given id does not exist.
   *
   * @param string $id
   *   A contextual link id.
   *
   * @internal
   */
  protected function assertNoContextualLinkPlaceHolder(string $id): void {
    $this->assertSession()->elementNotExists('css', 'div[data-contextual-id="' . $id . '"]');
  }

  /**
   * Get server-rendered contextual links for the given contextual link ids.
   *
   * @param array $ids
   *   An array of contextual link ids.
   * @param string $current_path
   *   The Drupal path for the page for which the contextual links are rendered.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   */
  protected function renderContextualLinks($ids, $current_path) {
    $tokens = array_map([$this, 'createContextualIdToken'], $ids);
    $http_client = $this->getHttpClient();
    $url = Url::fromRoute('contextual.render', [], [
      'query' => [
        '_format' => 'json',
        'destination' => $current_path,
      ],
    ]);

    return $http_client->request('POST', $this->buildUrl($url), [
      'cookies' => $this->getSessionCookies(),
      'form_params' => ['ids' => $ids, 'tokens' => $tokens],
      'http_errors' => FALSE,
    ]);
  }

  /**
   * Creates a contextual ID token.
   *
   * @param string $id
   *   The contextual ID to create a token for.
   *
   * @return string
   *   The contextual ID token.
   */
  protected function createContextualIdToken($id) {
    return Crypt::hmacBase64($id, Settings::getHashSalt() . $this->container->get('private_key')->get());
  }

}
