<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests the taxonomy term access permissions.
 *
 * @group taxonomy
 */
class TermAccessTest extends TaxonomyTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests access control functionality for taxonomy terms.
   */
  public function testTermAccess() {
    $assert_session = $this->assertSession();

    $vocabulary = $this->createVocabulary();

    // Create two terms.
    $published_term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'Published term',
      'status' => 1,
    ]);
    $published_term->save();
    $unpublished_term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'Unpublished term',
      'status' => 0,
    ]);
    $unpublished_term->save();

    // Start off logged in as admin.
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy']));

    // Test the 'administer taxonomy' permission.
    $this->drupalGet('taxonomy/term/' . $published_term->id());
    $assert_session->statusCodeEquals(200);
    $this->assertTermAccess($published_term, 'view', TRUE);
    $this->drupalGet('taxonomy/term/' . $unpublished_term->id());
    $assert_session->statusCodeEquals(200);
    $this->assertTermAccess($unpublished_term, 'view', TRUE);

    $this->drupalGet('taxonomy/term/' . $published_term->id() . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->assertTermAccess($published_term, 'update', TRUE);
    $this->drupalGet('taxonomy/term/' . $unpublished_term->id() . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->assertTermAccess($unpublished_term, 'update', TRUE);

    $this->drupalGet('taxonomy/term/' . $published_term->id() . '/delete');
    $assert_session->statusCodeEquals(200);
    $this->assertTermAccess($published_term, 'delete', TRUE);
    $this->drupalGet('taxonomy/term/' . $unpublished_term->id() . '/delete');
    $assert_session->statusCodeEquals(200);
    $this->assertTermAccess($unpublished_term, 'delete', TRUE);

    // Test the 'access content' permission.
    $this->drupalLogin($this->drupalCreateUser(['access content']));

    $this->drupalGet('taxonomy/term/' . $published_term->id());
    $assert_session->statusCodeEquals(200);
    $this->assertTermAccess($published_term, 'view', TRUE);

    $this->drupalGet('taxonomy/term/' . $unpublished_term->id());
    $assert_session->statusCodeEquals(403);
    $this->assertTermAccess($unpublished_term, 'view', FALSE, "The 'access content' permission is required and the taxonomy term must be published.");

    $this->drupalGet('taxonomy/term/' . $published_term->id() . '/edit');
    $assert_session->statusCodeEquals(403);
    $this->assertTermAccess($published_term, 'update', FALSE, "The following permissions are required: 'edit terms in {$vocabulary->id()}' OR 'administer taxonomy'.");
    $this->drupalGet('taxonomy/term/' . $unpublished_term->id() . '/edit');
    $assert_session->statusCodeEquals(403);
    $this->assertTermAccess($unpublished_term, 'update', FALSE, "The following permissions are required: 'edit terms in {$vocabulary->id()}' OR 'administer taxonomy'.");

    $this->drupalGet('taxonomy/term/' . $published_term->id() . '/delete');
    $assert_session->statusCodeEquals(403);
    $this->assertTermAccess($published_term, 'delete', FALSE, "The following permissions are required: 'delete terms in {$vocabulary->id()}' OR 'administer taxonomy'.");
    $this->drupalGet('taxonomy/term/' . $unpublished_term->id() . '/delete');
    $assert_session->statusCodeEquals(403);
    $this->assertTermAccess($unpublished_term, 'delete', FALSE, "The following permissions are required: 'delete terms in {$vocabulary->id()}' OR 'administer taxonomy'.");

    // Install the Views module and repeat the checks for the 'view' permission.
    \Drupal::service('module_installer')->install(['views'], TRUE);
    $this->rebuildContainer();

    $this->drupalGet('taxonomy/term/' . $published_term->id());
    $assert_session->statusCodeEquals(200);

    // @todo Change this assertion to expect a 403 status code when
    //   https://www.drupal.org/project/drupal/issues/2983070 is fixed.
    $this->drupalGet('taxonomy/term/' . $unpublished_term->id());
    $assert_session->statusCodeEquals(404);
  }

  /**
   * Checks access on taxonomy term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A taxonomy term entity.
   * @param $access_operation
   *   The entity operation, e.g. 'view', 'edit', 'delete', etc.
   * @param bool $access_allowed
   *   Whether the current use has access to the given operation or not.
   * @param string $access_reason
   *   (optional) The reason of the access result.
   */
  protected function assertTermAccess(TermInterface $term, $access_operation, $access_allowed, $access_reason = '') {
    $access_result = $term->access($access_operation, NULL, TRUE);
    $this->assertSame($access_allowed, $access_result->isAllowed());

    if ($access_reason) {
      $this->assertSame($access_reason, $access_result->getReason());
    }
  }

}
