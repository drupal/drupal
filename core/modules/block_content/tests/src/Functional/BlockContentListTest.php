<?php

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;

/**
 * Tests the listing of custom blocks.
 *
 * Tests the fallback block content list when Views is disabled.
 *
 * @group block_content
 * @see \Drupal\block\BlockContentListBuilder
 * @see \Drupal\block_content\Tests\BlockContentListViewsTest
 */
class BlockContentListTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_content', 'config_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests the custom block listing page.
   */
  public function testListing() {
    $this->drupalLogin($this->drupalCreateUser(['administer blocks', 'translate configuration']));
    $this->drupalGet('admin/structure/block/block-content');

    // Test for the page title.
    $this->assertSession()->titleEquals('Custom block library | Drupal');

    // Test for the table.
    $element = $this->xpath('//div[@class="layout-content"]//table');
    $this->assertNotEmpty($element, 'Configuration entity list table found.');

    // Test the table header.
    $elements = $this->xpath('//div[@class="layout-content"]//table/thead/tr/th');
    $this->assertCount(2, $elements, 'Correct number of table header cells found.');

    // Test the contents of each th cell.
    $expected_items = [t('Block description'), t('Operations')];
    foreach ($elements as $key => $element) {
      $this->assertEqual($element->getText(), $expected_items[$key]);
    }

    $label = 'Antelope';
    $new_label = 'Albatross';
    // Add a new entity using the operations link.
    $link_text = t('Add custom block');
    $this->assertSession()->linkExists($link_text);
    $this->clickLink($link_text);
    $this->assertSession()->statusCodeEquals(200);
    $edit = [];
    $edit['info[0][value]'] = $label;
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Confirm that once the user returns to the listing, the text of the label
    // (versus elsewhere on the page).
    $this->assertFieldByXpath('//td', $label, 'Label found for added block.');

    // Check the number of table row cells.
    $elements = $this->xpath('//div[@class="layout-content"]//table/tbody/tr[@class="odd"]/td');
    $this->assertCount(2, $elements, 'Correct number of table row cells found.');
    // Check the contents of each row cell. The first cell contains the label,
    // the second contains the machine name, and the third contains the
    // operations list.
    $this->assertIdentical($elements[0]->getText(), $label);

    // Edit the entity using the operations link.
    $blocks = $this->container
      ->get('entity_type.manager')
      ->getStorage('block_content')
      ->loadByProperties(['info' => $label]);
    $block = reset($blocks);
    if (!empty($block)) {
      $this->assertLinkByHref('block/' . $block->id());
      $this->clickLink(t('Edit'));
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->titleEquals("Edit custom block $label | Drupal");
      $edit = ['info[0][value]' => $new_label];
      $this->drupalPostForm(NULL, $edit, t('Save'));
    }
    else {
      $this->fail('Did not find Albatross block in the database.');
    }

    // Confirm that once the user returns to the listing, the text of the label
    // (versus elsewhere on the page).
    $this->assertFieldByXpath('//td', $new_label, 'Label found for updated custom block.');

    // Delete the added entity using the operations link.
    $this->assertLinkByHref('block/' . $block->id() . '/delete');
    $delete_text = t('Delete');
    $this->clickLink($delete_text);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals("Are you sure you want to delete the custom block $new_label? | Drupal");
    $this->drupalPostForm(NULL, [], $delete_text);

    // Verify that the text of the label and machine name does not appear in
    // the list (though it may appear elsewhere on the page).
    $this->assertNoFieldByXpath('//td', $new_label, 'No label found for deleted custom block.');

    // Confirm that the empty text is displayed.
    $this->assertText(t('There are no custom blocks yet.'));

    $block_content = BlockContent::create([
      'info' => 'Non-reusable block',
      'type' => 'basic',
      'reusable' => FALSE,
    ]);
    $block_content->save();

    $this->drupalGet('admin/structure/block/block-content');
    // Confirm that the empty text is displayed.
    $this->assertSession()->pageTextContains('There are no custom blocks yet.');
    // Confirm the non-reusable block is not on the page.
    $this->assertSession()->pageTextNotContains('Non-reusable block');
  }

}
