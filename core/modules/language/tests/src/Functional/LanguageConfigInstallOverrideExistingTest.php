<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensures the language config is installed but not altered on install.
 *
 * @group language
 */
class LanguageConfigInstallOverrideExistingTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'test_language_negotiation';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests when language config is installed existing config is not overridden.
   */
  public function testLanguageConfigInstallOverrideExisting(): void {
    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $this->container->get('config.storage');
    $config = $this->config('language.types');

    // The negotiation methods that have been removed should be disabled after
    // purging if not avoided in language_modules_installed().
    $language_types_data = $storage->read('language.types');
    $this->assertTrue(isset($language_types_data['negotiation']['language_content']['enabled']['test_language_negotiation_method']));
    $this->assertTrue(isset($language_types_data['negotiation']['language_content']['enabled']['language-selected']));

    $this->assertEquals(-10, $config->get('negotiation.language_content.enabled.test_language_negotiation_method'));
    $this->assertEquals(12, $config->get('negotiation.language_content.enabled.language-selected'));
  }

}
