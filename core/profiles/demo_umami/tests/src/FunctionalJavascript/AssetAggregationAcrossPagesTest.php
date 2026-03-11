<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests demo_umami profile performance.
 */
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class AssetAggregationAcrossPagesTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Tests asset aggregation duplication across multiple pages.
   */
  public function testAssetsAcrossMultiplePages(): void {
    $this->doTestFrontAndRecipesPages();
    $this->doTestFrontAndRecipesPagesAuthenticated();
    $this->doTestFrontAndRecipesPagesEditor();
    $this->doTestNodeAddPagesAuthor();
  }

  /**
   * Checks the asset requests made when the front and recipe pages are visited.
   */
  protected function doTestFrontAndRecipesPages(): void {
    $performance_data = $this->collectPerformanceData(function () {
      $this->doRequests();
    }, 'umamiFrontAndRecipePages');

    $expected = [
      'ScriptCount' => 1,
      'ScriptBytes' => 11700,
      'StylesheetCount' => 6,
      'StylesheetBytes' => 117400,
    ];
    $this->assertMetrics($expected, $performance_data);
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

    $expected = [
      'ScriptCount' => 3,
      'ScriptBytes' => 73750,
      'StylesheetCount' => 5,
      'StylesheetBytes' => 79400,
    ];
    $this->assertMetrics($expected, $performance_data);
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
    $expected = [
      'ScriptCount' => 5,
      'ScriptBytes' => 207212,
      'StylesheetCount' => 5,
      'StylesheetBytes' => 165250,
    ];
    $this->assertMetrics($expected, $performance_data);
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
    $expected = [
      'ScriptCount' => 15,
      'ScriptBytes' => 3810027,
      'StylesheetCount' => 8,
      'StylesheetBytes' => 619086,
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
