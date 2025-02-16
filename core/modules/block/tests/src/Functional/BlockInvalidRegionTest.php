<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests that blocks assigned to invalid regions are disabled with a warning.
 *
 * @group block
 */
class BlockInvalidRegionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_test'];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // This block is intentionally put in an invalid region, so it will violate
    // config schema.
    // @see ::testBlockInvalidRegion()
    'block.block.invalid_region',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create an admin user.
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that blocks assigned to invalid regions work correctly.
   */
  public function testBlockInInvalidRegion(): void {
    // Enable a test block and place it in an invalid region.
    $block = $this->drupalPlaceBlock('test_html', ['id' => 'invalid_region']);
    \Drupal::configFactory()->getEditable('block.block.' . $block->id())->set('region', 'invalid_region')->save();
    $block = Block::load($block->id());

    $warning_message = 'The block ' . $block->id() . ' was assigned to the invalid region invalid_region and has been disabled.';

    // Clearing the cache should disable the test block placed in the invalid
    // region.
    $this->drupalGet('admin/config/development/performance');
    $this->submitForm([], 'Clear all caches');
    $this->assertSession()->statusMessageContains($warning_message, 'warning');

    // Clear the cache to check if the warning message is not triggered.
    $this->drupalGet('admin/config/development/performance');
    $this->submitForm([], 'Clear all caches');
    $this->assertSession()->statusMessageNotContains($warning_message, 'warning');

    // Place disabled test block in the invalid region of the default theme.
    \Drupal::configFactory()->getEditable('block.block.' . $block->id())->set('region', 'invalid_region')->save();
    $block = Block::load($block->id());

    // Clear the cache to check if the warning message is not triggered.
    $this->drupalGet('admin/config/development/performance');
    $this->submitForm([], 'Clear all caches');
    $this->assertSession()->statusMessageNotContains($warning_message, 'warning');
  }

}
