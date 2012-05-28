<?php

/**
 * @file
 * Definition of Drupal\dashboard\Tests\DashboardBlockAvailabilityTest.
 */

namespace Drupal\dashboard\Tests;

use Drupal\simpletest\WebTestBase;

class DashboardBlockAvailabilityTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Block availability',
      'description' => 'Test blocks as used by the dashboard.',
      'group' => 'Dashboard',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and log in an administrative user having access to the dashboard.
    $admin_user = $this->drupalCreateUser(array('access dashboard', 'administer blocks', 'access administration pages', 'administer modules'));
    $this->drupalLogin($admin_user);

    // Make sure that the dashboard is using the same theme as the rest of the
    // site (and in particular, the same theme used on 403 pages). This forces
    // the dashboard blocks to be the same for an administrator as for a
    // regular user, and therefore lets us test that the dashboard blocks
    // themselves are specifically removed for a user who does not have access
    // to the dashboard page.
    theme_enable(array('stark'));
    variable_set('theme_default', 'stark');
    variable_set('admin_theme', 'stark');
  }

  /**
   * Tests that administrative blocks are available for the dashboard.
   */
  function testBlockAvailability() {
    // Test "Recent comments", which should be available (defined as
    // "administrative") but not enabled.
    $this->drupalGet('admin/dashboard');
    $this->assertNoText(t('Recent comments'), t('"Recent comments" not on dashboard.'));
    $this->drupalGet('admin/dashboard/drawer');
    $this->assertText(t('Recent comments'), t('Drawer of disabled blocks includes a block defined as "administrative".'));
    $this->assertNoText(t('Syndicate'), t('Drawer of disabled blocks excludes a block not defined as "administrative".'));
    $this->drupalGet('admin/dashboard/configure');
    $elements = $this->xpath('//select[@id=:id]//option[@selected="selected"]', array(':id' => 'edit-blocks-comment-recent-region'));
    $this->assertTrue($elements[0]['value'] == 'dashboard_inactive', t('A block defined as "administrative" defaults to dashboard_inactive.'));

    // Now enable the block on the dashboard.
    $values = array();
    $values['blocks[comment_recent][region]'] = 'dashboard_main';
    $this->drupalPost('admin/dashboard/configure', $values, t('Save blocks'));
    $this->drupalGet('admin/dashboard');
    $this->assertText(t('Recent comments'), t('"Recent comments" was placed on dashboard.'));
    $this->drupalGet('admin/dashboard/drawer');
    $this->assertNoText(t('Recent comments'), t('Drawer of disabled blocks excludes enabled blocks.'));
  }
}
