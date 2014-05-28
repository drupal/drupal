<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTitleXSSTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests XSS functionality with a node entity.
 */
class NodeTitleXSSTest extends NodeTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Node title XSS filtering',
      'description' => 'Create a node with dangerous tags in its title and test that they are escaped.',
      'group' => 'Node',
    );
  }

  /**
   * Tests XSS functionality with a node entity.
   */
  function testNodeTitleXSS() {
    // Prepare a user to do the stuff.
    $web_user = $this->drupalCreateUser(array('create page content', 'edit any page content'));
    $this->drupalLogin($web_user);

    $xss = '<script>alert("xss")</script>';
    $title = $xss . $this->randomName();
    $edit = array();
    $edit['title[0][value]'] = $title;

    $this->drupalPostForm('node/add/page', $edit, t('Preview'));
    $this->assertNoRaw($xss, 'Harmful tags are escaped when previewing a node.');

    $settings = array('title' => $title);
    $node = $this->drupalCreateNode($settings);

    $this->drupalGet('node/' . $node->id());
    // assertTitle() decodes HTML-entities inside the <title> element.
    $this->assertTitle($title . ' | Drupal', 'Title is diplayed when viewing a node.');
    $this->assertNoRaw($xss, 'Harmful tags are escaped when viewing a node.');

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoRaw($xss, 'Harmful tags are escaped when editing a node.');
  }
}
