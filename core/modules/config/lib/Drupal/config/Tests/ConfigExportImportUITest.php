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

  /**
   * Sort methods alphabetically in order to allow for a predictable sequence.
   */
  const SORT_METHODS = TRUE;

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
   * Tests a simple site configuration export case: site slogan.
   */
  function testExport() {
    // Create a role for second round.
    $this->admin_role = $this->drupalCreateRole(array('synchronize configuration', 'import configuration'));
    $this->slogan = $this->randomString(16);
    \Drupal::config('system.site')
      ->set('slogan', $this->slogan)
      ->save();
    $this->drupalPostForm('admin/config/development/configuration/export', array(), 'Export');
    $this->tarball = $this->drupalGetContent();
  }

  /**
   * Tests importing the tarball to ensure changes made it over.
   */
  function testImport() {
    $filename = 'temporary://' . $this->randomName();
    file_put_contents($filename, $this->tarball);
    $this->doImport($filename);
    // Now that the role is imported, change the slogan and re-import with a non-root user.
    $web_user = $this->drupalCreateUser();
    $web_user->addRole($this->admin_role);
    $web_user->save();
    $this->drupalLogin($web_user);
    \Drupal::config('system.site')
      ->set('slogan', $this->randomString(16))
      ->save();
    $this->doImport($filename);
  }

  /**
   * Import a tarball and assert the data is correct.
   *
   * @param string $filename
   *   The name of the tarball containing the configuration to be imported.
   */
  protected function doImport($filename) {
    $this->assertNotEqual($this->slogan, \Drupal::config('system.site')->get('slogan'));
    $this->drupalPostForm('admin/config/development/configuration/import', array('files[import_tarball]' => $filename), 'Upload');
    $this->drupalPostForm(NULL, array(), 'Import all');
    $this->assertEqual($this->slogan, \Drupal::config('system.site')->get('slogan'));
  }
}
