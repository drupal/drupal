<?php

/**
 * @file
 * Definition of Drupal\dashboard\Tests\DashboardBlocksTest.
 */

namespace Drupal\dashboard\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Dashboard module blocks.
 */
class DashboardBlocksTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Dashboard blocks',
      'description' => 'Test blocks as used by the dashboard.',
      'group' => 'Dashboard',
    );
  }

  function setUp() {
    parent::setUp(array('block', 'dashboard'));

    // Create and log in an administrative user having access to the dashboard.
    $admin_user = $this->drupalCreateUser(array('access dashboard', 'administer blocks', 'access administration pages', 'administer modules'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests adding a block to the dashboard and checking access to it.
   */
  function testDashboardAccess() {
    // Add a new custom block to a dashboard region.
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $this->randomName(8);
    $custom_block['body[value]'] = $this->randomName(32);
    $custom_block['regions[stark]'] = 'dashboard_main';
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));

    // Ensure admin access.
    $this->drupalGet('admin/dashboard');
    $this->assertResponse(200, t('Admin has access to the dashboard.'));
    $this->assertRaw($custom_block['title'], t('Admin has access to a dashboard block.'));

    // Ensure non-admin access is denied.
    $normal_user = $this->drupalCreateUser();
    $this->drupalLogin($normal_user);
    $this->drupalGet('admin/dashboard');
    $this->assertResponse(403, t('Non-admin has no access to the dashboard.'));
    $this->assertNoText($custom_block['title'], t('Non-admin has no access to a dashboard block.'));
  }

  /**
   * Tests that dashboard regions are displayed or hidden properly.
   */
  function testDashboardRegions() {
    $dashboard_regions = dashboard_region_descriptions();
    $this->assertTrue(!empty($dashboard_regions), 'One or more dashboard regions found.');

    // Ensure blocks can be placed in dashboard regions.
    $this->drupalGet('admin/dashboard/configure');
    foreach ($dashboard_regions as $region => $description) {
      $elements = $this->xpath('//option[@value=:region]', array(':region' => $region));
      $this->assertTrue(!empty($elements), t('%region is an available choice on the dashboard block configuration page.', array('%region' => $region)));
    }

    // Ensure blocks cannot be placed in dashboard regions on the standard
    // blocks configuration page.
    $this->drupalGet('admin/structure/block');
    foreach ($dashboard_regions as $region => $description) {
      $elements = $this->xpath('//option[@value=:region]', array(':region' => $region));
      $this->assertTrue(empty($elements), t('%region is not an available choice on the block configuration page.', array('%region' => $region)));
    }
  }

  /**
   * Tests that the dashboard module can be re-enabled, retaining its blocks.
   */
  function testDisableEnable() {
    // Add a new custom block to a dashboard region.
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $this->randomName(8);
    $custom_block['body[value]'] = $this->randomName(32);
    $custom_block['regions[stark]'] = 'dashboard_main';
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));
    $this->drupalGet('admin/dashboard');
    $this->assertRaw($custom_block['title'], t('Block appears on the dashboard.'));

    $edit = array();
    $edit['modules[Core][dashboard][enable]'] = FALSE;
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), t('Modules status has been updated.'));
    $this->assertNoRaw('assigned to the invalid region', t('Dashboard blocks gracefully disabled.'));
    module_list(TRUE);
    $this->assertFalse(module_exists('dashboard'), t('Dashboard disabled.'));

    $edit['modules[Core][dashboard][enable]'] = 'dashboard';
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), t('Modules status has been updated.'));
    module_list(TRUE);
    $this->assertTrue(module_exists('dashboard'), t('Dashboard enabled.'));

    $this->drupalGet('admin/dashboard');
    $this->assertRaw($custom_block['title'], t('Block still appears on the dashboard.'));
  }
}
