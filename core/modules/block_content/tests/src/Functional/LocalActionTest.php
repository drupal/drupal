<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

/**
 * Tests block_content local action links.
 *
 * @group block_content
 */
class LocalActionTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the block_content_add_action link.
   */
  public function testAddContentBlockLink(): void {
    // Verify that the link takes you straight to the block form if there's only
    // one type.
    $this->drupalGet('/admin/content/block');
    $this->clickLink('Add content block');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/block/add/basic');

    $type = $this->randomMachineName();
    $this->createBlockContentType([
      'id' => $type,
      'label' => $type,
    ]);

    // Verify that the link takes you to the block add page if there's more than
    // one type.
    $this->drupalGet('/admin/content/block');
    $this->clickLink('Add content block');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/block/add');
  }

}
