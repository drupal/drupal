<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Functional;

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
  protected static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a Tags vocabulary for the Article node type.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();

    // Create and log in user.
    $web_user = $this->drupalCreateUser([
      'administer url aliases',
      'administer taxonomy',
      'access administration pages',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests alias functionality through the admin interfaces.
   */
  public function testTermAlias(): void {
    // Create a term in the default 'Tags' vocabulary with URL alias.
    $vocabulary = Vocabulary::load('tags');
    $description = $this->randomMachineName();
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'description[0][value]' => $description,
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->submitForm($edit, 'Save');
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->condition('name', $edit['name[0][value]'])
      ->condition('default_langcode', 1)
      ->execute();
    $tid = reset($tids);

    // Confirm that the alias works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertSession()->pageTextContains($description);

    // Confirm the 'canonical' and 'shortlink' URLs.
    $this->assertSession()->elementExists('xpath', "//link[contains(@rel, 'canonical') and contains(@href, '" . $edit['path[0][alias]'] . "')]");
    $this->assertSession()->elementExists('xpath', "//link[contains(@rel, 'shortlink') and contains(@href, 'taxonomy/term/" . $tid . "')]");

    // Change the term's URL alias.
    $edit2 = [];
    $edit2['path[0][alias]'] = '/' . $this->randomMachineName();
    $this->drupalGet('taxonomy/term/' . $tid . '/edit');
    $this->submitForm($edit2, 'Save');

    // Confirm that the changed alias works.
    $this->drupalGet(trim($edit2['path[0][alias]'], '/'));
    $this->assertSession()->pageTextContains($description);

    // Confirm that the old alias no longer works.
    $this->drupalGet(trim($edit['path[0][alias]'], '/'));
    $this->assertSession()->pageTextNotContains($description);
    $this->assertSession()->statusCodeEquals(404);

    // Remove the term's URL alias.
    $edit3 = [];
    $edit3['path[0][alias]'] = '';
    $this->drupalGet('taxonomy/term/' . $tid . '/edit');
    $this->submitForm($edit3, 'Save');

    // Confirm that the alias no longer works.
    $this->drupalGet(trim($edit2['path[0][alias]'], '/'));
    $this->assertSession()->pageTextNotContains($description);
    $this->assertSession()->statusCodeEquals(404);
  }

}
