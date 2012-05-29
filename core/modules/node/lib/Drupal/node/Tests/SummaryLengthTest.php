<?php

/**
 * @file
 * Definition of Drupal\node\Tests\SummaryLengthTest.
 */

namespace Drupal\node\Tests;

class SummaryLengthTest extends NodeTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Summary length',
      'description' => 'Test summary length.',
      'group' => 'Node',
    );
  }

  /**
   * Creates a node and then an anonymous and unpermissioned user attempt to edit the node.
   */
  function testSummaryLength() {
    // Create a node to view.
    $settings = array(
      'body' => array(LANGUAGE_NOT_SPECIFIED => array(array('value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam vitae arcu at leo cursus laoreet. Curabitur dui tortor, adipiscing malesuada tempor in, bibendum ac diam. Cras non tellus a libero pellentesque condimentum. What is a Drupalism? Suspendisse ac lacus libero. Ut non est vel nisl faucibus interdum nec sed leo. Pellentesque sem risus, vulputate eu semper eget, auctor in libero. Ut fermentum est vitae metus convallis scelerisque. Phasellus pellentesque rhoncus tellus, eu dignissim purus posuere id. Quisque eu fringilla ligula. Morbi ullamcorper, lorem et mattis egestas, tortor neque pretium velit, eget eleifend odio turpis eu purus. Donec vitae metus quis leo pretium tincidunt a pulvinar sem. Morbi adipiscing laoreet mauris vel placerat. Nullam elementum, nisl sit amet scelerisque malesuada, dolor nunc hendrerit quam, eu ultrices erat est in orci. Curabitur feugiat egestas nisl sed accumsan.'))),
      'promote' => 1,
    );
    $node = $this->drupalCreateNode($settings);
    $this->assertTrue(node_load($node->nid), t('Node created.'));

    // Create user with permission to view the node.
    $web_user = $this->drupalCreateUser(array('access content', 'administer content types'));
    $this->drupalLogin($web_user);

    // Attempt to access the front page.
    $this->drupalGet("node");
    // The node teaser when it has 600 characters in length
    $expected = 'What is a Drupalism?';
    $this->assertRaw($expected, t('Check that the summary is 600 characters in length'), 'Node');

    // Change the teaser length for "Basic page" content type.
    $instance = field_info_instance('node', 'body', $node->type);
    $instance['display']['teaser']['settings']['trim_length'] = 200;
    field_update_instance($instance);

    // Attempt to access the front page again and check if the summary is now only 200 characters in length.
    $this->drupalGet("node");
    $this->assertNoRaw($expected, t('Check that the summary is not longer than 200 characters'), 'Node');
  }
}
