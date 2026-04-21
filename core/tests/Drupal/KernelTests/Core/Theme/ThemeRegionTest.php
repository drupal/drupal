<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests theme region listing.
 */
#[Group('Extension')]
#[RunTestsInSeparateProcesses]
class ThemeRegionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests listing a theme's regions.
   */
  public function testRegionListing(): void {
    // Install Stark.
    $this->container->get('theme_installer')->install(['stark']);
    $theme_handler = $this->container->get('theme_handler');
    $all_regions = $theme_handler->getTheme('stark')->listAllRegions();
    $visible_regions = $theme_handler->getTheme('stark')->listVisibleRegions();
    $this->assertArrayHasKey('page_top', $all_regions);
    $this->assertArrayHasKey('sidebar_first', $all_regions);
    $this->assertArrayNotHasKey('page_top', $visible_regions);
    $this->assertArrayHasKey('sidebar_first', $visible_regions);
    $this->assertEquals('sidebar_first', $theme_handler->getTheme('stark')->getDefaultRegion());
  }

  /**
   * Tests listing a theme's regions using legacy functions.
   */
  #[IgnoreDeprecations]
  public function testLegacyRegionListing(): void {
    $this->expectUserDeprecationMessage("system_region_list() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service('theme_handler')->getTheme()->listAllRegions() or \Drupal::service('theme_handler')->getTheme()->listVisibleRegions() instead. See https://www.drupal.org/node/3015925");
    $this->expectUserDeprecationMessage("system_default_region() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service('theme_handler')->getTheme()->getDefaultRegion() instead. See https://www.drupal.org/node/3015925");
    $all_regions = system_region_list('stark', REGIONS_ALL);
    $visible_regions = system_region_list('stark', REGIONS_VISIBLE);
    // There's no theme installed.
    $this->assertEmpty($all_regions);
    $this->assertEmpty($visible_regions);
    $this->assertEquals('', system_default_region('stark'));

    // Install Stark.
    $this->container->get('theme_installer')->install(['stark']);
    $all_regions = system_region_list('stark', REGIONS_ALL);
    $visible_regions = system_region_list('stark', REGIONS_VISIBLE);
    $this->assertArrayHasKey('page_top', $all_regions);
    $this->assertArrayHasKey('sidebar_first', $all_regions);
    $this->assertArrayNotHasKey('page_top', $visible_regions);
    $this->assertArrayHasKey('sidebar_first', $visible_regions);
    $this->assertEquals('sidebar_first', system_default_region('stark'));
  }

}
