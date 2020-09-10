<?php

namespace Drupal\Tests\taxonomy\Functional;

/**
 * Verifies that various taxonomy pages use the expected theme.
 *
 * @group taxonomy
 */
class ThemeTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Make sure we are using distinct default and administrative themes for
    // the duration of these tests.
    \Drupal::service('theme_installer')->install(['bartik', 'seven']);
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->set('admin', 'seven')
      ->save();

    // Create and log in as a user who has permission to add and edit taxonomy
    // terms and view the administrative theme.
    $admin_user = $this->drupalCreateUser([
      'administer taxonomy',
      'view the administration theme',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test the theme used when adding, viewing and editing taxonomy terms.
   */
  public function testTaxonomyTermThemes() {
    // Adding a term to a vocabulary is considered an administrative action and
    // should use the administrative theme.
    $vocabulary = $this->createVocabulary();
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    // Check that the administrative theme's CSS appears on the page for adding
    // a taxonomy term.
    $this->assertRaw('seven/css/base/elements.css');

    // Viewing a taxonomy term should use the default theme.
    $term = $this->createTerm($vocabulary);
    $this->drupalGet('taxonomy/term/' . $term->id());
    // Check that the default theme's CSS appears on the page for viewing
    // a taxonomy term.
    $this->assertRaw('bartik/css/base/elements.css');

    // Editing a taxonomy term should use the same theme as adding one.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    // Check that the administrative theme's CSS appears on the page for editing
    // a taxonomy term.
    $this->assertRaw('seven/css/base/elements.css');
  }

}
