<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\Tests\PerformanceData;

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
    $performance_data = $this->doRequests();
    $this->assertSame(4, $performance_data->getStylesheetCount());
    $this->assertLessThan(82000, $performance_data->getStylesheetBytes());
    $this->assertSame(1, $performance_data->getScriptCount());
    $this->assertLessThan(12000, $performance_data->getScriptBytes());
  }

  /**
   * Checks the asset requests made when the front and recipe pages are visited.
   */
  public function testFrontAndRecipesPagesAuthenticated(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);
    $this->rebuildAll();
    $performance_data = $this->doRequests();
    $this->assertSame(4, $performance_data->getStylesheetCount());
    $this->assertLessThan(87000, $performance_data->getStylesheetBytes());
    $this->assertSame(1, $performance_data->getScriptCount());
    $this->assertLessThan(133000, $performance_data->getScriptBytes());
  }

  /**
   * Helper to do requests so the above test methods stay in sync.
   */
  protected function doRequests(): PerformanceData {
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
      // Give additional time for the request and all assets to be returned
      // before making the next request.
      sleep(2);
      $this->drupalGet('articles');
    }, 'umamiFrontAndRecipePages');
    return $performance_data;
  }

}
