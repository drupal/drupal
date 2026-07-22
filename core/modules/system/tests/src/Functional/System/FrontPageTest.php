<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests front page functionality and administration.
 */
#[Group('system')]
#[RunTestsInSeparateProcesses]
class FrontPageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'path', 'system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The path to a node that is created for testing.
   *
   * @var string
   */
  protected $nodePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create admin user, log in admin user, and create one node.
    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'administer site configuration',
    ]));
    $this->drupalCreateContentType(['type' => 'page']);
    $this->nodePath = "node/" . $this->drupalCreateNode()->id();

    // Configure the created node as front page.
    $this->config('system.site')->set('page.front', '/' . $this->nodePath)->save();
    // Enable front page logging in system_test.module.
    \Drupal::state()->set('system_test.front_page_output', 1);
  }

  /**
   * Tests front page functionality.
   */
  public function testDrupalFrontPage(): void {
    // Test the front page displays correctly.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('On front page.');

    // Check that the direct path is also considered the front page.
    $this->drupalGet($this->nodePath);
    $this->assertSession()->pageTextContains('On front page.');

    // Test that other paths are not the front page.
    $this->drupalGet('admin/structure');
    $this->assertSession()->pageTextNotContains('On front page.');

    // Change the front page to an invalid path.
    $edit = ['site_frontpage' => '/kittens'];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains("Either the path '" . $edit['site_frontpage'] . "' is invalid or you do not have access to it.");

    // Change the front page to a path without a starting slash.
    $edit = ['site_frontpage' => $this->nodePath];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains("The path '{$edit['site_frontpage']}' has to start with a slash.");

    // Change the front page again to a valid path.
    $edit['site_frontpage'] = '/' . $this->nodePath;
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');
    // Check that the front page path has been saved.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    // Check that the empty path is the front page.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('On front page.');
    // Test that other paths are not the front page.
    $this->drupalGet('admin');
    $this->assertSession()->pageTextNotContains('On front page.');
    // Check that the direct path is also considered the front page.
    $this->drupalGet($this->nodePath);
    $this->assertSession()->pageTextContains('On front page.');

    // Test the form when page.front is null.
    $this->config('system.site')->clear('page.front')->save();
    $this->drupalGet('admin/config/system/site-information');
    $this->assertSession()->statusCodeEquals(200);

    // Create a new piece of content with an alias and confirm that its aliased
    // path can be set as the front page.
    $this->drupalCreateNode([
      'path' => '/what-a-twist',
      'body' => 'Space, the final frontier.',
    ]);
    $edit['site_frontpage'] = '/what-a-twist';
    $this->submitForm($edit, 'Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('The configuration options have been saved.');
    $assert_session->fieldValueEquals('site_frontpage', '/what-a-twist');
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('On front page.');
    $assert_session->pageTextContains('Space, the final frontier.');
    $assert_session->addressEquals('/');
    $this->assertSame('/what-a-twist', $this->config('system.site')->get('page.front'));
  }

}
