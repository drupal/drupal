<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests locale.bulk.inc.
 */
#[Group('locale')]
class LocaleBulkDeprecationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('locale.config_manager', $this->createMock('Drupal\locale\LocaleConfigManager'));
    $language_manager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->any())
      ->method('getDefaultLanguage')
      ->willReturn($this->createMock('Drupal\Core\Language\LanguageInterface'));
    $container->set('language_manager', $language_manager);

    \Drupal::setContainer($container);

    include_once DRUPAL_ROOT . '/core/modules/locale/locale.bulk.inc';
  }

  /**
   * Tests the deprecation of locale_config_batch_refresh_name().
   *
   * @see locale_config_batch_refresh_name()
   */
  #[IgnoreDeprecations]
  public function testDeprecatedLocaleConfigBatchRefreshName(): void {
    $this->expectDeprecation('locale_config_batch_refresh_name() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use locale_config_batch_update_config_translations() instead. See https://www.drupal.org/node/3475054');
    $names = ['English', 'German'];
    $langcodes = ['en', 'de'];
    locale_config_batch_refresh_name($names, $langcodes, $context);
  }

  /**
   * Tests the deprecation of locale_config_batch_set_config_langcodes().
   *
   * @see locale_config_batch_set_config_langcodes()
   */
  #[IgnoreDeprecations]
  public function testDeprecatedLocaleConfigBatchSetConfigLangcodes(): void {
    $this->expectDeprecation('locale_config_batch_set_config_langcodes() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use locale_config_batch_update_default_config_langcodes() instead. See https://www.drupal.org/node/3475054');
    $context = [];
    locale_config_batch_set_config_langcodes($context);
  }

}
