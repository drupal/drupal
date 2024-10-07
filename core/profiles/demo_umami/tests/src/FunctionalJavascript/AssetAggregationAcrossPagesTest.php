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
    $this->assertSame(6, $performance_data->getStylesheetCount());
    $this->assertLessThan(125000, $performance_data->getStylesheetBytes());
    $this->assertSame(1, $performance_data->getScriptCount());
    $this->assertLessThan(12000, $performance_data->getScriptBytes());
  }

  /**
   * Checks the asset requests made when the front and recipe pages are visited.
   */
  public function testFrontAndRecipesPagesAuthenticated(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);
    sleep(2);
    $performance_data = $this->collectPerformanceData(function () {
      $this->doRequests();
    }, 'umamiFrontAndRecipePagesAuthenticated');
    $this->assertSame(6, $performance_data->getStylesheetCount());
    $this->assertLessThan(132500, $performance_data->getStylesheetBytes());
    $this->assertSame(2, $performance_data->getScriptCount());
    $this->assertLessThan(250000, $performance_data->getScriptBytes());
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
