<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\DrupalKernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore contenedor fuera reiniciado Después

/**
 * Ensures that the container rebuild works as expected.
 *
 * @group DrupalKernel
 */
class ContainerResetWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['container_rebuild_test', 'locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('es')->save();
    // Create translations for testing.
    $locale_storage = $this->container->get('locale.storage');
    $langcode = 'es';
    $source = $locale_storage->createString(['source' => 'Before the container was reset.'])->save();
    $locale_storage->createTranslation([
      'lid' => $source->lid,
      'language' => $langcode,
      'translation' => 'Antes de que el contenedor fuera reiniciado.',
    ])->save();
    $source = $locale_storage->createString(['source' => 'After the container was reset.'])->save();
    $locale_storage->createTranslation([
      'lid' => $source->lid,
      'language' => $langcode,
      'translation' => 'Después de que el contenedor fue reiniciado.',
    ])->save();
  }

  /**
   * Sets a different deployment identifier.
   */
  public function testContainerRebuild(): void {
    $this->drupalLogin($this->drupalCreateUser());

    $this->drupalGet('container_rebuild_test/container_reset');
    $this->assertSession()->pageTextContains('Before the container was reset');
    $this->assertSession()->pageTextContains('After the container was reset');
    $this->drupalGet('es/container_rebuild_test/container_reset');
    $this->assertSession()->pageTextContains('Antes de que el contenedor fuera reiniciado.');
    $this->assertSession()->pageTextContains('Después de que el contenedor fue reiniciado.');
  }

}
