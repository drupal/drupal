<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\BareStandardUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Performs major version release upgrade tests on a bare database.
 *
 * Loads an installation of Drupal 7.x and runs the upgrade process on it.
 *
 * The install contains the standard profile (plus all optional) modules
 * without any content so that an update from any of the modules under this
 * profile installation can be wholly tested.
 */
class BareStandardUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'Basic standard + all profile upgrade path, bare database',
      'description'  => 'Basic upgrade path tests for a standard profile install with all enabled modules and a bare database.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
    );
    parent::setUp();
  }

  /**
   * Tests a successful major version release upgrade.
   */
  public function testBasicStandardUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Hit the frontpage.
    $this->drupalGet('');
    $this->assertResponse(200);

    // Verify that we are still logged in.
    $this->drupalGet('user');
    $this->clickLink(t('Edit'));
    $this->assertEqual($this->getUrl(), url('user/1/edit', array('absolute' => TRUE)), 'We are still logged in as admin at the end of the upgrade.');

    // Logout and verify that we can login back in with our initial password.
    $this->drupalLogout();
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

    // Verify that the blog node type has been assigned to node module.
    $blog_type = node_type_load('blog');
    $this->assertEqual($blog_type->module, 'node', "Content type 'blog' has been reassigned from the blog module to the node module.");
    $this->assertEqual($blog_type->base, 'node_content', "The base string used to construct callbacks corresponding to content type 'Blog' has been reassigned to 'node_content'.");

  }
}
