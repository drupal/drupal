<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentListTest.
 */

namespace Drupal\block_content\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the listing of custom blocks.
 *
 * @group block_content
 * @see \Drupal\block\BlockContentListBuilder
 */
class BlockContentListTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_content', 'config_translation');

  /**
   * Tests the custom block listing page.
   */
  public function testListing() {
    $this->drupalLogin($this->drupalCreateUser(array('administer blocks', 'translate configuration')));
    $this->drupalGet('admin/structure/block/block-content');

    // Test for the page title.
    $this->assertTitle(t('Custom block library') . ' | Drupal');

    // Test for the table.
    $element = $this->xpath('//div[@class="layout-content"]//table');
    $this->assertTrue($element, 'Configuration entity list table found.');

    // Test the table header.
    $elements = $this->xpath('//div[@class="layout-content"]//table/thead/tr/th');
    $this->assertEqual(count($elements), 2, 'Correct number of table header cells found.');

    // Test the contents of each th cell.
    $expected_items = array(t('Block description'), t('Operations'));
    foreach ($elements as $key => $element) {
      $this->assertIdentical((string) $element[0], $expected_items[$key]);
    }

    $label = 'Antelope';
    $new_label = 'Albatross';
    // Add a new entity using the operations link.
    $link_text = t('Add custom block');
    $this->assertLink($link_text);
    $this->clickLink($link_text);
    $this->assertResponse(200);
    $edit = array();
    $edit['info[0][value]'] = $label;
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Confirm that once the user returns to the listing, the text of the label
    // (versus elsewhere on the page).
    $this->assertFieldByXpath('//td', $label, 'Label found for added block.');

    // Check the number of table row cells.
    $elements = $this->xpath('//div[@class="layout-content"]//table/tbody/tr[@class="odd"]/td');
    $this->assertEqual(count($elements), 2, 'Correct number of table row cells found.');
    // Check the contents of each row cell. The first cell contains the label,
    // the second contains the machine name, and the third contains the
    // operations list.
    $this->assertIdentical((string) $elements[0], $label);

    // Edit the entity using the operations link.
    $blocks = $this->container
      ->get('entity.manager')
      ->getStorage('block_content')
      ->loadByProperties(array('info' => $label));
    $block = reset($blocks);
    if (!empty($block)) {
      $this->assertLinkByHref('block/' . $block->id());
      $this->clickLink(t('Edit'));
      $this->assertResponse(200);
      $this->assertTitle(strip_tags(t('Edit custom block %label', array('%label' => $label)) . ' | Drupal'));
      $edit = array('info[0][value]' => $new_label);
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
    $this->assertResponse(200);
    $this->assertTitle(strip_tags(t('Are you sure you want to delete %label?', array('%label' => $new_label)) . ' | Drupal'));
    $this->drupalPostForm(NULL, array(), $delete_text);

    // Verify that the text of the label and machine name does not appear in
    // the list (though it may appear elsewhere on the page).
    $this->assertNoFieldByXpath('//td', $new_label, 'No label found for deleted custom block.');

    // Confirm that the empty text is displayed.
    $this->assertText(t('There is no Custom Block yet.'));
  }

}
