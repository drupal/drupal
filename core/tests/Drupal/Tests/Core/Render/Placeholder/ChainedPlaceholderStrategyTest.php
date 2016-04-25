<?php

namespace Drupal\Tests\Core\Render\Placeholder;

use Drupal\Core\Render\Placeholder\ChainedPlaceholderStrategy;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Placeholder\ChainedPlaceholderStrategy
 * @group Render
 */
class ChainedPlaceholderStrategyTest extends UnitTestCase {

  /**
   * @covers ::addPlaceholderStrategy
   * @covers ::processPlaceholders
   *
   * @dataProvider providerProcessPlaceholders
   */
  public function testProcessPlaceholders($strategies, $placeholders, $result) {
    $chained_placeholder_strategy = new ChainedPlaceholderStrategy();

    foreach ($strategies as $strategy) {
      $chained_placeholder_strategy->addPlaceholderStrategy($strategy);
    }

    $this->assertEquals($result, $chained_placeholder_strategy->processPlaceholders($placeholders));
  }

  /**
   * Provides a list of render strategies, placeholders and results.
   *
   * @return array
   */
  public function providerProcessPlaceholders() {
    $data = [];

    // Empty placeholders.
    $data['empty placeholders'] = [[], [], []];

    // Placeholder removing strategy.
    $placeholders = [
      'remove-me' => ['#markup' => 'I-am-a-llama-that-will-be-removed-sad-face.'],
    ];

    $prophecy = $this->prophesize('\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface');
    $prophecy->processPlaceholders($placeholders)->willReturn([]);
    $dev_null_strategy = $prophecy->reveal();

    $data['placeholder removing strategy'] = [[$dev_null_strategy], $placeholders, []];

    // Fake Single Flush strategy.
    $placeholders = [
      '67890' => ['#markup' => 'special-placeholder'],
    ];

    $prophecy = $this->prophesize('\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface');
    $prophecy->processPlaceholders($placeholders)->willReturn($placeholders);
    $single_flush_strategy = $prophecy->reveal();

    $data['fake single flush strategy'] = [[$single_flush_strategy], $placeholders, $placeholders];

    // Fake ESI strategy.
    $placeholders = [
      '12345' => ['#markup' => 'special-placeholder-for-esi'],
    ];
    $result = [
      '12345' => ['#markup' => '<esi:include src="/fragment/12345" />'],
    ];

    $prophecy = $this->prophesize('\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface');
    $prophecy->processPlaceholders($placeholders)->willReturn($result);
    $esi_strategy = $prophecy->reveal();

    $data['fake esi strategy'] = [[$esi_strategy], $placeholders, $result];

    // ESI + SingleFlush strategy (ESI replaces all).
    $prophecy = $this->prophesize('\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface');
    $prophecy->processPlaceholders($placeholders)->willReturn($result);
    $esi_strategy = $prophecy->reveal();

    $prophecy = $this->prophesize('\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface');
    $prophecy->processPlaceholders($placeholders)->shouldNotBeCalled();
    $prophecy->processPlaceholders($result)->shouldNotBeCalled();
    $prophecy->processPlaceholders([])->shouldNotBeCalled();
    $single_flush_strategy = $prophecy->reveal();

    $data['fake esi and single_flush strategy - esi replaces all'] = [[$esi_strategy, $single_flush_strategy], $placeholders, $result];

    // ESI + SingleFlush strategy (mixed).
    $placeholders = [
      '12345' => ['#markup' => 'special-placeholder-for-ESI'],
      '67890' => ['#markup' => 'special-placeholder'],
      'foo' => ['#markup' => 'bar'],
    ];

    $esi_result = [
      '12345' => ['#markup' => '<esi:include src="/fragment/12345" />'],
    ];

    $normal_result = [
      '67890' => ['#markup' => 'special-placeholder'],
      'foo' => ['#markup' => 'bar'],
    ];

    $result = $esi_result + $normal_result;

    $prophecy = $this->prophesize('\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface');
    $prophecy->processPlaceholders($placeholders)->willReturn($esi_result);
    $esi_strategy = $prophecy->reveal();

    $prophecy = $this->prophesize('\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface');
    $prophecy->processPlaceholders($normal_result)->willReturn($normal_result);
    $single_flush_strategy = $prophecy->reveal();

    $data['fake esi and single_flush strategy - mixed'] = [[$esi_strategy, $single_flush_strategy], $placeholders, $result];

    return $data;
  }

  /**
   * @covers ::processPlaceholders
   *
   * @expectedException \AssertionError
   * @expectedExceptionMessage At least one placeholder strategy must be present; by default the fallback strategy \Drupal\Core\Render\Placeholder\SingleFlushStrategy is always present.
   */
  public function testProcessPlaceholdersNoStrategies() {
    // Placeholders but no strategies defined.
    $placeholders = [
      'assert-me' => ['#markup' => 'I-am-a-llama-that-will-lead-to-an-assertion-by-the-chained-placeholder-strategy.'],
    ];

    $chained_placeholder_strategy = new ChainedPlaceholderStrategy();
    $chained_placeholder_strategy->processPlaceholders($placeholders);
  }

  /**
   * @covers ::processPlaceholders
   *
   * @expectedException \AssertionError
   * @expectedExceptionMessage Processed placeholders must be a subset of all placeholders.
   */
  public function testProcessPlaceholdersWithRoguePlaceholderStrategy() {
    // Placeholders but no strategies defined.
    $placeholders = [
      'assert-me' => ['#markup' => 'llama'],
    ];

    $result = [
      'assert-me' => ['#markup' => 'llama'],
      'new-placeholder' => ['#markup' => 'rogue llama'],
    ];

    $prophecy = $this->prophesize('\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface');
    $prophecy->processPlaceholders($placeholders)->willReturn($result);
    $rogue_strategy = $prophecy->reveal();

    $chained_placeholder_strategy = new ChainedPlaceholderStrategy();
    $chained_placeholder_strategy->addPlaceholderStrategy($rogue_strategy);
    $chained_placeholder_strategy->processPlaceholders($placeholders);
  }

}
