<?php

/**
 * @file
 * Definition of Drupal\php\Tests\PhpFilterTest.
 */

namespace Drupal\php\Tests;

/**
 * Tests to make sure the PHP filter actually evaluates PHP code when used.
 */
class PhpFilterTest extends PhpTestBase {
  public static function getInfo() {
    return array(
      'name' => 'PHP filter functionality',
      'description' => 'Make sure that PHP filter properly evaluates PHP code when enabled.',
      'group' => 'PHP',
    );
  }

  /**
   * Makes sure that the PHP filter evaluates PHP code when used.
   */
  function testPhpFilter() {
    // Log in as a user with permission to use the PHP code text format.
    $php_code_permission = filter_permission_name(filter_format_load('php_code'));
    $web_user = $this->drupalCreateUser(array('access content', 'create page content', 'edit own page content', $php_code_permission));
    $this->drupalLogin($web_user);

    // Create a node with PHP code in it.
    $node = $this->createNodeWithCode();

    // Make sure that the PHP code shows up as text.
    $this->drupalGet('node/' . $node->nid);
    $this->assertText('php print');

    // Change filter to PHP filter and see that PHP code is evaluated.
    $edit = array();
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $edit["body[$langcode][0][format]"] = $this->php_code_format->format;
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));
    $this->assertRaw(t('Basic page %title has been updated.', array('%title' => $node->title)), t('PHP code filter turned on.'));

    // Make sure that the PHP code shows up as text.
    $this->assertNoText('print "SimpleTest PHP was executed!"', t("PHP code isn't displayed."));
    $this->assertText('SimpleTest PHP was executed!', t('PHP code has been evaluated.'));
  }
}
