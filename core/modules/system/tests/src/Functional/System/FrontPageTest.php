<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests front page functionality and administration.
 *
 * @group system
 */
class FrontPageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'system_test', 'views'];

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
    $this->nodePath = "node/" . $this->drupalCreateNode(['promote' => 1])->id();

    // Configure 'node' as front page.
    $this->config('system.site')->set('page.front', '/node')->save();
    // Enable front page logging in system_test.module.
    \Drupal::state()->set('system_test.front_page_output', 1);
  }

  /**
   * Tests front page functionality.
   */
  public function testDrupalFrontPage() {
    // Create a promoted node to test the <title> tag on the front page view.
    $settings = [
      'title' => $this->randomMachineName(8),
      'promote' => 1,
    ];
    $this->drupalCreateNode($settings);
    $this->drupalGet('');
    $this->assertSession()->titleEquals('Home | Drupal');

    // Check that path is the front page.
    $this->assertSession()->pageTextContains('On front page.');
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains('On front page.');
    $this->drupalGet($this->nodePath);
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

    // Change the front page to a valid path.
    $edit['site_frontpage'] = '/' . $this->nodePath;
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');
    // Check that the front page path has been saved.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    // Check that path is the front page.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('On front page.');
    $this->drupalGet('node');
    $this->assertSession()->pageTextNotContains('On front page.');
    $this->drupalGet($this->nodePath);
    $this->assertSession()->pageTextContains('On front page.');
  }

}
