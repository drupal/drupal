<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests altering the inbound path and the outbound path.
 *
 * @group form
 * @group legacy
 */
class UrlAlterFunctionalTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['path', 'forum', 'forum_url_alter_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that URL altering works and that it occurs in the correct order.
   */
  public function testUrlAlter(): void {
    // Ensure that the path_alias table exists after Drupal installation.
    $this->assertTrue(Database::getConnection()->schema()->tableExists('path_alias'), 'The path_alias table exists after Drupal installation.');

    // Test that 'forum' is altered to 'community' correctly, both at the root
    // level and for a specific existing forum.
    $this->drupalGet('community');
    $this->assertSession()->pageTextContains('General discussion');
    $this->assertUrlOutboundAlter('/forum', '/community');
    $forum_vid = $this->config('forum.settings')->get('vocabulary');
    $term_name = $this->randomMachineName();
    $term = Term::create([
      'name' => $term_name,
      'vid' => $forum_vid,
    ]);
    $term->save();
    $this->drupalGet("community/" . $term->id());
    $this->assertSession()->pageTextContains($term_name);
    $this->assertUrlOutboundAlter("/forum/" . $term->id(), "/community/" . $term->id());
  }

  /**
   * Assert that an outbound path is altered to an expected value.
   *
   * @param string $original
   *   A string with the original path that is run through generateFrommPath().
   * @param string $final
   *   A string with the expected result after generateFrommPath().
   *
   * @internal
   */
  protected function assertUrlOutboundAlter(string $original, string $final): void {
    // Test outbound altering.
    $result = $this->container->get('path_processor_manager')->processOutbound($original);
    $this->assertSame($final, $result, "Altered outbound URL $original, expected $final, and got $result.");
  }

  /**
   * Assert that an inbound path is altered to an expected value.
   *
   * @param string $original
   *   The original path before it has been altered by inbound URL processing.
   * @param string $final
   *   A string with the expected result.
   *
   * @internal
   */
  protected function assertUrlInboundAlter(string $original, string $final): void {
    // Test inbound altering.
    $result = $this->container->get('path_alias.manager')->getPathByAlias($original);
    $this->assertSame($final, $result, "Altered inbound URL $original, expected $final, and got $result.");
  }

}
