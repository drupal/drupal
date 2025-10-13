<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the configurable language manager and locale operate correctly.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleConfigurableLanguageManagerTest extends KernelTestBase {

  /**
   * A list of modules to install for this test.
   *
   * @var array
   */
  protected static $modules = ['language', 'locale'];

  /**
   * Tests retrieving languages from the language manager.
   */
  public function testGetLanguages(): void {
    $this->installSchema('locale', ['locales_source', 'locales_target', 'locales_location']);
    $default_language = ConfigurableLanguage::create([
      'label' => $this->randomMachineName(),
      'id' => 'default',
      'weight' => 0,
    ]);
    $default_language->save();

    // Set new default language.
    \Drupal::service('language.default')->set($default_language);
    \Drupal::service('string_translation')->setDefaultLangcode($default_language->getId());

    $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_ALL);
    $this->assertEquals(['default', 'und', 'zxx'], array_keys($languages));

    $configurableLanguage = ConfigurableLanguage::create([
      'label' => $this->randomMachineName(),
      'id' => 'test',
      'weight' => 1,
    ]);
    // Simulate a configuration sync by setting the flag otherwise the locked
    // language weights would be updated whilst saving.
    // @see \Drupal\language\Entity\ConfigurableLanguage::postSave()
    $configurableLanguage->setSyncing(TRUE)->save();

    $languages = \Drupal::service('language_manager')->getLanguages(LanguageInterface::STATE_ALL);
    $this->assertEquals(['default', 'test', 'und', 'zxx'], array_keys($languages));
  }

}
