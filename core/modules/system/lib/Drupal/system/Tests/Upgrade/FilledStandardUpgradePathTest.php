<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\FilledStandardUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Performs major version release upgrade tests on a populated database.
 *
 * Loads an installation of Drupal 7.x and runs the upgrade process on it.
 *
 * The install contains the standard profile (plus all optional) modules
 * with generated content so that an update from any of the modules under this
 * profile installation can be wholly tested.
 */
class FilledStandardUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'Basic standard + all profile upgrade path, populated database',
      'description'  => 'Basic upgrade path tests for a standard profile install with all enabled modules and a populated database.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.filled.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.user_data.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests a successful point release update.
   */
  public function testFilledStandardUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Hit the frontpage.
    $this->drupalGet('');
    $this->assertResponse(200);

    // Verify that the former Navigation system menu block appears as Tools.
    // @todo Blocks are not being upgraded.
    //   $this->assertText(t('Tools'));

    // Verify that the Account menu still appears as secondary links source.
    $this->assertText(t('My account'));
    $this->assertText(t('Log out'));

    // Verify the the Main menu still appears as primary links source.
    $this->assertLink(t('Home'));

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
    $node_type = entity_load('node_type', 'blog');
    $this->assertFalse($node_type->isLocked(), "Content type 'blog' has been reassigned from the blog module to the node module.");
    $node_type = entity_load('node_type', 'forum');
    $this->assertTrue($node_type->isLocked(), "The base string used to construct callbacks corresponding to content type 'Forum' has been reassigned to forum module.");

    // Each entity type has a 'full' view mode, ensure it was migrated.
    $all_view_modes = entity_get_view_modes();
    $this->assertTrue(!empty($all_view_modes), 'The view modes have been migrated.');
    foreach ($all_view_modes as $entity_view_modes) {
      $this->assertTrue(isset($entity_view_modes['full']));
    }

    // Check that user data has been migrated correctly.
    $query = db_query('SELECT * FROM {users_data}');

    $userdata = array();
    $i = 0;
    foreach ($query as $row) {
      $i++;
      $userdata[$row->uid][$row->module][$row->name] = $row;
    }
    // Check that the correct amount of rows exist.
    $this->assertEqual($i, 5);
    // Check that the data has been converted correctly.
    $this->assertEqual(unserialize($userdata[1]['contact']['enabled']->value), 1);
    $this->assertEqual($userdata[1]['contact']['enabled']->serialized, 1);
    $this->assertEqual(unserialize($userdata[2]['contact']['enabled']->value), 0);
    $this->assertEqual(unserialize($userdata[1]['overlay']['enabled']->value), 1);
    $this->assertEqual(unserialize($userdata[2]['overlay']['enabled']->value), 1);
    $this->assertEqual(unserialize($userdata[1]['overlay']['message_dismissed']->value), 1);
    $this->assertFalse(isset($userdata[2]['overlay']['message_dismissed']));

    // Make sure that only the garbage is remaining in the helper table.
    $this->assertEqual(db_query('SELECT COUNT(*) FROM {_d7_users_data}')->fetchField(), 2);
  }
}
