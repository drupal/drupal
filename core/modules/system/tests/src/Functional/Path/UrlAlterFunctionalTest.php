<?php

namespace Drupal\Tests\system\Functional\Path;

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests altering the inbound path and the outbound path.
 *
 * @group Path
 */
class UrlAlterFunctionalTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['path', 'forum', 'url_alter_test'];

  /**
   * Test that URL altering works and that it occurs in the correct order.
   */
  public function testUrlAlter() {
    // Ensure that the url_alias table exists after Drupal installation.
    $this->assertTrue(Database::getConnection()->schema()->tableExists('url_alias'), 'The url_alias table exists after Drupal installation.');

    // User names can have quotes and plus signs so we should ensure that URL
    // altering works with this.
    $account = $this->drupalCreateUser(['administer url aliases'], "a'foo+bar");
    $this->drupalLogin($account);

    $uid = $account->id();
    $name = $account->getAccountName();

    // Test a single altered path.
    $this->drupalGet("user/$name");
    $this->assertResponse('200', 'The user/username path gets resolved correctly');
    $this->assertUrlOutboundAlter("/user/$uid", "/user/$name");

    // Test that a path always uses its alias.
    $path = ['source' => "/user/$uid/test1", 'alias' => '/alias/test1'];
    $this->container->get('path.alias_storage')->save($path['source'], $path['alias']);
    $this->rebuildContainer();
    $this->assertUrlInboundAlter('/alias/test1', "/user/$uid/test1");
    $this->assertUrlOutboundAlter("/user/$uid/test1", '/alias/test1');

    // Test adding an alias via the UI.
    $edit = ['source' => "/user/$uid/edit", 'alias' => '/alias/test2'];
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));
    $this->assertText(t('The alias has been saved.'));
    $this->drupalGet('alias/test2');
    $this->assertResponse('200', 'The path alias gets resolved correctly');
    $this->assertUrlOutboundAlter("/user/$uid/edit", '/alias/test2');

    // Test a non-existent user is not altered.
    $uid++;
    $this->assertUrlOutboundAlter("/user/$uid", "/user/$uid");

    // Test that 'forum' is altered to 'community' correctly, both at the root
    // level and for a specific existing forum.
    $this->drupalGet('community');
    $this->assertText('General discussion', 'The community path gets resolved correctly');
    $this->assertUrlOutboundAlter('/forum', '/community');
    $forum_vid = $this->config('forum.settings')->get('vocabulary');
    $term_name = $this->randomMachineName();
    $term = Term::create([
      'name' => $term_name,
      'vid' => $forum_vid,
    ]);
    $term->save();
    $this->drupalGet("community/" . $term->id());
    $this->assertText($term_name, 'The community/{tid} path gets resolved correctly');
    $this->assertUrlOutboundAlter("/forum/" . $term->id(), "/community/" . $term->id());

    // Test outbound query string altering.
    $url = Url::fromRoute('user.login');
    $this->assertIdentical(\Drupal::request()->getBaseUrl() . '/user/login?foo=bar', $url->toString());
  }

  /**
   * Assert that an outbound path is altered to an expected value.
   *
   * @param $original
   *   A string with the original path that is run through generateFrommPath().
   * @param $final
   *   A string with the expected result after generateFrommPath().
   *
   * @return
   *   TRUE if $original was correctly altered to $final, FALSE otherwise.
   */
  protected function assertUrlOutboundAlter($original, $final) {
    // Test outbound altering.
    $result = $this->container->get('path_processor_manager')->processOutbound($original);
    return $this->assertIdentical($result, $final, format_string('Altered outbound URL %original, expected %final, and got %result.', ['%original' => $original, '%final' => $final, '%result' => $result]));
  }

  /**
   * Assert that a inbound path is altered to an expected value.
   *
   * @param $original
   *   The original path before it has been altered by inbound URL processing.
   * @param $final
   *   A string with the expected result.
   *
   * @return
   *   TRUE if $original was correctly altered to $final, FALSE otherwise.
   */
  protected function assertUrlInboundAlter($original, $final) {
    // Test inbound altering.
    $result = $this->container->get('path.alias_manager')->getPathByAlias($original);
    return $this->assertIdentical($result, $final, format_string('Altered inbound URL %original, expected %final, and got %result.', ['%original' => $original, '%final' => $final, '%result' => $result]));
  }

}
