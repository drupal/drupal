<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\MainContentFallbackTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 *  Test system module main content rendering fallback.
 *
 * @group system
 */
class MainContentFallbackTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'system_test');

  protected $admin_user;
  protected $web_user;

  function setUp() {
    parent::setUp();

    // Create and login admin user.
    $this->admin_user = $this->drupalCreateUser(array(
      'access administration pages',
      'administer site configuration',
      'administer modules',
    ));
    $this->drupalLogin($this->admin_user);

    // Create a web user.
    $this->web_user = $this->drupalCreateUser(array('access user profiles'));
  }

  /**
   * Test availability of main content.
   */
  function testMainContentFallback() {
    $edit = array();
    // Uninstall the block module.
    $edit['uninstall[block]'] = 'block';
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('block'), 'Block module uninstall.');

    // At this point, no region is filled and fallback should be triggered.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertField('site_name', 'Admin interface still available.');

    // Fallback should not trigger when another module is handling content.
    $this->drupalGet('system-test/main-content-handling');
    $this->assertRaw('id="system-test-content"', 'Content handled by another module');
    $this->assertText(t('Content to test main content fallback'), 'Main content still displayed.');

    // Fallback should trigger when another module
    // indicates that it is not handling the content.
    $this->drupalGet('system-test/main-content-fallback');
    $this->assertText(t('Content to test main content fallback'), 'Main content fallback properly triggers.');

    // Fallback should not trigger when another module is handling content.
    // Note that this test ensures that no duplicate
    // content gets created by the fallback.
    $this->drupalGet('system-test/main-content-duplication');
    $this->assertNoText(t('Content to test main content fallback'), 'Main content not duplicated.');

    // Request a user* page and see if it is displayed.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->web_user->id() . '/edit');
    $this->assertField('mail', 'User interface still available.');

    // Enable the block module again.
    $this->drupalLogin($this->admin_user);
    $edit = array();
    $edit['modules[Core][block][enable]'] = 'block';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Modules status has been updated.');
    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('block'), 'Block module re-enabled.');
  }
}
