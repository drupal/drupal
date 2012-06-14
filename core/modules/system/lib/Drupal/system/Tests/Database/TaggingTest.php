<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\TaggingTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Select tagging tests.
 *
 * Tags are a way to flag queries for alter hooks so they know
 * what type of query it is, such as "node_access".
 */
class TaggingTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Query tagging tests',
      'description' => 'Test the tagging capabilities of the Select builder.',
      'group' => 'Database',
    );
  }

  /**
   * Confirm that a query has a "tag" added to it.
   */
  function testHasTag() {
    $query = db_select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');

    $query->addTag('test');

    $this->assertTrue($query->hasTag('test'), t('hasTag() returned true.'));
    $this->assertFalse($query->hasTag('other'), t('hasTag() returned false.'));
  }

  /**
   * Test query tagging "has all of these tags" functionality.
   */
  function testHasAllTags() {
    $query = db_select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');

    $query->addTag('test');
    $query->addTag('other');

    $this->assertTrue($query->hasAllTags('test', 'other'), t('hasAllTags() returned true.'));
    $this->assertFalse($query->hasAllTags('test', 'stuff'), t('hasAllTags() returned false.'));
  }

  /**
   * Test query tagging "has at least one of these tags" functionality.
   */
  function testHasAnyTag() {
    $query = db_select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');

    $query->addTag('test');

    $this->assertTrue($query->hasAnyTag('test', 'other'), t('hasAnyTag() returned true.'));
    $this->assertFalse($query->hasAnyTag('other', 'stuff'), t('hasAnyTag() returned false.'));
  }

  /**
   * Test that we can attach meta data to a query object.
   *
   * This is how we pass additional context to alter hooks.
   */
  function testMetaData() {
    $query = db_select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');

    $data = array(
      'a' => 'A',
      'b' => 'B',
    );

    $query->addMetaData('test', $data);

    $return = $query->getMetaData('test');
    $this->assertEqual($data, $return, t('Corect metadata returned.'));

    $return = $query->getMetaData('nothere');
    $this->assertNull($return, t('Non-existent key returned NULL.'));
  }
}
