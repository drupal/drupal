<?php

namespace Drupal\Tests\Core\Batch;

use Drupal\Core\Batch\Percentage;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Batch\Percentage
 * @group Batch
 *
 * Tests the Batch helper object to make sure that the rounding works properly
 * in all cases.
 */
class PercentagesTest extends UnitTestCase {
  protected $testCases = [];

  /**
   * @dataProvider providerTestPercentages
   * @covers ::format
   */
  public function testPercentages($total, $current, $expected_result) {
    $actual_result = Percentage::format($total, $current);
    $this->assertEquals($actual_result, $expected_result, sprintf('The expected the batch api percentage at the state %s/%s is %s%% and got %s%%.', $current, $total, $expected_result, $actual_result));
  }

  /**
   * Provide data for batch unit tests.
   *
   * @return array
   *   An array of data used by the test.
   */
  public function providerTestPercentages() {
    // Set up an array of test cases.
    return [
      // array(total, current, expected).
      // 1/2 is 50%.
      [2, 1, '50'],
      // Though we should never encounter a case where the current set is set
      // 0, if we did, we should get 0%.
      [3, 0, '0'],
      // 1/3 is closer to 33% than to 34%.
      [3, 1, '33'],
      // 2/3 is closer to 67% than to 66%.
      [3, 2, '67'],
      // 1/199 should round up to 1%.
      [199, 1, '1'],
      // 198/199 should round down to 99%.
      [199, 198, '99'],
      // 199/200 would have rounded up to 100%, which would give the false
      // impression of being finished, so we add another digit and should get
      // 99.5%.
      [200, 199, '99.5'],
      // The same logic holds for 1/200: we should get 0.5%.
      [200, 1, '0.5'],
      // Numbers that come out evenly, such as 50/200, should be forced to have
      // extra digits for consistency.
      [200, 50, '25.0'],
      // Regardless of number of digits we're using, 100% should always just be
      // 100%.
      [200, 200, '100'],
      // 1998/1999 should similarly round down to 99.9%.
      [1999, 1998, '99.9'],
      // 1999/2000 should add another digit and go to 99.95%.
      [2000, 1999, '99.95'],
      // 19999/20000 should add yet another digit and go to 99.995%.
      [20000, 19999, '99.995'],
      // The next five test cases simulate a batch with a single operation
      // ('total' equals 1) that takes several steps to complete. Within the
      // operation, we imagine that there are 501 items to process, and 100 are
      // completed during each step. The percentages we get back should be
      // rounded the usual way for the first few passes (i.e., 20%, 40%, etc.),
      // but for the last pass through, when 500 out of 501 items have been
      // processed, we do not want to round up to 100%, since that would
      // erroneously indicate that the processing is complete.
      ['total' => 1, 'current' => 100 / 501, '20'],
      ['total' => 1, 'current' => 200 / 501, '40'],
      ['total' => 1, 'current' => 300 / 501, '60'],
      ['total' => 1, 'current' => 400 / 501, '80'],
      ['total' => 1, 'current' => 500 / 501, '99.8'],
    ];
  }

}
