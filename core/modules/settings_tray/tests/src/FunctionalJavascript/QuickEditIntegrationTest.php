<?php

namespace Drupal\Tests\settings_tray\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\user\Entity\Role;

/**
 * Test Settings Tray and Quick Edit modules integration.
 *
 * @group settings_tray
 */
class QuickEditIntegrationTest extends SettingsTrayTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'block_content',
    'quickedit',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $user = $this->createUser([
      'administer blocks',
      'access contextual links',
      'access toolbar',
      'administer nodes',
      'access in-place editing',
    ]);
    $this->drupalLogin($user);

  }

  /**
   * Tests QuickEdit links behavior.
   */
  public function testQuickEditLinks() {
    $quick_edit_selector = '#quickedit-entity-toolbar';
    $node_selector = '[data-quickedit-entity-id="node/1"]';
    $body_selector = '[data-quickedit-field-id="node/1/body/en/full"]';
    $web_assert = $this->assertSession();
    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $auth_role = Role::load(Role::AUTHENTICATED_ID);
    $this->grantPermissions($auth_role, [
      'edit any page content',
      'access content',
    ]);
    $node = $this->createNode(
      [
        'title' => 'Page One',
        'type' => 'page',
        'body' => [
          [
            'value' => 'Regular NODE body for the test.',
            'format' => 'plain_text',
          ],
        ],
      ]
    );
    $page = $this->getSession()->getPage();
    $block_plugin = 'system_powered_by_block';

    foreach ($this->getTestThemes() as $theme) {

      $this->enableTheme($theme);

      $block = $this->placeBlock($block_plugin);
      $block_selector = $this->getBlockSelector($block);
      // Load the same page twice.
      foreach ([1, 2] as $page_load_times) {
        $this->drupalGet('node/' . $node->id());
        // The 2nd page load we should already be in edit mode.
        if ($page_load_times == 1) {
          $this->enableEditMode();
        }
        // In Edit mode clicking field should open QuickEdit toolbar.
        $page->find('css', $body_selector)->click();
        $this->assertElementVisibleAfterWait('css', $quick_edit_selector);

        $this->disableEditMode();
        // Exiting Edit mode should close QuickEdit toolbar.
        $web_assert->elementNotExists('css', $quick_edit_selector);
        // When not in Edit mode QuickEdit toolbar should not open.
        $page->find('css', $body_selector)->click();
        $web_assert->elementNotExists('css', $quick_edit_selector);
        $this->enableEditMode();
        $this->openBlockForm($block_selector);
        $page->find('css', $body_selector)->click();
        $this->assertElementVisibleAfterWait('css', $quick_edit_selector);
        // Off-canvas dialog should be closed when opening QuickEdit toolbar.
        $this->waitForOffCanvasToClose();

        $this->openBlockForm($block_selector);
        // QuickEdit toolbar should be closed when opening Off-canvas dialog.
        $web_assert->elementNotExists('css', $quick_edit_selector);
      }
      // Check using contextual links to invoke QuickEdit and open the tray.
      $this->drupalGet('node/' . $node->id());
      $web_assert->assertWaitOnAjaxRequest();
      $this->disableEditMode();
      // Open QuickEdit toolbar before going into Edit mode.
      $this->clickContextualLink($node_selector, "Quick edit");
      $this->assertElementVisibleAfterWait('css', $quick_edit_selector);
      // Open off-canvas and enter Edit mode via contextual link.
      $this->clickContextualLink($block_selector, "Quick edit");
      $this->waitForOffCanvasToOpen();
      // QuickEdit toolbar should be closed when opening off-canvas dialog.
      $web_assert->elementNotExists('css', $quick_edit_selector);
      // Open QuickEdit toolbar via contextual link while in Edit mode.
      $this->clickContextualLink($node_selector, "Quick edit", FALSE);
      $this->waitForOffCanvasToClose();
      $this->assertElementVisibleAfterWait('css', $quick_edit_selector);
      $this->disableEditMode();
    }
  }

  /**
   * Tests that contextual links in custom blocks are changed.
   *
   * "Quick edit" is quickedit.module link.
   * "Quick edit settings" is settings_tray.module link.
   */
  public function testCustomBlockLinks() {
    $this->createBlockContentType('basic', TRUE);
    $block_content = $this->createBlockContent('Custom Block', 'basic', TRUE);
    $this->placeBlock('block_content:' . $block_content->uuid(), ['id' => 'custom']);
    $this->drupalGet('user');
    $page = $this->getSession()->getPage();
    $this->toggleContextualTriggerVisibility('#block-custom');
    $page->find('css', '#block-custom .contextual button')->press();
    $links = $page->findAll('css', "#block-custom .contextual-links li a");
    $link_labels = [];
    /** @var \Behat\Mink\Element\NodeElement $link */
    foreach ($links as $link) {
      $link_labels[$link->getAttribute('href')] = $link->getText();
    }
    $href = array_search('Quick edit', $link_labels);
    $this->assertEquals('', $href);
    $href = array_search('Quick edit settings', $link_labels);
    $this->assertTrue(strstr($href, '/admin/structure/block/manage/custom/settings-tray?destination=user/2') !== FALSE);
  }

  /**
   * Creates a custom block.
   *
   * @param bool|string $title
   *   (optional) Title of block. When no value is given uses a random name.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'basic'.
   * @param bool $save
   *   (optional) Whether to save the block. Defaults to TRUE.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created custom block.
   */
  protected function createBlockContent($title = FALSE, $bundle = 'basic', $save = TRUE) {
    $title = $title ?: $this->randomName();
    $block_content = BlockContent::create([
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en',
      'body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ],
    ]);
    if ($block_content && $save === TRUE) {
      $block_content->save();
    }
    return $block_content;
  }

  /**
   * Creates a custom block type (bundle).
   *
   * @param string $label
   *   The block type label.
   * @param bool $create_body
   *   Whether or not to create the body field.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created custom block type.
   */
  protected function createBlockContentType($label, $create_body = FALSE) {
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => FALSE,
    ]);
    $bundle->save();
    if ($create_body) {
      block_content_add_body_field($bundle->id());
    }
    return $bundle;
  }

}
