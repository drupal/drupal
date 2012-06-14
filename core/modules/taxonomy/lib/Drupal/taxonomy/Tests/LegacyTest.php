<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\LegacyTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Test for legacy node bug.
 */
class LegacyTest extends TaxonomyTestBase {

  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Test for legacy node bug.',
      'description' => 'Posts an article with a taxonomy term and a date prior to 1970.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test taxonomy functionality with nodes prior to 1970.
   */
  function testTaxonomyLegacyNode() {
    // Posts an article with a taxonomy term and a date prior to 1970.
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $edit = array();
    $edit['title'] = $this->randomName();
    $edit['date'] = '1969-01-01 00:00:00 -0500';
    $edit["body[$langcode][0][value]"] = $this->randomName();
    $edit["field_tags[$langcode]"] = $this->randomName();
    $this->drupalPost('node/add/article', $edit, t('Save'));
    // Checks that the node has been saved.
    $node = $this->drupalGetNodeByTitle($edit['title']);
    $this->assertEqual($node->created, strtotime($edit['date']), 'Legacy node was saved with the right date.');
  }
}
