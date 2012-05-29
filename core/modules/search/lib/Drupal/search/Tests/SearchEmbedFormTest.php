<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchEmbedFormTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests that we can embed a form in search results and submit it.
 */
class SearchEmbedFormTest extends SearchTestBase {
  /**
   * Node used for testing.
   */
  public $node;

  /**
   * Count of how many times the form has been submitted.
   */
  public $submit_count = 0;

  public static function getInfo() {
    return array(
      'name' => 'Embedded forms',
      'description' => 'Verifies that a form embedded in search results works',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp(array('search_embedded_form'));

    // Create a user and a node, and update the search index.
    $test_user = $this->drupalCreateUser(array('access content', 'search content', 'administer nodes'));
    $this->drupalLogin($test_user);

    $this->node = $this->drupalCreateNode();

    node_update_index();
    search_update_totals();

    // Set up a dummy initial count of times the form has been submitted.
    $this->submit_count = 12;
    variable_set('search_embedded_form_submitted', $this->submit_count);
    $this->refreshVariables();
  }

  /**
   * Tests that the embedded form appears and can be submitted.
   */
  function testEmbeddedForm() {
    // First verify we can submit the form from the module's page.
    $this->drupalPost('search_embedded_form',
      array('name' => 'John'),
      t('Send away'));
    $this->assertText(t('Test form was submitted'), 'Form message appears');
    $count = variable_get('search_embedded_form_submitted', 0);
    $this->assertEqual($this->submit_count + 1, $count, 'Form submission count is correct');
    $this->submit_count = $count;

    // Now verify that we can see and submit the form from the search results.
    $this->drupalGet('search/node/' . $this->node->title);
    $this->assertText(t('Your name'), 'Form is visible');
    $this->drupalPost('search/node/' . $this->node->title,
      array('name' => 'John'),
      t('Send away'));
    $this->assertText(t('Test form was submitted'), 'Form message appears');
    $count = variable_get('search_embedded_form_submitted', 0);
    $this->assertEqual($this->submit_count + 1, $count, 'Form submission count is correct');
    $this->submit_count = $count;

    // Now verify that if we submit the search form, it doesn't count as
    // our form being submitted.
    $this->drupalPost('search',
      array('keys' => 'foo'),
      t('Search'));
    $this->assertNoText(t('Test form was submitted'), 'Form message does not appear');
    $count = variable_get('search_embedded_form_submitted', 0);
    $this->assertEqual($this->submit_count, $count, 'Form submission count is correct');
    $this->submit_count = $count;
  }
}
