<?php

namespace Drupal\Tests\tour\Functional\Block;

use Drupal\Tests\tour\Functional\TourTestBase;

/**
 * Tests the Block Layout tour.
 *
 * @group tour
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
