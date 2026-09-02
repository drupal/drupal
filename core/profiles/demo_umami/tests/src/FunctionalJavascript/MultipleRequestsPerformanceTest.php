<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests demo_umami profile performance.
 */
#[Group('Performance')]
#[Group('#slow')]
#[RequiresPhpExtension('apcu')]
#[RunTestsInSeparateProcesses]
class MultipleRequestsPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Test performance of various with various cache permutations.
   */
  public function testUmamiPerformance(): void {
    $this->doTestFrontAndRecipesPages();
    $this->doTestFrontAndRecipesPagesAuthenticated();
    $this->doTestFrontAndRecipesPagesEditor();
    $this->doTestNodeAddPagesAuthor();
    $this->doTestLogin();
  }

  /**
   * Checks the asset requests made when the front and recipe pages are visited.
   */
  protected function doTestFrontAndRecipesPages(): void {
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
   * Tests the performance of logging in.
   */
  protected function doTestLogin(): void {
    $this->drupalLogout();
    $user = $this->createUser();
    // Create a user and log them in to warm all caches. Manually submit the
    // form so that we repeat the same steps when recording performance data. Do
    // this twice so that any caches which take two requests to warm are also
    // covered.
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('user/login');
      $this->submitLoginForm($user);
      $this->drupalLogout();
    }

    $this->drupalGet('user/login');
    $performance_data = $this->collectPerformanceData(function () use ($user) {
      $this->submitLoginForm($user);
    }, 'umamiLogin');

    $this->assertQueriesByName('umamiLogin', $performance_data->getQueries());
    $this->assertMetricsByName('umamiLogin', $performance_data);
    $this->drupalLogout();
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
   * Submit the user login form.
   */
  protected function submitLoginForm($account): void {
    $this->submitForm([
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ], 'Log in');
  }

}
