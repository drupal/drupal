<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test for proper version fallback in case of a development release.
 *
 * @group language
 */
class LocaleUpdateDevelopmentReleaseTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['locale', 'locale_test_development_release'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.compare');
    $admin_user = $this->drupalCreateUser([
      'administer modules',
      'administer languages',
      'access administration pages',
      'translate interface',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'hu'], 'Add language');
  }

  public function testLocaleUpdateDevelopmentRelease(): void {
    $projects = locale_translation_build_projects();
    $this->assertEquals('8.0.x', $projects['drupal']->info['version'], 'The branch of the core dev release.');
    $this->assertEquals('12.x-10.x', $projects['contrib']->info['version'], 'The branch of the contrib module dev release.');
  }

}
