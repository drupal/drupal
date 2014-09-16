<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleUpdateNotDevelopmentReleaseTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test for finding the first available normal core release version,
 * in case of core is a development release.
 *
 * @group language
 */
class LocaleUpdateNotDevelopmentReleaseTest extends WebTestBase {

  public static $modules = array('update', 'locale', 'locale_test_not_development_release');

  protected function setUp() {
    parent::setUp();
    module_load_include('compare.inc', 'locale');
    $admin_user = $this->drupalCreateUser(array('administer modules', 'administer languages', 'access administration pages', 'translate interface'));
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('admin/config/regional/language/add', array('predefined_langcode' => 'hu'), t('Add language'));
  }

  public function testLocaleUpdateNotDevelopmentRelease() {
    // Set available Drupal releases for test.
    $available = array(
      'title' => 'Drupal core',
      'short_name' => 'drupal',
      'type' => 'project_core',
      'api_version' => '8.x',
      'project_status' => 'unsupported',
      'link' => 'https://www.drupal.org/project/drupal',
      'terms' => '',
      'releases' => array(
        '8.0.0-alpha110' => array(
          'name'          => 'drupal 8.0.0-alpha110',
          'version'       => '8.0.0-alpha110',
          'tag'           => '8.0.0-alpha110',
          'version_major' => '8',
          'version_minor' => '0',
          'version_patch' => '0',
          'version_extra' => 'alpha110',
          'status'        => 'published',
          'release_link'  => 'https://www.drupal.org/node/2316617',
          'download_link' => 'http://ftp.drupal.org/files/projects/drupal-8.0.0-alpha110.tar.gz',
          'date'          => '1407344628',
          'mdhash'        => '9d71afdd0ce541f2ff5ca2fbbca00df7',
          'filesize'      => '9172832',
          'files'         => '',
          'terms'         => array(),
        ),
        '8.0.0-alpha100' => array(
          'name'          => 'drupal 8.0.0-alpha100',
          'version'       => '8.0.0-alpha100',
          'tag'           => '8.0.0-alpha100',
          'version_major' => '8',
          'version_minor' => '0',
          'version_patch' => '0',
          'version_extra' => 'alpha100',
          'status'        => 'published',
          'release_link'  => 'https://www.drupal.org/node/2316617',
          'download_link' => 'http://ftp.drupal.org/files/projects/drupal-8.0.0-alpha100.tar.gz',
          'date'          => '1407344628',
          'mdhash'        => '9d71afdd0ce541f2ff5ca2fbbca00df7',
          'filesize'      => '9172832',
          'files'         => '',
          'terms'         => array(),
        ),
      ),
    );
    $available['last_fetch'] = REQUEST_TIME;
    \Drupal::keyValueExpirable('update_available_releases')->setWithExpire('drupal', $available, 10);
    $projects = locale_translation_build_projects();
    $this->verbose($projects['drupal']->info['version']);
    $this->assertEqual($projects['drupal']->info['version'], '8.0.0-alpha110', 'The first release with the same major release number which is not a development release.');
  }
}
