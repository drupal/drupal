<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\ForumUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrading a filled database with forum data.
 *
 * Loads a filled installation of Drupal 7 with forums and containers and runs
 * the upgrade process on it.
 */
class ForumUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name'  => 'Forum upgrade test',
      'description'  => 'Upgrade tests with forum data.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $path = drupal_get_path('module', 'system') . '/tests/upgrade';
    $this->databaseDumpFiles = array(
      $path . '/drupal-7.bare.standard_all.database.php.gz',
      $path . '/drupal-7.forum.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests expected forum and container conversions after a successful upgrade.
   */
  public function testForumUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Make sure the field is created.
    $vocabulary = $this->container->get('config.factory')->get('forum.settings')->get('vocabulary');
    $field = field_info_instance('taxonomy_term', 'forum_container', $vocabulary);
    $this->assertTrue((bool) $field, 'Field was found');

    // Check that the values of forum_container are correct.
    $containers = entity_load_multiple_by_properties('taxonomy_term', array('name' => 'Container'));
    $container = reset($containers);
    $this->assertTrue((bool) $container->forum_container->value);

    $forums = entity_load_multiple_by_properties('taxonomy_term', array('name' => 'Forum'));
    $forum = reset($forums);
    $this->assertFalse((bool) $forum->forum_container->value);
  }

}
