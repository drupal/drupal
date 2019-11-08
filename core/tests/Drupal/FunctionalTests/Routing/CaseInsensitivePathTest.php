<?php

namespace Drupal\FunctionalTests\Routing;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests incoming path case insensitivity.
 *
 * @group routing
 */
class CaseInsensitivePathTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'views', 'node', 'system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::state()->set('system_test.module_hidden', FALSE);
    $this->createContentType(['type' => 'page']);
  }

  /**
   * Tests mixed case paths.
   */
  public function testMixedCasePaths() {
    // Tests paths defined by routes from standard modules as anonymous.
    $this->drupalGet('user/login');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/Log in/');
    $this->drupalGet('User/Login');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/Log in/');

    // Tests paths defined by routes from the Views module.
    $admin = $this->drupalCreateUser(['access administration pages', 'administer nodes', 'access content overview']);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/Content/');
    $this->drupalGet('Admin/Content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/Content/');

    // Tests paths with query arguments.

    // Make sure our node title doesn't exist.
    $this->drupalGet('admin/content');
    $this->assertSession()->linkNotExists('FooBarBaz');
    $this->assertSession()->linkNotExists('foobarbaz');

    // Create a node, and make sure it shows up on admin/content.
    $node = $this->createNode([
      'title' => 'FooBarBaz',
      'type' => 'page',
    ]);

    $this->drupalGet('admin/content', [
      'query' => [
        'title' => 'FooBarBaz',
      ],
    ]);

    $this->assertSession()->linkExists('FooBarBaz');
    $this->assertSession()->linkByHrefExists($node->toUrl()->toString());

    // Make sure the path is case-insensitive, and query case is preserved.

    $this->drupalGet('Admin/Content', [
      'query' => [
        'title' => 'FooBarBaz',
      ],
    ]);

    $this->assertSession()->linkExists('FooBarBaz');
    $this->assertSession()->linkByHrefExists($node->toUrl()->toString());
    $this->assertSession()->fieldValueEquals('edit-title', 'FooBarBaz');
    // Check that we can access the node with a mixed case path.
    $this->drupalGet('NOdE/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/FooBarBaz/');
  }

  /**
   * Tests paths with slugs.
   */
  public function testPathsWithArguments() {
    $this->drupalGet('system-test/echo/foobarbaz');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/foobarbaz/');
    $this->assertSession()->pageTextNotMatches('/FooBarBaz/');

    $this->drupalGet('system-test/echo/FooBarBaz');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/FooBarBaz/');
    $this->assertSession()->pageTextNotMatches('/foobarbaz/');

    // Test utf-8 characters in the route path.
    $this->drupalGet('/system-test/Ȅchȏ/meΦω/ABc123');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/ABc123/');
    $this->drupalGet('/system-test/ȅchȎ/MEΦΩ/ABc123');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextMatches('/ABc123/');
  }

}
