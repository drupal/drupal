<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\locale\LocaleProjectRepository;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test for proper version fallback in case of a development release.
 */
#[Group('language')]
#[RunTestsInSeparateProcesses]
#[CoversMethod(LocaleProjectRepository::class, 'buildProjects')]
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

  /**
   * Tests locale update development release.
   */
  public function testLocaleUpdateDevelopmentRelease(): void {
    $projects = \Drupal::service(LocaleProjectRepository::class)->buildProjects();
    $this->assertEquals('8.0.0-alpha102-dev', $projects['drupal']->info['version'], 'The branch of the core dev release.');
    $this->assertEquals('12.x-10.x', $projects['contrib']->info['version'], 'The branch of the contrib module dev release.');
  }

}
