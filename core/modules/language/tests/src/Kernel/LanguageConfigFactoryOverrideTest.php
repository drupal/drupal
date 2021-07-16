<?php

namespace Drupal\Tests\language\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests \Drupal\language\Config\LanguageConfigFactoryOverride.
 *
 * @group language
 */
class LanguageConfigFactoryOverrideTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'language'];

  /**
   * Tests language.config_factory_override service has the default language.
   */
  public function testLanguageConfigFactoryOverride() {
    $this->installConfig('system');
    $this->installConfig('language');

    /** @var \Drupal\language\Config\LanguageConfigFactoryOverride $config_factory_override */
    $config_factory_override = \Drupal::service('language.config_factory_override');
    $this->assertEquals('en', $config_factory_override->getLanguage()->getId());

    ConfigurableLanguage::createFromLangcode('de')->save();

    // Invalidate the container.
    $this->config('system.site')->set('default_langcode', 'de')->save();
    $this->container->get('kernel')->rebuildContainer();

    $config_factory_override = \Drupal::service('language.config_factory_override');
    $this->assertEquals('de', $config_factory_override->getLanguage()->getId());
  }

}
