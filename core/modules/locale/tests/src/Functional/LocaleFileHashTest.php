<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\locale\LocaleSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore zusatz

/**
 * Tests that file hash is used for local translation file change detection.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleFileHashTest extends LocaleUpdateBase {

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
      'administer site configuration',
      'administer languages',
      'access administration pages',
      'translate interface',
    ]);
    $this->drupalLogin($admin_user);
    $this->addLanguage('de');

    // Import the translations via the UI.
    $this->setTranslationFiles();
    $this->setCurrentTranslations();

    $edit = [
      'use_source' => LOCALE_TRANSLATION_USE_SOURCE_LOCAL,
      'overwrite' => LOCALE_TRANSLATION_OVERWRITE_ALL,
    ];
    $this->drupalGet('admin/config/regional/translate/settings');
    $this->submitForm($edit, 'Save configuration');

    // Check for available translations and update them via the UI.
    $this->drupalGet('admin/reports/translations/check');
    $this->assertSession()->addressEquals('admin/reports/translations');
    $this->submitForm([], 'Update translations');
  }

  /**
   * Tests that modifying a file produces a different hash, detected by the UI.
   */
  public function testModifiedFileProducesDifferentHash(): void {
    // Check for translation updates via the UI.
    $this->drupalGet('admin/reports/translations/check');
    // The translation status page should show no updates are available.
    $this->assertSession()->addressEquals('admin/reports/translations');
    $this->assertSession()->pageTextNotContains('Updates for:');
    $this->assertSession()->pageTextContains('All translations up to date.');

    // Check the stored hash after import.
    $uri = 'translations://contrib_module_two-8.x-2.0-beta4.de._po';
    $initial_hash = hash_file(LocaleSource::LOCAL_FILE_HASH_ALGO, $uri);
    $this->assertHashes($initial_hash, $initial_hash, 'contrib_module_two', 'de');

    // Change the mtime of the file to show that only the hash is used for local
    // files.
    touch($uri, time() + 20000);

    // Run check again via the UI.
    $this->drupalGet('admin/reports/translations/check');
    // The translation status page should show no updates are available.
    $this->assertSession()->addressEquals('admin/reports/translations');
    $this->assertSession()->pageTextNotContains('Updates for:');
    $this->assertSession()->pageTextContains('All translations up to date.');

    // Modify the content.
    file_put_contents($uri, "\nmsgid \"Extra\"\nmsgstr \"Zusatz\"\n", FILE_APPEND);
    // Set the mtime to the previous mtime to prove the hash is causing the
    // update.
    touch($uri, filemtime($uri));

    // Run check again via the UI.
    $this->drupalGet('admin/reports/translations/check');
    // The translation status page should show an update is available.
    $this->assertSession()->addressEquals('admin/reports/translations');
    $this->assertSession()->pageTextContains('Updates for: Contributed module two');

    // The hash computed by sourceCheckFile should differ because the file
    // content changed.
    $expected_hash = hash_file(LocaleSource::LOCAL_FILE_HASH_ALGO, $uri);
    $this->assertHashes($initial_hash, $expected_hash, 'contrib_module_two', 'de');

    $this->submitForm([], 'Update translations');

    // Check the stored hash after import.
    $this->assertHashes($expected_hash, $expected_hash, 'contrib_module_two', 'de');

    // Check for translation updates via the UI.
    $this->drupalGet('admin/reports/translations/check');
    // The translation status page should show no updates are available.
    $this->assertSession()->addressEquals('admin/reports/translations');
    $this->assertSession()->pageTextNotContains('Updates for:');
    $this->assertSession()->pageTextContains('All translations up to date.');

    // Change the mtime of the file and empty the hash to prove that fallback
    // works.
    touch($uri, time() + 20000);
    $status = locale_translation_get_status(['contrib_module_two']);
    $status['contrib_module_two']['de']->hash = '';
    $status['contrib_module_two']['de']->files[LOCALE_TRANSLATION_LOCAL]->hash = '';
    \Drupal::keyValue('locale.translation_status')->set('contrib_module_two', $status['contrib_module_two']);

    // Test fallback to mtime if the hash is not available.
    $this->drupalGet('admin/reports/translations/check');
    // The translation status page should show no updates are available.
    $this->assertSession()->addressEquals('admin/reports/translations');
    $this->assertSession()->pageTextContains('Updates for: Contributed module two');
  }

  /**
   * Checks that the stored hash values are as expected.
   *
   * @param string $history_hash
   *   The expected hash value in the locale_file table.
   * @param string $status_hash
   *   The expected hash in the translation status key value store.
   * @param string $project
   *   The translation project.
   * @param string $langcode
   *   The langcode.
   */
  public function assertHashes(string $history_hash, string $status_hash, string $project, string $langcode): void {
    drupal_static_reset('locale_translation_get_file_history');
    $history = locale_translation_get_file_history();
    $this->assertSame($history_hash, $history[$project][$langcode]->hash);
    $status = locale_translation_get_status([$project]);
    $this->assertSame($status_hash, $status[$project][$langcode]->hash);
    $this->assertSame($status_hash, $status[$project][$langcode]->files[LOCALE_TRANSLATION_LOCAL]->hash);
  }

}
