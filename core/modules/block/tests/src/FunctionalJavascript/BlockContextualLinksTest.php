<?php

declare(strict_types=1);

namespace Drupal\Tests\block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the contextual links added while rendering the block.
 *
 * @group block
 */
class BlockContextualLinksTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block', 'contextual'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Block id of the block.
   *
   * @var string
   */
  protected $blockId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->blockId = $this->defaultTheme . '_powered';
    $this->placeBlock('system_powered_by_block', [
      'id' => $this->blockId,
      'region' => 'content',
    ]);
  }

  /**
   * Test to ensure that remove contextual link is present in the block.
   */
  public function testBlockContextualRemoveLinks() {
    // Ensure that contextual filter links are visible on the page.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('<front>');
    $contextual_id = "[data-contextual-id^='block:block=$this->blockId:langcode=en']";
    $this->assertSession()->waitForElement('css', "$contextual_id .contextual-links");

    $expected_configure_block_link = base_path() . 'admin/structure/block/manage/' . $this->blockId;
    $actual_configure_block_link = parse_url($this->getSession()->getPage()->findLink('Configure block')->getAttribute('href'));
    $this->assertEquals($expected_configure_block_link, $actual_configure_block_link['path']);

    $expected_remove_block_link = base_path() . 'admin/structure/block/manage/' . $this->blockId . '/delete';
    $actual_remove_block_link = parse_url($this->getSession()->getPage()->findLink('Remove block')->getAttribute('href'));
    $this->assertEquals($expected_remove_block_link, $actual_remove_block_link['path']);
  }

}
