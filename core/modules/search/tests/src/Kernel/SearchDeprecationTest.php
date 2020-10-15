<?php

namespace Drupal\Tests\search\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated search methods.
 *
 * @group legacy
 * @group search
 */
class SearchDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('search', [
      'search_index',
      'search_dataset',
      'search_total',
    ]);
    $this->installConfig(['search']);
  }

  public function testDeprecatedIndexSplit() {
    $this->expectDeprecation('search_index_split() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\search\SearchTextProcessorInterface::process() instead. See https://www.drupal.org/node/3078162');
    $this->assertEquals(["two", "words"], search_index_split("two words"));
  }

  public function testDeprecatedSimplify() {
    $this->expectDeprecation('search_simplify() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\search\SearchTextProcessorInterface::analyze() instead. See https://www.drupal.org/node/3078162');
    // cSpell:disable-next-line
    $this->assertEquals("vogel", search_simplify("Vögel"));
  }

  public function testExpandCjk() {
    $this->expectDeprecation('search_expand_cjk() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use a custom implementation of SearchTextProcessorInterface instead. instead. See https://www.drupal.org/node/3078162');
    $this->assertEquals(" 이런 ", search_expand_cjk(["이런"]));
  }

  public function testInvokePreprocess() {
    $this->expectDeprecation('search_invoke_preprocess() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use a custom implementation of SearchTextProcessorInterface instead. See https://www.drupal.org/node/3078162');
    $text = $this->randomString();
    search_invoke_preprocess($text);
    $this->assertIsString($text);
  }

}
