<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Edit a node when you don't have permission to add or edit menu links.
 *
 * @group menu_ui
 */
class MenuUiNodeAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_ui',
    'test_page_test',
    'node',
    'menu_link_access_test',
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

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
  }

  /**
   * Tests menu link create access is enforced.
   */
  public function testMenuLinkCreateAccess(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer menu',
      'edit any page content',
    ]));
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => $this->rootUser->id(),
      'status' => 1,
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->elementNotExists('css', 'input[name="menu[title]"]');
  }

  /**
   * Tests menu link edit/delete access is enforced.
   */
  public function testMenuLinkEditAccess(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer menu',
      'edit any page content',
    ]));
    $mainLinkTitle = $this->randomMachineName();
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => $this->rootUser->id(),
      'status' => 1,
    ]);
    $node->save();
    MenuLinkContent::create([
      'link' => [['uri' => 'entity:node/' . $node->id()]],
      'title' => $mainLinkTitle,
      'menu_name' => 'main',
    ])->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->elementNotExists('css', 'input[name="menu[title]"]');
  }

}
