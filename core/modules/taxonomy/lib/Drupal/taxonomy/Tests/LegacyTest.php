<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\LegacyTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Datetime\DrupalDateTime;

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
    $date = new DrupalDateTime('1969-01-01 00:00:00');
    $edit = array();
    $edit['title[0][value]'] = $this->randomName();
    $edit['created[date]'] = $date->format('Y-m-d');
    $edit['created[time]'] = $date->format('H:i:s');
    $edit['body[0][value]'] = $this->randomName();
    $edit['field_tags'] = $this->randomName();
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    // Checks that the node has been saved.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertEqual($node->getCreatedTime(), $date->getTimestamp(), 'Legacy node was saved with the right date.');
  }
}
