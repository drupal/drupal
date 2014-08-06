<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\FrontPageTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests front page functionality and administration.
 *
 * @group system
 */
class FrontPageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'system_test', 'views');

  function setUp() {
    parent::setUp();

    // Create admin user, log in admin user, and create one node.
    $this->admin_user = $this->drupalCreateUser(array('access content', 'administer site configuration'));
    $this->drupalLogin($this->admin_user);
    $this->node_path = "node/" . $this->drupalCreateNode(array('promote' => 1))->id();

    // Configure 'node' as front page.
    \Drupal::config('system.site')->set('page.front', 'node')->save();
    // Enable front page logging in system_test.module.
    \Drupal::state()->set('system_test.front_page_output', 1);
  }

  /**
   * Test front page functionality.
   */
  public function testDrupalFrontPage() {
    // Create a promoted node to test the <title> tag on the front page view.
    $settings = array(
      'title' => $this->randomMachineName(8),
      'promote' => 1,
    );
    $this->drupalCreateNode($settings);
    $this->drupalGet('');
    $this->assertTitle('Home | Drupal');

    $this->assertText(t('On front page.'), 'Path is the front page.');
    $this->drupalGet('node');
    $this->assertText(t('On front page.'), 'Path is the front page.');
    $this->drupalGet($this->node_path);
    $this->assertNoText(t('On front page.'), 'Path is not the front page.');

    // Change the front page to an invalid path.
    $edit = array('site_frontpage' => 'kittens');
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));
    $this->assertText(t("The path '@path' is either invalid or you do not have access to it.", array('@path' => $edit['site_frontpage'])));

    // Change the front page to a valid path.
    $edit['site_frontpage'] = $this->node_path;
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'The front page path has been saved.');

    $this->drupalGet('');
    $this->assertText(t('On front page.'), 'Path is the front page.');
    $this->drupalGet('node');
    $this->assertNoText(t('On front page.'), 'Path is not the front page.');
    $this->drupalGet($this->node_path);
    $this->assertText(t('On front page.'), 'Path is the front page.');
  }
}
