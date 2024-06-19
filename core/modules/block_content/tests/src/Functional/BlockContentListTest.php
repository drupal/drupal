<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;

/**
 * Tests the listing of content blocks.
 *
 * Tests the fallback block content list when Views is disabled.
 *
 * @group block_content
 * @see \Drupal\block\BlockContentListBuilder
 * @see \Drupal\block_content\Tests\BlockContentListViewsTest
 */
class BlockContentListTest extends BlockContentTestBase {

  /**
   * A user with 'access block library' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser1;

  /**
   * A user with access to create and edit custom basic blocks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser2;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer blocks',
    'access block library',
    'create basic block content',
    'edit any basic block content',
    'delete any basic block content',
    'translate configuration',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_content', 'config_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->baseUser1 = $this->drupalCreateUser(['access block library']);
    $this->baseUser2 = $this->drupalCreateUser([
      'access block library',
      'create basic block content',
      'edit any basic block content',
      'delete any basic block content',
    ]);
  }

  /**
   * Tests the region value when a new block is saved.
   */
  public function testBlockRegionPlacement(): void {
    $this->drupalLogin($this->drupalCreateUser($this->permissions));
    $this->drupalGet("admin/structure/block/library/stark", ['query' => ['region' => 'content']]);

    $this->clickLink('Add content block');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'info[0][value]' => 'foo',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->fieldValueEquals('region', 'content');
  }

  /**
   * Tests the content block listing page with different permissions.
   */
  public function testListing(): void {
    // Test with the admin user.
    $this->drupalLogin($this->drupalCreateUser(['access block library', 'administer block content']));
    $this->drupalGet('admin/content/block');

    // Test for the page title.
    $this->assertSession()->titleEquals('Content blocks | Drupal');

    // Test for the table.
    $this->assertSession()->elementExists('xpath', '//div[@class="layout-content"]//table');

    // Test the table header, two cells should be present.
    $this->assertSession()->elementsCount('xpath', '//div[@class="layout-content"]//table/thead/tr/th', 2);

    // Test the contents of each th cell.
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[1]', 'Block description');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[2]', 'Operations');

    $label = 'Antelope';
    $new_label = 'Albatross';
    // Add a new entity using the operations link.
    $this->clickLink('Add content block');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [];
    $edit['info[0][value]'] = $label;
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->submitForm($edit, 'Save');

    // Confirm that once the user returns to the listing, the text of the label
    // (versus elsewhere on the page).
    $this->assertSession()->elementTextContains('xpath', '//td', $label);

    // Check the number of table row cells.
    $this->assertSession()->elementsCount('xpath', '//div[@class="layout-content"]//table/tbody/tr[1]/td', 2);
    // Check the contents of the row. The first cell contains the label,
    // and the second contains the operations list.
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/tbody/tr[1]/td[1]', $label);

    // Edit the entity using the operations link.
    $blocks = $this->container
      ->get('entity_type.manager')
      ->getStorage('block_content')
      ->loadByProperties(['info' => $label]);
    $block = reset($blocks);
    if (!empty($block)) {
      $this->assertSession()->linkByHrefExists('admin/content/block/' . $block->id());
      $this->clickLink('Edit');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->titleEquals("Edit content block $label | Drupal");
      $edit = ['info[0][value]' => $new_label];
      $this->submitForm($edit, 'Save');
    }
    else {
      $this->fail('Did not find Albatross block in the database.');
    }

    // Confirm that once the user returns to the listing, the text of the label
    // (versus elsewhere on the page).
    $this->assertSession()->elementTextContains('xpath', '//td', $new_label);

    // Delete the added entity using the operations link.
    $this->assertSession()->linkByHrefExists('admin/content/block/' . $block->id() . '/delete');
    $this->clickLink('Delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals("Are you sure you want to delete the content block $new_label? | Drupal");
    $this->submitForm([], 'Delete');

    // Verify that the text of the label and machine name does not appear in
    // the list (though it may appear elsewhere on the page).
    $this->assertSession()->elementTextNotContains('xpath', '//td', $new_label);

    // Confirm that the empty text is displayed.
    $this->assertSession()->pageTextContains('There are no content blocks yet.');

    $block_content = BlockContent::create([
      'info' => 'Non-reusable block',
      'type' => 'basic',
      'reusable' => FALSE,
    ]);
    $block_content->save();

    $this->drupalGet('admin/content/block');
    // Confirm that the empty text is displayed.
    $this->assertSession()->pageTextContains('There are no content blocks yet.');
    // Confirm the non-reusable block is not on the page.
    $this->assertSession()->pageTextNotContains('Non-reusable block');

    $this->drupalLogout();

    // Create test block for other user tests.
    $test_block = $this->createBlockContent($label);

    $link_text = t('Add content block');
    // Test as a user with view only permissions.
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet('admin/content/block');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists($link_text);
    $this->assertSession()->linkByHrefNotExists('admin/content/block/' . $test_block->id());
    $this->assertSession()->linkByHrefNotExists('admin/content/block/' . $test_block->id() . '/delete');

    $this->drupalLogout();

    // Test as a user with permission to create/edit/delete basic blocks.
    $this->drupalLogin($this->baseUser2);
    $this->drupalGet('admin/content/block');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists($link_text);
    $this->assertSession()->linkByHrefExists('admin/content/block/' . $test_block->id());
    $this->assertSession()->linkByHrefExists('admin/content/block/' . $test_block->id() . '/delete');
  }

}
