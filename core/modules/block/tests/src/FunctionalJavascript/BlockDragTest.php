<?php

declare(strict_types=1);

namespace Drupal\Tests\block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests drag and drop blocks on block layout page.
 *
 * @group block
 */
class BlockDragTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests drag and drop blocks.
   */
  public function testDragAndDropBlocks(): void {
    // Resize window to work around https://github.com/bitovi/syn/issues/164.
    $this->getSession()->resizeWindow(1024, 2048);
    $this->drupalGet('admin/structure/block');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    // Test if drag orientation on block layout page was applied with success.
    $this->assertNotEmpty($assertSession->waitForElementVisible('css', '.tabledrag-handle-y'));

    // Dragging main-menu and status messages to header region.
    $siteBranding = $this->getDragRow($page, 'edit-blocks-olivero-site-branding');
    $mainMenuRow = $this->getDragRow($page, 'edit-blocks-olivero-main-menu');
    $mainMenuRow->dragTo($siteBranding);
    $messages = $this->getDragRow($page, 'edit-blocks-olivero-messages');
    $messages->dragTo($siteBranding);

    // Test if both blocks above was positioned on the header region.
    $this->assertEquals(
      'header',
      $page->findField('edit-blocks-olivero-main-menu-region')->getValue(),
      'Main menu should be positioned on header region'
    );
    $this->assertEquals(
      'header',
      $page->findField('edit-blocks-olivero-messages-region')->getValue(),
      'Status messages should be positioned on header region'
    );

    // Check if the message unsaved changed appears.
    $assertSession->pageTextContains('You have unsaved changes.');

    // Test if the message for empty regions appear after drag the unique block on the region.
    $noBlockMessage = $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-primary-menu-message"] td')->getText();
    $this->assertSession()->assert($noBlockMessage === 'No blocks in this region', 'Region primary menu should be empty.');

    // Testing drag row to an empty region.
    $pageTitle = $this->getDragRow($page, 'edit-blocks-olivero-page-title');
    $heroRegion = $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-hero-message"]');
    $pageTitle->dragTo($heroRegion);
    $this->assertSession()->assert(
      $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-hero-message"] td')->getText() !== 'No blocks in this region',
      "Region here shouldn't be empty"
    );

  }

  /**
   * Helper function to find block tr element on the page.
   */
  private function getDragRow($page, $blockId) {
    return $page->find('css', '#blocks tbody tr[data-drupal-selector="' . $blockId . '"] a.tabledrag-handle');
  }

}
