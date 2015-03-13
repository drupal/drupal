<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityListBuilderTest.
*/

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;

/**
 * Tests entity list builder functionality.
 *
 * @group Entity
 */
class EntityListBuilderTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('entity_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login user.
    $this->web_user = $this->drupalCreateUser(array(
      'administer entity_test content',
    ));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Test paging.
   */
  public function testPager() {
    // Create 51 test entities.
    for ($i = 1; $i < 52; $i++) {
      entity_create('entity_test', array('name' => 'Test entity ' . $i))->save();
    }

    // Load the listing page.
    $this->drupalGet('entity_test/list');

    // Item 51 should not be present.
    $this->assertRaw('Test entity 50', 'Item 50 is shown.');
    $this->assertNoRaw('Test entity 51', 'Item 51 is on the next page.');

    // Browse to the next page.
    $this->clickLink(t('Page 2'));
    $this->assertNoRaw('Test entity 50', 'Test entity 50 is on the previous page.');
    $this->assertRaw('Test entity 51', 'Test entity 51 is shown.');
  }

}
