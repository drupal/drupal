<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigExportImportUITest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Performs various configuration import/export scenarios through the UI.
 *
 * Each testX method does a complete rebuild of a Drupal site, so values being
 * tested need to be stored in protected properties in order to survive until
 * the next rebuild.
 *
 * Each testX method is executed alphabetically, so naming is important.
 */
class ConfigExportImportUITest extends WebTestBase {

  /**
   * The site UUID.
   *
   * @var string
   */
  protected $siteUuid;

  /**
   * The slogan value, for a simple export test case.
   *
   * @var string
   */
  protected $slogan;

  /**
   * The contents of the config export tarball, held between test methods.
   *
   * @var string
   */
  protected $tarball;

  /**
   * The name of the role with config UI administer permissions.
   *
   * @var string
   */
  protected $admin_role;

  public static $modules = array('config');

  public static function getInfo() {
    return array(
      'name' => 'Export/import UI',
      'description' => 'Tests the user interface for importing/exporting configuration.',
      'group' => 'Configuration',
    );
  }

  protected function setUp() {
    parent::setUp();
    // The initial import must be done with uid 1 because if separately named
    // roles are created then the role is lost after import. If the roles
    // created have the same name then the sync will fail because they will
    // have different UUIDs.
    $this->drupalLogin($this->root_user);
  }

  /**
   * Tests a simple site export import case.
   */
  public function testExportImport() {
    $this->originalSlogan = \Drupal::config('system.site')->get('slogan');
    $this->newSlogan = $this->randomString(16);
    $this->assertNotEqual($this->newSlogan, $this->originalSlogan);
    \Drupal::config('system.site')
      ->set('slogan', $this->newSlogan)
      ->save();
    $this->assertEqual(\Drupal::config('system.site')->get('slogan'), $this->newSlogan);

    $this->drupalPostForm('admin/config/development/configuration/full/export', array(), 'Export');
    $this->tarball = $this->drupalGetContent();

    \Drupal::config('system.site')
      ->set('slogan', $this->originalSlogan)
      ->save();
    $this->assertEqual(\Drupal::config('system.site')->get('slogan'), $this->originalSlogan);

    $filename = 'temporary://' . $this->randomName();
    file_put_contents($filename, $this->tarball);
    $this->drupalPostForm('admin/config/development/configuration/full/import', array('files[import_tarball]' => $filename), 'Upload');
    $this->drupalPostForm(NULL, array(), 'Import all');

    $this->assertEqual(\Drupal::config('system.site')->get('slogan'), $this->newSlogan);
  }
}

