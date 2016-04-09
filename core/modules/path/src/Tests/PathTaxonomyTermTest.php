<?php

namespace Drupal\path\Tests;

use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests URL aliases for taxonomy terms.
 *
 * @group path
 */
class PathTaxonomyTermTest extends PathTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  protected function setUp() {
    parent::setUp();

    // Create a Tags vocabulary for the Article node type.
    $vocabulary = Vocabulary::create([
      'name' => t('Tags'),
      'vid' => 'tags',
    ]);
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
    $vocabulary = Vocabulary::load('tags');
    $description = $this->randomMachineName();
    $edit = array(
      'name[0][value]' => $this->randomMachineName(),
      'description[0][value]' => $description,
      'path[0][alias]' => '/' . $this->randomMachineName(),
    );
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add', $edit, t('Save'));
    $tid = db_query("SELECT tid FROM {taxonomy_term_field_data} WHERE name = :name AND default_langcode = 1", array(':name' => $edit['name[0][value]']))->fetchField();

    // Confirm that the alias works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertText($description, 'Term can be accessed on URL alias.');

    // Confirm the 'canonical' and 'shortlink' URLs.
    $elements = $this->xpath("//link[contains(@rel, 'canonical') and contains(@href, '" . $edit['path[0][alias]'] . "')]");
    $this->assertTrue(!empty($elements), 'Term page contains canonical link URL.');
    $elements = $this->xpath("//link[contains(@rel, 'shortlink') and contains(@href, 'taxonomy/term/" . $tid . "')]");
    $this->assertTrue(!empty($elements), 'Term page contains shortlink URL.');

    // Change the term's URL alias.
    $edit2 = array();
    $edit2['path[0][alias]'] = '/' . $this->randomMachineName();
    $this->drupalPostForm('taxonomy/term/' . $tid . '/edit', $edit2, t('Save'));

    // Confirm that the changed alias works.
    $this->drupalGet(trim($edit2['path[0][alias]'], '/'));
    $this->assertText($description, 'Term can be accessed on changed URL alias.');

    // Confirm that the old alias no longer works.
    $this->drupalGet(trim($edit['path[0][alias]'], '/'));
    $this->assertNoText($description, 'Old URL alias has been removed after altering.');
    $this->assertResponse(404, 'Old URL alias returns 404.');

    // Remove the term's URL alias.
    $edit3 = array();
    $edit3['path[0][alias]'] = '';
    $this->drupalPostForm('taxonomy/term/' . $tid . '/edit', $edit3, t('Save'));

    // Confirm that the alias no longer works.
    $this->drupalGet(trim($edit2['path[0][alias]'], '/'));
    $this->assertNoText($description, 'Old URL alias has been removed after altering.');
    $this->assertResponse(404, 'Old URL alias returns 404.');
  }
}
