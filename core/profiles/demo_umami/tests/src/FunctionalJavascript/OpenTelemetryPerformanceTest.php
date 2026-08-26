<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore languageswitcher
/**
 * Tests demo_umami profile performance.
 */
#[Group('OpenTelemetry')]
#[Group('#slow')]
#[RequiresPhpExtension('apcu')]
#[RunTestsInSeparateProcesses]
class OpenTelemetryPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Test performance of various with various cache permutations.
   */
  public function testUmamiPerformance(): void {
    $this->testNodePageColdCache();
    $this->testNodePageCoolCache();
    $this->testNodePageWarmCache();
    $this->testNodePageHotCache();
    $this->testFrontPageColdCache();
    $this->testFrontPageCoolCache();
    $this->testFrontPageHotCache();
    $this->doTestFrontPageAuthenticatedWarmCache();
    $this->doTestNodePageAdministrator();
    $this->doTestFrontAndRecipesPages();
    $this->doTestFrontAndRecipesPagesAuthenticated();
    $this->doTestFrontAndRecipesPagesEditor();
    $this->doTestNodeAddPagesAuthor();
  }

  /**
   * Logs node page tracing data with a cold cache.
   */
  protected function testNodePageColdCache(): void {
    // Request the node page twice then clear caches, this allows asset
    // aggregate requests to complete so they are excluded from the performance
    // test itself. Including the asset aggregates would lead to
    // a non-deterministic test since they happen in parallel and therefore post
    // response tasks run in different orders each time.
    $this->drupalGet('node/1');
    // Allow time for image style and aggregate requests to finish.
    sleep(2);
    $this->drupalGet('node/1');
    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageColdCache');
    $this->assertSession()->pageTextContains('quiche');

    $this->assertMetricsByName('umamiNodePageColdCache', $performance_data);
  }

  /**
   * Logs node page tracing data with a hot cache.
   *
   * Hot here means that all possible caches are warmed.
   */
  protected function testNodePageHotCache(): void {
    // Request the page twice so that asset aggregates are definitely cached in
    // the browser cache.
    $this->drupalGet('node/1');
    $this->drupalGet('node/1');

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageHotCache');
    $this->assertSession()->pageTextContains('quiche');

    $this->assertMetricsByName('umamiNodePageHotCache', $performance_data);
  }

  /**
   * Logs node/1 tracing data with a cool cache.
   *
   * Cool here means that 'global' site caches are warm but anything
   * specific to the route or path is cold.
   */
  protected function testNodePageCoolCache(): void {
    // First of all visit the node page to ensure the image style exists.
    $this->drupalGet('node/1');
    $this->clearCaches();
    // Now visit a non-node page to warm non-route-specific caches.
    $this->drupalGet('user/login');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageCoolCache');
    $this->assertSession()->pageTextContains('quiche');

    $this->assertMetricsByName('umamiNodePageCoolCache', $performance_data);
  }

  /**
   * Log node/1 tracing data with a warm cache.
   *
   * Warm here means that 'global' site caches and route-specific caches are
   * warm but caches specific to this particular node/path are not.
   */
  protected function testNodePageWarmCache(): void {
    // First of all visit the node page to ensure the image style exists.
    $this->drupalGet('node/1');
    // Allow time for the image style and asset aggregate requests to finish.
    sleep(1);
    $this->clearCaches();
    // Now visit a different node page to warm non-path-specific caches.
    $this->drupalGet('node/2');
    sleep(1);
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageWarmCache');
    $this->assertSession()->pageTextContains('quiche');
    // Check the actual queries so that if a change simultaneously adds and
    // removes a query the change is detected.
    $this->assertQueriesByName('umamiNodePageWarmCache', $performance_data->getQueries());
    $this->assertMetricsByName('umamiNodePageWarmCache', $performance_data);
  }

  /**
   * Logs front page tracing data with a cold cache.
   */
  protected function testFrontPageColdCache(): void {
    // Request the front page twice then clear caches, this allows asset
    // aggregate requests to complete so they are excluded from the performance
    // test itself. Including the asset aggregates would lead to
    // a non-deterministic test since they happen in parallel and therefore post
    // response tasks run in different orders each time.
    $this->drupalGet('<front>');
    $this->drupalGet('<front>');
    sleep(2);
    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageColdCache');
    $this->assertSession()->pageTextContains('Umami');

    $this->assertMetricsByName('umamiFrontPageColdCache', $performance_data);
  }

  /**
   * Logs front page tracing data with a hot cache.
   *
   * Hot here means that all possible caches are warmed.
   */
  protected function testFrontPageHotCache(): void {
    // Request the page twice so that asset aggregates and image derivatives are
    // definitely cached in the browser cache. The first response builds the
    // file and serves from PHP with private, no-store headers. The second
    // request will get the file served directly from disk by the browser with
    // cacheable headers, so only the third request actually has the files
    // in the browser cache.
    $this->drupalGet('<front>');
    $this->drupalGet('<front>');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageHotCache');
    $this->assertSession()->pageTextContains('Umami');

    $this->assertMetricsByName('umamiFrontPageHotCache', $performance_data);
  }

  /**
   * Logs front page tracing data with a lukewarm cache.
   *
   * Cool here means that 'global' site caches are warm but anything
   * specific to the front page is cold.
   */
  protected function testFrontPageCoolCache(): void {
    // First of all visit the front page to ensure the image style exists.
    $this->drupalGet('<front>');
    sleep(2);
    $this->clearCaches();
    // Now visit a different page to warm non-route-specific caches.
    $this->drupalGet('user/login');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageCoolCache');

    $this->assertMetricsByName('umamiFrontPageCoolCache', $performance_data);
  }

  /**
   * Logs front page tracing data with an authenticated user and warm cache.
   */
  protected function doTestFrontPageAuthenticatedWarmCache(): void {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    sleep(2);
    $this->drupalGet('<front>');
    sleep(2);
    $this->drupalGet('<front>');
    sleep(2);
    $this->drupalGet('<front>');
    sleep(2);

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'authenticatedFrontPage');

    $this->assertQueriesByName('authenticatedFrontPage', $performance_data->getQueries());
    $this->assertMetricsByName('authenticatedFrontPage', $performance_data);
  }

  /**
   * Logs node page performance with an administrator.
   */
  protected function doTestNodePageAdministrator(): void {
    // Create a user with most important admin permissions, but not access to
    // contextual links. This is because contextual module makes an AJAX request
    // dependent on the content of browser local storage, which can make
    // performance testing indeterminate, use a deterministic user role.
    $this->createRole([
      'administer nodes',
      'bypass node access',
      'access administration pages',
      'administer site configuration',
      'administer modules',
      'administer themes',
      'access site reports',
      'administer users',
      'access navigation',
      'administer media',
      'access files overview',
      'administer blocks',
      'administer block content',
      'administer taxonomy',
      'administer menu',
    ], 'test_admin');
    $user = $this->drupalCreateUser(values: ['roles' => ['test_admin']]);

    $this->drupalLogin($user);

    // Ensure the asset cache warming request happens with empty caches,
    // otherwise the unique combination of assets for the performance request
    // may not have been created yet.
    $this->clearCaches();

    $this->drupalGet('node/1');
    sleep(2);
    $this->drupalGet('node/1');
    sleep(2);

    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'administratorNodePage');

    $this->assertMetricsByName('administratorNodePage', $performance_data);
  }

  /**
   * Checks the asset requests made when the front and recipe pages are visited.
   */
  protected function doTestFrontAndRecipesPages(): void {
    $this->drupalLogout();
    $performance_data = $this->collectPerformanceData(function () {
      $this->doRequests();
    }, 'umamiFrontAndRecipePages');

    $this->assertMetricsByName('umamiFrontAndRecipePages', $performance_data, [
      'ScriptCount',
      'ScriptBytes',
      'StylesheetCount',
      'StylesheetBytes',
    ]);
  }

  /**
   * Checks the front and recipe page asset requests as an authenticated user.
   */
  protected function doTestFrontAndRecipesPagesAuthenticated(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);
    sleep(2);
    $performance_data = $this->collectPerformanceData(function () {
      $this->doRequests();
    }, 'umamiFrontAndRecipePagesAuthenticated');

    $this->assertMetricsByName('umamiFrontAndRecipePagesAuthenticated', $performance_data, [
      'ScriptCount',
      'ScriptBytes',
      'StylesheetCount',
      'StylesheetBytes',
    ]);
  }

  /**
   * Checks the front and recipe page asset requests as an editor.
   */
  protected function doTestFrontAndRecipesPagesEditor(): void {
    $user = $this->createUser();
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);
    sleep(2);
    $performance_data = $this->collectPerformanceData(function () {
      $this->doRequests();
    }, 'umamiFrontAndRecipePagesEditor');
    $this->assertMetricsByName('umamiFrontAndRecipePagesEditor', $performance_data, [
      'ScriptCount',
      'ScriptBytes',
      'StylesheetCount',
      'StylesheetBytes',
    ]);
  }

  /**
   * Checks the node/add page asset requests as an author.
   */
  protected function doTestNodeAddPagesAuthor(): void {
    $user = $this->createUser();
    $user->addRole('author');
    $user->save();
    $this->drupalLogin($user);
    $this->drupalGet('<front>');
    // Give additional time for the request and all assets to be returned
    // before making the next request.
    sleep(2);
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/add/article');
      sleep(2);
      $this->drupalGet('node/add/recipe');
      sleep(2);
      $this->drupalGet('node/add/page');
    }, 'umamiNodeAddEditor');
    $this->assertMetricsByName('umamiNodeAddEditor', $performance_data, [
      'ScriptCount',
      'ScriptBytes',
      'StylesheetCount',
      'StylesheetBytes',
    ]);
  }

  /**
   * Performs a common set of requests so the above test methods stay in sync.
   */
  protected function doRequests(): void {
    $this->drupalGet('<front>');
    // Give additional time for the request and all assets to be returned
    // before making the next request.
    sleep(2);
    $this->drupalGet('articles');
    sleep(2);
    $this->drupalGet('recipes');
    sleep(2);
    $this->drupalGet('recipes/deep-mediterranean-quiche');
  }

  /**
   * Clear caches.
   */
  protected function clearCaches(): void {
    foreach (Cache::getBins() as $bin) {
      $bin->deleteAll();
    }
  }

}
