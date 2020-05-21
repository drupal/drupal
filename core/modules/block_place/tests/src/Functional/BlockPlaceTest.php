<?php

namespace Drupal\Tests\block_place\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the placing a block.
 *
 * @group block_place
 * @group legacy
 */
class BlockPlaceTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'block_place', 'toolbar'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests placing blocks as an admin.
   */
  public function testPlacingBlocksAdmin() {
    // Create administrative user.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'access toolbar',
      'administer blocks',
      'view the administration theme',
    ]));
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->clickLink('Place block');

    // Each region should have one link to place a block.
    $theme_name = $this->container->get('theme.manager')->getActiveTheme()->getName();
    $visible_regions = system_region_list($theme_name, REGIONS_VISIBLE);
    $this->assertGreaterThan(0, count($visible_regions));

    $default_theme = $this->config('system.theme')->get('default');
    $block_library_url = Url::fromRoute('block.admin_library', ['theme' => $default_theme]);
    foreach ($visible_regions as $region => $name) {
      $block_library_url->setOption('query', ['region' => $region]);
      $links = $this->xpath('//a[contains(@href, :href)]', [':href' => $block_library_url->toString()]);
      $this->assertCount(1, $links);

      list(, $query_string) = explode('?', $links[0]->getAttribute('href'), 2);
      parse_str($query_string, $query_parts);
      $this->assertNotEmpty($query_parts['destination']);

      // Get the text inside the div->a->span->em.
      $demo_block = $this->xpath('//div[@class="block-place-region"]/a/span[text()="Place block in the "]/em[text()="' . $name . '"]');
      $this->assertCount(1, $demo_block);
    }
  }

  /**
   * Tests placing blocks as an unprivileged user.
   */
  public function testPlacingBlocksUnprivileged() {
    // Create a user who cannot administer blocks.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'access toolbar',
      'view the administration theme',
    ]));
    $this->drupalGet(Url::fromRoute('<front>'));
    $links = $this->xpath('//a[text()=:label]', [':label' => 'Place block']);
    $this->assertEmpty($links);

    $this->drupalGet(Url::fromRoute('block.admin_library', ['theme' => 'classy']));
    $this->assertSession()->statusCodeEquals(403);
  }

}
