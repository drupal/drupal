<?php
/**
 * @file
 * Implements tests for devel_generate submodule.
 */

namespace Drupal\devel_generate\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * class DevelGenerateTest
 */
class DevelGenerateTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('devel', 'devel_generate', 'taxonomy', 'menu', 'comment');

  /*
   * The getInfo() method provides information about the test.
   * In order for the test to be run, the getInfo() method needs
   * to be implemented.
   */
  public static function getInfo() {
    return array(
      'name' => t('Devel Generate'),
      'description' => t('Tests the logic to generate data.'),
      'group' => t('Devel'),
    );
  }

  /**
   * Prepares the testing environment
   */
  function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic Page'));
    }
  }

  /**
   * Tests generate commands
   */
  public function testGenerate() {
    $user = $this->drupalCreateUser(array(
      'administer taxonomy',
      'administer menu',
      'administer nodes',
    ));
    $this->drupalLogin($user);

    // Generate taxonomy vocabularies.
    $edit = array(
      'num_vocabs' => 5,
      'title_length' => 12,
      'kill_taxonomy' => 1,
    );
    $this->drupalPost('admin/config/development/generate/vocabs',
                      $edit, t('Generate'));
    $this->assertText(t('Deleted existing vocabularies.'));
    $this->assertText(t('Created the following new vocabularies:'));

    // Generate taxonomy terms.
    $form = devel_generate_term_form();
    $vids = array_keys($form['vids']['#options']);
    $edit = array(
      'vids[]' => $vids,
      'num_terms' => 5,
      'title_length' => 12,
      'kill_taxonomy' => 1,
    );
    $this->drupalPost('admin/config/development/generate/taxonomy',
                      $edit, t('Generate'));
    $this->assertText(t('Deleted existing terms.'));
    $this->assertText(t('Created the following new terms: '));

    // Generate menus.
    $edit = array(
      'existing_menus[__new-menu__]' => 1,
      'num_menus' => 2,
      'num_links' => 50,
      'title_length' => 12,
      'link_types[node]' => 1,
      'link_types[front]' => 1,
      'link_types[external]' => 1,
      'max_depth' => 4,
      'max_width' => 6,
      'kill' => 1,
    );
    $this->drupalPost('admin/config/development/generate/menu',
                      $edit, t('Generate'));
    $this->assertText(t('Deleted existing menus and links.'));
    $this->assertText(t('Created the following new menus:'));
    $this->assertText(t('Created 50 new menu links.'));

    // Generate content.
    // First we create a node in order to test the Delete content checkbox.
    $this->drupalCreateNode(array());

    // Now submit the generate content form.
    $edit = array(
      'node_types[page]' => 1,
      'kill_content' => 1,
      'num_nodes' => 2,
      'time_range' => 604800,
      'max_comments' => 3,
      'title_length' => 4,
    );
    $this->drupalPost('admin/config/development/generate/content', $edit, t('Generate'));
    $this->assertText(t('Deleted 1 nodes.'));
    $this->assertText(t('Finished creating 2 nodes'));
  }
}
