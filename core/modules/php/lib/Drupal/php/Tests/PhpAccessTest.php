<?php

/**
 * @file
 * Definition of Drupal\php\Tests\PhpAccessTest.
 */

namespace Drupal\php\Tests;

/**
 * Tests to make sure access to the PHP filter is properly restricted.
 */
class PhpAccessTest extends PhpTestBase {
  public static function getInfo() {
    return array(
      'name' => 'PHP filter access check',
      'description' => 'Make sure that users who don\'t have access to the PHP filter can\'t see it.',
      'group' => 'PHP',
    );
  }

  /**
   * Makes sure that the user can't use the PHP filter when not given access.
   */
  function testNoPrivileges() {
    // Create node with PHP filter enabled.
    $web_user = $this->drupalCreateUser(array('access content', 'create page content', 'edit own page content'));
    $this->drupalLogin($web_user);
    $node = $this->createNodeWithCode();

    // Make sure that the PHP code shows up as text.
    $this->drupalGet('node/' . $node->nid);
    $this->assertText('print', t('PHP code was not evaluated.'));

    // Make sure that user doesn't have access to filter.
    $this->drupalGet('node/' . $node->nid . '/edit');
    $this->assertNoRaw('<option value="' . $this->php_code_format->format . '">', t('PHP code format not available.'));
  }
}
