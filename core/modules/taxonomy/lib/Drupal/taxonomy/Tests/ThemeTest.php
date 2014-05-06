<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\ThemeTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests for verifying that taxonomy pages use the correct theme.
 */
class ThemeTest extends TaxonomyTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy theme switching',
      'description' => 'Verifies that various taxonomy pages use the expected theme.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();

    // Make sure we are using distinct default and administrative themes for
    // the duration of these tests.
    theme_enable(array('bartik', 'seven'));
    \Drupal::config('system.theme')
      ->set('default', 'bartik')
      ->set('admin', 'seven')
      ->save();

    // Create and log in as a user who has permission to add and edit taxonomy
    // terms and view the administrative theme.
    $admin_user = $this->drupalCreateUser(array('administer taxonomy', 'view the administration theme'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Test the theme used when adding, viewing and editing taxonomy terms.
   */
  function testTaxonomyTermThemes() {
    // Adding a term to a vocabulary is considered an administrative action and
    // should use the administrative theme.
    $vocabulary = $this->createVocabulary();
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertRaw('seven/style.css', t("The administrative theme's CSS appears on the page for adding a taxonomy term."));

    // Viewing a taxonomy term should use the default theme.
    $term = $this->createTerm($vocabulary);
    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertRaw('bartik/css/style.css', t("The default theme's CSS appears on the page for viewing a taxonomy term."));

    // Editing a taxonomy term should use the same theme as adding one.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertRaw('seven/style.css', t("The administrative theme's CSS appears on the page for editing a taxonomy term."));
  }
}
