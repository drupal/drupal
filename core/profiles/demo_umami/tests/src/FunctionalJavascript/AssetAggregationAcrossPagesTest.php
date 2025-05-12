<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests demo_umami profile performance.
 *
 * @group #slow
 */
class AssetAggregationAcrossPagesTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Checks the asset requests made when the front and recipe pages are visited.
   */
  public function testFrontAndRecipesPages(): void {
    $performance_data = $this->collectPerformanceData(function () {
      $this->doRequests();
    }, 'umamiFrontAndRecipePages');

    $expected = [
      'ScriptCount' => 1,
      'ScriptBytes' => 11700,
      'StylesheetCount' => 6,
      'StylesheetBytes' => 119600,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Checks the front and recipe page asset requests as an authenticated user.
   */
  public function testFrontAndRecipesPagesAuthenticated(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);
    sleep(2);
    $performance_data = $this->collectPerformanceData(function () {
      $this->doRequests();
    }, 'umamiFrontAndRecipePagesAuthenticated');

    $expected = [
      'ScriptCount' => 3,
      'ScriptBytes' => 170500,
      'StylesheetCount' => 5,
      'StylesheetBytes' => 85600,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Checks the front and recipe page asset requests as an editor.
   */
  public function testFrontAndRecipesPagesEditor(): void {
    $user = $this->createUser();
    $user->addRole('editor');
    $user->save();
    $this->drupalLogin($user);
    sleep(2);
    $performance_data = $this->collectPerformanceData(function () {
      $this->doRequests();
    }, 'umamiFrontAndRecipePagesEditor');
    $expected = [
      'ScriptCount' => 5,
      'ScriptBytes' => 335637,
      'StylesheetCount' => 5,
      'StylesheetBytes' => 205700,
    ];
    $this->assertMetrics($expected, $performance_data);
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

}
