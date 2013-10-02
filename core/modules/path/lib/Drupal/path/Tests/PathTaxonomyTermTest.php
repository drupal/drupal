<?php

/**
 * @file
 * Definition of Drupal\path\Tests\PathTaxonomyTermTest.
 */

namespace Drupal\path\Tests;

/**
 * Tests URL aliases for taxonomy terms.
 */
class PathTaxonomyTermTest extends PathTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy term URL aliases',
      'description' => 'Tests URL aliases for taxonomy terms.',
      'group' => 'Path',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a Tags vocabulary for the Article node type.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => t('Tags'),
      'vid' => 'tags',
    ));
    $vocabulary->save();

    // Create and login user.
    $web_user = $this->drupalCreateUser(array('administer url aliases', 'administer taxonomy', 'access administration pages'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests alias functionality through the admin interfaces.
   */
  function testTermAlias() {
    // Create a term in the default 'Tags' vocabulary with URL alias.
    $vocabulary = entity_load('taxonomy_vocabulary', 'tags');
    $description = $this->randomName();
    $edit = array(
      'name' => $this->randomName(),
      'description[value]' => $description,
      'path[alias]' => $this->randomName(),
    );
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add', $edit, t('Save'));
    $tid = db_query("SELECT tid FROM {taxonomy_term_data} WHERE name = :name", array(':name' => $edit['name']))->fetchField();

    // Confirm that the alias works.
    $this->drupalGet($edit['path[alias]']);
    $this->assertText($description, 'Term can be accessed on URL alias.');

    // Confirm the 'canonical' and 'shortlink' URLs.
    $elements = $this->xpath("//link[contains(@rel, 'canonical') and contains(@href, '" . $edit['path[alias]'] . "')]");
    $this->assertTrue(!empty($elements), 'Term page contains canonical link URL.');
    $elements = $this->xpath("//link[contains(@rel, 'shortlink') and contains(@href, 'taxonomy/term/" . $tid . "')]");
    $this->assertTrue(!empty($elements), 'Term page contains shortlink URL.');

    // Change the term's URL alias.
    $edit2 = array();
    $edit2['path[alias]'] = $this->randomName();
    $this->drupalPostForm('taxonomy/term/' . $tid . '/edit', $edit2, t('Save'));

    // Confirm that the changed alias works.
    $this->drupalGet($edit2['path[alias]']);
    $this->assertText($description, 'Term can be accessed on changed URL alias.');

    // Confirm that the old alias no longer works.
    $this->drupalGet($edit['path[alias]']);
    $this->assertNoText($description, 'Old URL alias has been removed after altering.');
    $this->assertResponse(404, 'Old URL alias returns 404.');

    // Remove the term's URL alias.
    $edit3 = array();
    $edit3['path[alias]'] = '';
    $this->drupalPostForm('taxonomy/term/' . $tid . '/edit', $edit3, t('Save'));

    // Confirm that the alias no longer works.
    $this->drupalGet($edit2['path[alias]']);
    $this->assertNoText($description, 'Old URL alias has been removed after altering.');
    $this->assertResponse(404, 'Old URL alias returns 404.');
  }
}
