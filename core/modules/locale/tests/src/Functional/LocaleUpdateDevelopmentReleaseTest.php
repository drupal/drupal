<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test for proper version fallback in case of a development release.
 *
 * @group language
 */
class LocaleUpdateDevelopmentReleaseTest extends BrowserTestBase {

  protected static $modules = ['locale', 'locale_test_development_release'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    module_load_include('compare.inc', 'locale');
    $admin_user = $this->drupalCreateUser(['administer modules', 'administer languages', 'access administration pages', 'translate interface']);
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => 'hu'], t('Add language'));
  }

  public function testLocaleUpdateDevelopmentRelease() {
    $projects = locale_translation_build_projects();
    $this->verbose($projects['drupal']->info['version']);
    $this->assertEqual($projects['drupal']->info['version'], '8.0.x', 'The branch of the core dev release.');
    $this->verbose($projects['contrib']->info['version']);
    $this->assertEqual($projects['contrib']->info['version'], '12.x-10.x', 'The branch of the contrib module dev release.');
  }

}
