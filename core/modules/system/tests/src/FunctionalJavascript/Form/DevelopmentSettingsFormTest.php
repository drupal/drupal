<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Form;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests development settings form items for expected behavior.
 *
 * @group Form
 */
class DevelopmentSettingsFormTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'dynamic_page_cache', 'page_cache'];

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
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests turning on Twig development mode.
   *
   * @dataProvider twigDevelopmentData
   */
  public function testTwigDevelopmentMode(bool $twig_development_mode, ?bool $twig_debug, ?bool $twig_cache_disable): void {
    $twig_debug = $twig_debug ?? $twig_development_mode;
    $twig_cache_disable = $twig_cache_disable ?? $twig_development_mode;

    $twig_config = \Drupal::getContainer()->getParameter('twig.config');
    self::assertFalse($twig_config['debug']);
    self::assertNull($twig_config['auto_reload']);
    self::assertTrue($twig_config['cache']);

    $this->drupalGet(Url::fromRoute('system.development_settings'));
    if ($twig_development_mode) {
      $this->getSession()->getPage()->checkField('Twig development mode');
      $this->assertSession()->checkboxChecked('Twig debug mode');
      $this->assertSession()->checkboxChecked('Disable Twig cache');
    }
    if (!$twig_debug) {
      $this->getSession()->getPage()->uncheckField('Twig debug mode');
    }
    if (!$twig_cache_disable) {
      $this->getSession()->getPage()->uncheckField('Disable Twig cache');
    }
    $this->getSession()->getPage()->pressButton('Save settings');

    $this->drupalGet(Url::fromRoute('system.status'));
    if (!$twig_development_mode) {
      $this->assertSession()->pageTextNotContains('Twig development mode');
    }
    else {
      $this->assertSession()->pageTextContains('Twig development mode');
      $this->assertSession()->linkExists('development settings page');
    }

    $refreshed_container = $this->initKernel(Request::create('/'));
    $twig_config = $refreshed_container->getParameter('twig.config');
    self::assertEquals($twig_debug, $twig_config['debug']);
    self::assertNull($twig_config['auto_reload']);
    self::assertEquals(!$twig_cache_disable, $twig_config['cache']);
  }

  /**
   * Test data for Twig development mode.
   *
   * @return array[]
   *   An array of test data.
   */
  public static function twigDevelopmentData(): array {
    return [
      'Twig development mode checked only' => [
        TRUE,
        NULL,
        NULL,
      ],
      'Twig debug mode only, keep Twig cache' => [
        TRUE,
        TRUE,
        FALSE,
      ],
      'Twig debug mode off, disable Twig cache' => [
        TRUE,
        FALSE,
        TRUE,
      ],
      'No changes' => [
        FALSE,
        NULL,
        NULL,
      ],
    ];
  }

  /**
   * Tests disabling cache bins which cache markup.
   */
  public function testDisabledRenderedOutputCacheBins(): void {
    self::assertFalse(\Drupal::getContainer()->has('cache.backend.null'));

    $this->drupalGet(Url::fromRoute('system.development_settings'));
    $this->getSession()->getPage()->checkField('Do not cache markup');
    $this->getSession()->getPage()->pressButton('Save settings');

    $this->drupalGet(Url::fromRoute('system.status'));
    $this->assertSession()->pageTextContains('Markup caching disabled');
    $this->assertSession()->linkExists('development settings page');

    $refreshed_container = $this->initKernel(Request::create('/'));
    self::assertTrue($refreshed_container->has('cache.backend.null'));
    $cache_bins = ['page', 'dynamic_page_cache', 'render'];
    foreach ($cache_bins as $cache_bin) {
      self::assertInstanceOf(NullBackend::class, $refreshed_container->get("cache.$cache_bin"));
    }
  }

}
