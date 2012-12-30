<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Wizard\BasicTest.
 */

namespace Drupal\views\Tests\Wizard;

/**
 * Tests creating views with the wizard and viewing them on the listing page.
 */
class BasicTest extends WizardTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Basic functionality',
      'description' => 'Test creating basic views with the wizard and viewing them on the listing page.',
      'group' => 'Views Wizard',
    );
  }

  function testViewsWizardAndListing() {
    $this->drupalCreateContentType(array('type' => 'article'));
    $this->drupalCreateContentType(array('type' => 'page'));

    // Check if we can access the main views admin page.
    $this->drupalGet('admin/structure/views');
    $this->assertText(t('Add new view'));

    // Create a simple and not at all useful view.
    $view1 = array();
    $view1['human_name'] = $this->randomName(16);
    $view1['name'] = strtolower($this->randomName(16));
    $view1['description'] = $this->randomName(16);
    $view1['page[create]'] = FALSE;
    $this->drupalPost('admin/structure/views/add', $view1, t('Save and edit'));
    $this->assertResponse(200);
    $this->drupalGet('admin/structure/views');
    $this->assertText($view1['human_name']);
    $this->assertText($view1['description']);
    // @todo For now, clone is being left to config.module to solve.
    foreach (array('delete', 'edit') as $operation) {
      $this->assertLinkByHref(url('admin/structure/views/view/' . $view1['name'] . '/' . $operation));
    }

    // This view should not have a block.
    $this->drupalGet('admin/structure/block');
    $this->assertNoText('View: ' . $view1['human_name']);

    // Create two nodes.
    $node1 = $this->drupalCreateNode(array('type' => 'page'));
    $node2 = $this->drupalCreateNode(array('type' => 'article'));

    // Now create a page with simple node listing and an attached feed.
    $view2 = array();
    $view2['human_name'] = $this->randomName(16);
    $view2['name'] = strtolower($this->randomName(16));
    $view2['description'] = $this->randomName(16);
    $view2['page[create]'] = 1;
    $view2['page[title]'] = $this->randomName(16);
    $view2['page[path]'] = $this->randomName(16);
    $view2['page[feed]'] = 1;
    $view2['page[feed_properties][path]'] = $this->randomName(16);
    $this->drupalPost('admin/structure/views/add', $view2, t('Save and edit'));
    $this->drupalGet($view2['page[path]']);
    $this->assertResponse(200);

    // Since the view has a page, we expect to be automatically redirected to
    // it.
    $this->assertUrl($view2['page[path]']);
    $this->assertText($view2['page[title]']);
    $this->assertText($node1->label());
    $this->assertText($node2->label());

    // Check if we have the feed.
    $this->assertLinkByHref(url($view2['page[feed_properties][path]']));
    $this->drupalGet($view2['page[feed_properties][path]']);
    $this->assertRaw('<rss version="2.0"');
    // The feed should have the same title and nodes as the page.
    $this->assertText($view2['page[title]']);
    $this->assertRaw(url('node/' . $node1->nid, array('absolute' => TRUE)));
    $this->assertText($node1->label());
    $this->assertRaw(url('node/' . $node2->nid, array('absolute' => TRUE)));
    $this->assertText($node2->label());

    // Go back to the views page and check if this view is there.
    $this->drupalGet('admin/structure/views');
    $this->assertText($view2['human_name']);
    $this->assertText($view2['description']);
    $this->assertLinkByHref(url($view2['page[path]']));

    // This view should not have a block.
    $this->drupalGet('admin/structure/block');
    $this->assertNoText('View: ' . $view2['human_name']);

    // Create a view with a page and a block, and filter the listing.
    $view3 = array();
    $view3['human_name'] = $this->randomName(16);
    $view3['name'] = strtolower($this->randomName(16));
    $view3['description'] = $this->randomName(16);
    $view3['show[wizard_key]'] = 'node';
    $view3['show[type]'] = 'page';
    $view3['page[create]'] = 1;
    $view3['page[title]'] = $this->randomName(16);
    $view3['page[path]'] = $this->randomName(16);
    $view3['block[create]'] = 1;
    $view3['block[title]'] = $this->randomName(16);
    $this->drupalPost('admin/structure/views/add', $view3, t('Save and edit'));
    $this->drupalGet($view3['page[path]']);
    $this->assertResponse(200);

    // Make sure the view only displays the node we expect.
    $this->assertUrl($view3['page[path]']);
    $this->assertText($view3['page[title]']);
    $this->assertText($node1->label());
    $this->assertNoText($node2->label());

    // Go back to the views page and check if this view is there.
    $this->drupalGet('admin/structure/views');
    $this->assertText($view3['human_name']);
    $this->assertText($view3['description']);
    $this->assertLinkByHref(url($view3['page[path]']));

    // Put the block into the first sidebar region.
    $this->drupalGet('admin/structure/block');
    $this->assertText('View: ' . $view3['human_name']);
    $edit = array();
    $edit["blocks[views_{$view3['name']}-block_1][region]"] = 'sidebar_first';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Visit a random page (not the one that displays the view itself) and look
    // for the expected node title in the block.
    $this->drupalGet('user');
    $this->assertText($node1->label());
    $this->assertNoText($node2->label());

    // Make sure the listing page doesn't show disabled default views.
    $this->assertNoText('tracker', t('Default tracker view does not show on the listing page.'));
  }

}
