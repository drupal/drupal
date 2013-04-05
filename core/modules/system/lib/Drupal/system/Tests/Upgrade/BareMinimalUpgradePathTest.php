<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\BareMinimalUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Performs major version release upgrade tests on a bare database.
 *
 * Loads an installation of Drupal 7.x and runs the upgrade process on it.
 *
 * The install contains the minimal profile modules (without any generated
 * content) so that an upgrade from of a site under this profile may be tested.
 */
class BareMinimalUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name'  => 'Basic minimal profile upgrade path, bare database',
      'description'  => 'Basic upgrade path tests for a minimal profile install with a bare database.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.minimal.database.php.gz',
    );
    parent::setUp();
  }

  /**
   * Tests a successful major version release upgrade.
   */
  public function testBasicMinimalUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Hit the frontpage.
    $this->drupalGet('');
    $this->assertResponse(200);

    // Verify that we are still logged in.
    $this->finishUpgradeSession();
    $this->drupalLogin((object) array(
      'uid' => 1,
      'name' => 'admin',
      'pass_raw' => 'drupal',
    ));

    // The previous login should've triggered a password rehash, so login one
    // more time to make sure the new hash is readable.
    $this->drupalLogout();
    $this->drupalLogin((object) array(
      'uid' => 1,
      'name' => 'admin',
      'pass_raw' => 'drupal',
    ));

    // Test that the site name is correctly displayed.
    $this->assertText('drupal', 'The site name is correctly displayed.');

    // Verify that the main admin sections are available.
    $this->drupalGet('admin');
    $this->assertText(t('Content'));
    $this->assertText(t('Appearance'));
    $this->assertText(t('People'));
    $this->assertText(t('Configuration'));
    $this->assertText(t('Reports'));
    $this->assertText(t('Structure'));
    $this->assertText(t('Extend'));

    // Confirm that no {menu_links} entry exists for user/autocomplete.
    $result = db_query('SELECT COUNT(*) FROM {menu_links} WHERE link_path = :user_autocomplete', array(':user_autocomplete' => 'user/autocomplete'))->fetchField();
    $this->assertFalse($result, 'No {menu_links} entry exists for user/autocomplete');

    // Verify that all required modules are enabled.
    $enabled = $this->container->get('module_handler')->getModuleList();
    $required = array_filter(system_rebuild_module_data(), function ($data) {
      return !empty($data->info['required']);
    });
    $this->assertEqual(array_diff_key($required, $enabled), array());

    // Verify that image.module was correctly installed.
    $this->assertEqual('thumbnail', config('image.style.thumbnail')->get('name'));

    // Make sure that the default mail configuration has been converted.
    $this->assertEqual(array('default' => 'Drupal\Core\Mail\PhpMail'), config('system.mail')->get('interface'), 'Default mail configuration set.');
  }

  /**
   * Asserts that the session was kept during update. Also, log out.
   */
  protected function finishUpgradeSession() {
    $this->drupalGet('user');
    $this->clickLink(t('Edit'));
    $this->assertEqual($this->getUrl(), url('user/1/edit', array('absolute' => TRUE)), 'We are still logged in as admin at the end of the upgrade.');
    $this->drupalLogout();
  }

}
