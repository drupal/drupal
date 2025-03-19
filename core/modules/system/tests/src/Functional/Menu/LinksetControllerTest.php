<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Menu;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

/**
 * Tests the behavior of the linkset controller.
 *
 * The purpose of this test is to validate that the a typical menu can be
 * correctly serialized as using the application/linkset+json media type.
 *
 * @group decoupled_menus
 *
 * @see https://tools.ietf.org/html/draft-ietf-httpapi-linkset-00
 */
final class LinksetControllerTest extends LinksetControllerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * An HTTP kernel.
   *
   * Used to send a test request to the controller under test and validate its
   * response.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * A user account to author test content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorAccount;

  /**
   * Test set up.
   *
   * Installs necessary database schemas, then creates test content and menu
   * items. The concept of this set up is to replicate a typical site's menus.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    parent::setUp();

    $permissions = ['view own unpublished content'];
    $this->authorAccount = $this->setUpCurrentUser([
      'name' => 'author',
      'pass' => 'authorPass',
    ], $permissions);

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    $home_page_link = $this->createMenuItem([
      'title' => 'Home',
      'description' => 'Links to the home page.',
      'link' => 'internal:/<front>',
      'weight' => 0,
      'menu_name' => 'main',
    ]);

    $about_us_page = $this->createNode([
      'nid' => 1,
      'title' => 'About us',
      'type' => 'page',
      'path' => '/about',
    ]);
    $about_us_link = $this->createMenuItem([
      'title' => 'About us',
      'description' => 'Links to the about us page.',
      'link' => 'entity:node/' . (int) $about_us_page->id(),
      'weight' => $home_page_link->getWeight() + 1,
      'menu_name' => 'main',
    ]);

    $our_name_page = $this->createNode([
      'nid' => 2,
      'title' => 'Our name',
      'type' => 'page',
      'path' => '/about/name',
    ]);
    $this->createMenuItem([
      'title' => 'Our name',
      'description' => 'Links to the page which describes the origin of the organization name.',
      'link' => 'entity:node/' . (int) $our_name_page->id(),
      'menu_name' => 'main',
      'parent' => $about_us_link->getPluginId(),
    ]);

    $custom_attributes_test_page = $this->createNode([
      'nid' => 3,
      'title' => 'Custom attributes test page',
      'type' => 'page',
      'path' => '/about/custom-attributes',
    ]);
    $options = [
      'attributes' => [
        'class' => [
          'foo',
          'bar',
          1729,
          TRUE,
          -1,
          3.141592,
        ],
        'data-baz' => '42',
        '*ignored' => '¯\_(ツ)_/¯',
        "hreflang" => "en-mx",
        "media" => "???",
        "type" => "???",
        "title" => "???",
        "title*" => "???",
      ],
    ];
    $this->createMenuItem([
      'title' => 'Custom attributes test page',
      'description' => 'Links to the page which describes the origin of the organization name.',
      'link' => 'entity:node/' . (int) $custom_attributes_test_page->id(),
      'menu_name' => 'main',
      'parent' => $about_us_link->getPluginId(),
    ], $options);

    $this->httpKernel = $this->container->get('http_kernel');
  }

  /**
   * Test core functions of the linkset endpoint.
   *
   * Not intended to test every feature of the endpoint, only the most basic
   * functionality.
   *
   * The expected linkset also ensures that path aliasing is working properly.
   *
   * @throws \Exception
   */
  public function testBasicFunctions(): void {
    $this->enableEndpoint(TRUE);
    $expected_linkset = $this->getReferenceLinksetDataFromFile(__DIR__ . '/../../../fixtures/linkset/linkset-menu-main.json');
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'));
    $this->assertSame('application/linkset+json', $response->getHeaderLine('content-type'));
    $this->assertSame($expected_linkset, Json::decode((string) $response->getBody()));
    $this->doRequest('GET', Url::fromUri('base:/system/menu/missing/linkset'), 404);
  }

  /**
   * Test the cacheability of the linkset endpoint.
   *
   * This test's purpose is to ensure that the menu linkset response is properly
   * cached. It does this by sending a request and validating it has a cache
   * miss and the correct cacheability meta, then by sending the same request to
   * assert a cache hit. Finally, a new menu item is created to ensure that the
   * cached response is properly invalidated.
   */
  public function testCacheability(): void {
    $this->enableEndpoint(TRUE);
    $expected_cacheability = new CacheableMetadata();
    $expected_cacheability->addCacheContexts([
      'user.permissions',
    ]);
    $expected_cacheability->addCacheTags([
      'config:system.menu.main',
      'config:user.role.anonymous',
      'http_response',
      'node:1',
      'node:2',
      'node:3',
    ]);
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'));
    $this->assertDrupalResponseCacheability('MISS', $expected_cacheability, $response);
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'));
    $this->assertDrupalResponseCacheability('HIT', $expected_cacheability, $response);
    // Create a new menu item to invalidate the cache.
    $duplicate_title = 'About us (duplicate)';
    $this->createMenuItem([
      'title' => $duplicate_title,
      'description' => 'Links to the about us page again.',
      'link' => 'entity:node/1',
      'menu_name' => 'main',
    ]);
    // Redo the request.
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'));
    // Assert that the cache has been invalidated.
    $this->assertDrupalResponseCacheability('MISS', $expected_cacheability, $response);
    // Then ensure that the new menu link is in the response.
    $link_items = Json::decode((string) $response->getBody())['linkset'][0]['item'];
    $titles = array_column($link_items, 'title');
    $this->assertContains($duplicate_title, $titles);
  }

  /**
   * Test the access control functionality of the linkset endpoint.
   *
   * By testing with different current users (Anonymous included) against the
   * user account menu, this test ensures that the menu endpoint respects route
   * access controls. E.g. it does not output links to which the current user
   * does not have access (if it can be determined).
   */
  public function testAccess(): void {
    $this->enableEndpoint(TRUE);
    $expected_cacheability = new CacheableMetadata();
    $expected_cacheability->addCacheContexts(['user.permissions']);
    $expected_cacheability->addCacheTags([
      'config:system.menu.main',
      'config:user.role.anonymous',
      'http_response',
      'node:1',
      'node:2',
      'node:3',
    ]);
    // Warm the cache, then get a response and ensure it was warmed.
    $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'));
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'));
    $this->assertDrupalResponseCacheability('HIT', $expected_cacheability, $response);
    // Ensure the "Our name" menu link is visible.
    $link_items = Json::decode((string) $response->getBody())['linkset'][0]['item'];
    $titles = array_column($link_items, 'title');
    $this->assertContains('Our name', $titles);
    // Now, unpublish the target node.
    $our_name_page = Node::load(2);
    assert($our_name_page instanceof NodeInterface);
    $our_name_page->setUnpublished()->save();
    // Redo the request.
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'));
    // Assert that the cache was invalidated.
    $this->assertDrupalResponseCacheability('MISS', $expected_cacheability, $response);
    // Ensure the "Our name" menu link is no longer visible.
    $link_items = Json::decode((string) $response->getBody())['linkset'][0]['item'];
    $titles = array_column($link_items, 'title');
    $this->assertNotContains('Our name', $titles);
    // Redo the request, but authenticate as the unpublished page's author.
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'), 200, $this->authorAccount);
    $expected_cacheability = new CacheableMetadata();
    $expected_cacheability->addCacheContexts(['user']);
    $expected_cacheability->addCacheTags([
      'config:system.menu.main',
      'http_response',
      'node:1',
      'node:2',
      'node:3',
    ]);
    $this->assertDrupalResponseCacheability(FALSE, $expected_cacheability, $response);
    // Ensure the "Our name" menu link is visible.
    $link_items = Json::decode((string) $response->getBody())['linkset'][0]['item'];
    $titles = array_column($link_items, 'title');
    $this->assertContains('Our name', $titles);
  }

  /**
   * Tests that the user account menu behaves as it should.
   *
   * The account menu is a good test case because it provides a restricted,
   * YAML-defined link ("My account") and a dynamic code-defined link
   * ("Log in/out")
   */
  public function testUserAccountMenu(): void {
    $this->enableEndpoint(TRUE);
    $expected_cacheability = new CacheableMetadata();
    $expected_cacheability->addCacheContexts([
      'user.roles:authenticated',
    ]);
    $expected_cacheability->addCacheTags([
      'config:system.menu.account',
      'http_response',
    ]);
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/account/linkset'));
    $this->assertDrupalResponseCacheability('MISS', $expected_cacheability, $response);
    $link_items = Json::decode((string) $response->getBody())['linkset'][0]['item'];
    $titles = array_column($link_items, 'title');
    $this->assertContains('Log in', $titles);
    $this->assertNotContains('Log out', $titles);
    $this->assertNotContains('My account', $titles);
    // Redo the request, but with an authenticated user.
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/account/linkset'), 200, $this->authorAccount);
    // The expected cache tags must be updated.
    $expected_cacheability->setCacheTags([
      'config:system.menu.account',
      'http_response',
    ]);
    // Authenticated requests do not use the page cache, so a "HIT" or "MISS"
    // isn't expected either.
    $this->assertDrupalResponseCacheability(FALSE, $expected_cacheability, $response);
    $link_items = Json::decode((string) $response->getBody())['linkset'][0]['item'];
    $titles = array_column($link_items, 'title');
    $this->assertContains('Log out', $titles);
    $this->assertContains('My account', $titles);
    $this->assertNotContains('Log in', $titles);
  }

  /**
   * Tests that menu items can use a custom link relation.
   */
  public function testCustomLinkRelation(): void {
    $this->enableEndpoint(TRUE);
    $this->assertTrue($this->container->get('module_installer')->install(['decoupled_menus_test'], TRUE), 'Installed modules.');
    $response = $this->doRequest('GET', Url::fromUri('base:/system/menu/account/linkset'), 200, $this->authorAccount);
    $link_context_object = Json::decode((string) $response->getBody())['linkset'][0];
    $this->assertContains('authenticated-as', array_keys($link_context_object));
    $my_account_link = $link_context_object['authenticated-as'][0];
    $this->assertSame('My account', $my_account_link['title']);
  }

  /**
   * Test that api route does not exist if the config option is disabled.
   */
  public function testDisabledEndpoint(): void {
    $this->doRequest('GET', Url::fromUri('base:/system/menu/main/linkset'), 404);
  }

}
