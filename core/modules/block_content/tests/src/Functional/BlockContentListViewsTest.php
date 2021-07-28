<?php

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;

/**
 * Tests the Views-powered listing of custom blocks.
 *
 * @group block_content
 * @see \Drupal\block\BlockContentListBuilder
 * @see \Drupal\block_content\Tests\BlockContentListTest
 */
class BlockContentListViewsTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'block_content',
    'config_translation',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the custom block listing page.
   */
  public function testListing() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
      'translate configuration',
    ]));
    $this->drupalGet('admin/structure/block/block-content');

    // Test for the page title.
    $this->assertSession()->titleEquals('Custom block library | Drupal');

    // Test for the exposed filters.
    $this->assertSession()->fieldExists('info');
    $this->assertSession()->fieldExists('type');

    // Test for the table.
    $this->assertSession()->elementExists('xpath', '//div[@class="layout-content"]//table');

    // Test the table header, four cells should be present.
    $this->assertSession()->elementsCount('xpath', '//div[@class="layout-content"]//table/thead/tr/th', 4);

    // Test the contents of each th cell.
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[1]', 'Block description');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[2]', 'Block type');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[3]', 'Updated Sort ascending');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/thead/tr/th[4]', 'Operations');

    $label = 'Antelope';
    $new_label = 'Albatross';
    // Add a new entity using the operations link.
    $this->clickLink('Add custom block');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [];
    $edit['info[0][value]'] = $label;
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->submitForm($edit, 'Save');

    // Confirm that once the user returns to the listing, the text of the label
    // (versus elsewhere on the page).
    $this->assertSession()->elementTextContains('xpath', '//td/a', $label);

    // Check the number of table row cells.
    $this->assertSession()->elementsCount('xpath', '//div[@class="layout-content"]//table/tbody/tr/td', 4);
    // Check the contents of each row cell. The first cell contains the label,
    // the second contains the machine name, and the third contains the
    // operations list.
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//table/tbody/tr/td/a', $label);

    // Edit the entity using the operations link.
    $blocks = $this->container
      ->get('entity_type.manager')
      ->getStorage('block_content')
      ->loadByProperties(['info' => $label]);
    $block = reset($blocks);
    if (!empty($block)) {
      $this->assertSession()->linkByHrefExists('block/' . $block->id());
      $this->clickLink('Edit');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->titleEquals("Edit custom block $label | Drupal");
      $edit = ['info[0][value]' => $new_label];
      $this->submitForm($edit, 'Save');
    }
    else {
      $this->fail('Did not find Albatross block in the database.');
    }

    // Confirm that once the user returns to the listing, the text of the label
    // (versus elsewhere on the page).
    $this->assertSession()->elementTextContains('xpath', '//td/a', $new_label);

    // Delete the added entity using the operations link.
    $this->assertSession()->linkByHrefExists('block/' . $block->id() . '/delete');
    $this->clickLink('Delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals("Are you sure you want to delete the custom block $new_label? | Drupal");
    $this->submitForm([], 'Delete');

    // Verify that the text of the label and machine name does not appear in
    // the list (though it may appear elsewhere on the page).
    $this->assertSession()->elementTextNotContains('xpath', '//td', $new_label);

    // Confirm that the empty text is displayed.
    $this->assertSession()->pageTextContains('There are no custom blocks available.');
    $this->assertSession()->linkExists('custom block');

    $block_content = BlockContent::create([
      'info' => 'Non-reusable block',
      'type' => 'basic',
      'reusable' => FALSE,
    ]);
    $block_content->save();

    $this->drupalGet('admin/structure/block/block-content');
    // Confirm that the empty text is displayed.
    $this->assertSession()->pageTextContains('There are no custom blocks available.');
    // Confirm the non-reusable block is not on the page.
    $this->assertSession()->pageTextNotContains('Non-reusable block');
  }

}
