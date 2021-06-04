<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\views\Views;

/**
 * Tests core view token replacement.
 *
 * @group views
 */
class TokenReplaceTest extends ViewsKernelTestBase {

  protected static $modules = ['system'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_tokens', 'test_invalid_tokens'];

  /**
   * Tests core token replacements generated from a view.
   */
  public function testTokenReplacement() {
    $token_handler = \Drupal::token();
    $view = Views::getView('test_tokens');
    $view->setDisplay('page_1');
    // Force the view to span more than one page to better test page_count.
    $view->display_handler->getPlugin('pager')->setItemsPerPage(4);
    $this->executeView($view);

    $expected = [
      '[view:label]' => 'Test tokens',
      '[view:description]' => 'Test view to token replacement tests.',
      '[view:id]' => 'test_tokens',
      '[view:title]' => 'Test token page',
      '[view:url]' => $view->getUrl(NULL, 'page_1')->setAbsolute(TRUE)->toString(),
      '[view:total-rows]' => '5',
      '[view:base-table]' => 'views_test_data',
      '[view:base-field]' => 'id',
      '[view:items-per-page]' => '4',
      '[view:current-page]' => '1',
      '[view:page-count]' => '2',
    ];

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($view->storage);
    $metadata_tests = [];
    $metadata_tests['[view:label]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:description]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:id]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:title]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:total-rows]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:base-table]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:base-field]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:items-per-page]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:current-page]'] = $base_bubbleable_metadata;
    $metadata_tests['[view:page-count]'] = $base_bubbleable_metadata;

    foreach ($expected as $token => $expected_output) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $token_handler->replace($token, ['view' => $view], [], $bubbleable_metadata);
      $this->assertSame($expected_output, $output, new FormattableMarkup('Token %token replaced correctly.', ['%token' => $token]));
      $this->assertEquals($metadata_tests[$token], $bubbleable_metadata);
    }
  }

  /**
   * Tests core token replacements generated from a view.
   */
  public function testTokenReplacementWithMiniPager() {
    $token_handler = \Drupal::token();
    $view = Views::getView('test_tokens');
    $view->setDisplay('page_3');
    $this->executeView($view);

    $this->assertTrue($view->get_total_rows, 'The query was set to calculate the total number of rows.');

    $expected = [
      '[view:label]' => 'Test tokens',
      '[view:description]' => 'Test view to token replacement tests.',
      '[view:id]' => 'test_tokens',
      '[view:title]' => 'Test token page with minipager',
      '[view:url]' => $view->getUrl(NULL, 'page_3')
        ->setAbsolute(TRUE)
        ->toString(),
      '[view:total-rows]' => '5',
      '[view:base-table]' => 'views_test_data',
      '[view:base-field]' => 'id',
      '[view:items-per-page]' => '2',
      '[view:current-page]' => '1',
      '[view:page-count]' => '3',
    ];

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($view->storage);

    foreach ($expected as $token => $expected_output) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $token_handler->replace($token, ['view' => $view], [], $bubbleable_metadata);
      $this->assertSame($expected_output, $output, sprintf('Token %s replaced correctly.', $token));
      $this->assertEquals($base_bubbleable_metadata, $bubbleable_metadata);
    }
  }

  /**
   * Tests core token replacements generated from a view without results.
   */
  public function testTokenReplacementNoResults() {
    $token_handler = \Drupal::token();
    $view = Views::getView('test_tokens');
    $view->setDisplay('page_2');
    $this->executeView($view);

    $expected = [
      '[view:page-count]' => '1',
    ];

    foreach ($expected as $token => $expected_output) {
      $output = $token_handler->replace($token, ['view' => $view]);
      $this->assertSame($expected_output, $output, new FormattableMarkup('Token %token replaced correctly.', ['%token' => $token]));
    }
  }

  /**
   * Tests path token replacements generated from a view without a path.
   */
  public function testTokenReplacementNoPath() {
    $token_handler = \Drupal::token();
    $view = Views::getView('test_invalid_tokens');
    $view->setDisplay('block_1');
    $this->executeView($view);

    $expected = [
      '[view:url]' => '',
    ];

    foreach ($expected as $token => $expected_output) {
      $output = $token_handler->replace($token, ['view' => $view]);
      $this->assertSame($expected_output, $output, new FormattableMarkup('Token %token replaced correctly.', ['%token' => $token]));
    }
  }

}
