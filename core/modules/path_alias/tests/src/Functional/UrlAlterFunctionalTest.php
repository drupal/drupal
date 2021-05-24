<?php

namespace Drupal\Tests\path_alias\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests altering the inbound path and the outbound path.
 *
 * @group path_alias
 */
class UrlAlterFunctionalTest extends BrowserTestBase {

  use PathAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['path', 'forum', 'url_alter_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that URL altering works and that it occurs in the correct order.
   */
  public function testUrlAlter() {
    // Ensure that the path_alias table exists after Drupal installation.
    $this->assertTrue(Database::getConnection()->schema()->tableExists('path_alias'), 'The path_alias table exists after Drupal installation.');

    // User names can have quotes and plus signs so we should ensure that URL
    // altering works with this.
    $account = $this->drupalCreateUser(['administer url aliases'], "a'foo+bar");
    $this->drupalLogin($account);

    $uid = $account->id();
    $name = $account->getAccountName();

    // Test a single altered path.
    $this->drupalGet("user/$name");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertUrlOutboundAlter("/user/$uid", "/user/$name");

    // Test that a path always uses its alias.
    $this->createPathAlias("/user/$uid/test1", '/alias/test1');
    $this->rebuildContainer();
    $this->assertUrlInboundAlter('/alias/test1', "/user/$uid/test1");
    $this->assertUrlOutboundAlter("/user/$uid/test1", '/alias/test1');

    // Test adding an alias via the UI.
    $edit = ['path[0][value]' => "/user/$uid/edit", 'alias[0][value]' => '/alias/test2'];
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The alias has been saved.');
    $this->drupalGet('alias/test2');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertUrlOutboundAlter("/user/$uid/edit", '/alias/test2');

    // Test a non-existent user is not altered.
    $uid++;
    $this->assertUrlOutboundAlter("/user/$uid", "/user/$uid");

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

    // Test outbound query string altering.
    $url = Url::fromRoute('user.login');
    $this->assertSame(\Drupal::request()->getBaseUrl() . '/user/login?foo=bar', $url->toString());
  }

  /**
   * Assert that an outbound path is altered to an expected value.
   *
   * @param $original
   *   A string with the original path that is run through generateFrommPath().
   * @param $final
   *   A string with the expected result after generateFrommPath().
   */
  protected function assertUrlOutboundAlter($original, $final) {
    // Test outbound altering.
    $result = $this->container->get('path_processor_manager')->processOutbound($original);
    $this->assertSame($final, $result, new FormattableMarkup('Altered outbound URL %original, expected %final, and got %result.', ['%original' => $original, '%final' => $final, '%result' => $result]));
  }

  /**
   * Assert that an inbound path is altered to an expected value.
   *
   * @param $original
   *   The original path before it has been altered by inbound URL processing.
   * @param $final
   *   A string with the expected result.
   */
  protected function assertUrlInboundAlter($original, $final) {
    // Test inbound altering.
    $result = $this->container->get('path_alias.manager')->getPathByAlias($original);
    $this->assertSame($final, $result, new FormattableMarkup('Altered inbound URL %original, expected %final, and got %result.', ['%original' => $original, '%final' => $final, '%result' => $result]));
  }

}
