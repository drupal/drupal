<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\tour\Functional\TourTestBase;

/**
 * Tests the Block Layout tour.
 *
 * @group block
 */
class BlockLayoutTourTest extends TourTestBase {

  /**
   * An admin user with administrative permissions for Blocks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'tour'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer blocks', 'access tour']);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests Block Layout tour tip availability.
   */
  public function testBlockLayoutTourTips() {
    $this->drupalGet('admin/structure/block');
    $this->assertTourTips();
  }

}
