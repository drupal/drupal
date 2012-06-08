<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Batch\PercentagesUnitTest.
 */

namespace Drupal\system\Tests\Batch;

use Drupal\simpletest\UnitTestBase;

/**
 * Unit tests of progress percentage rounding.
 *
 * Tests the function _batch_api_percentage() to make sure that the rounding
 * works properly in all cases.
 */
class PercentagesUnitTest extends UnitTestBase {
  protected $testCases = array();

  public static function getInfo() {
    return array(
      'name' => 'Batch percentages',
      'description' => 'Unit tests of progress percentage rounding.',
      'group' => 'Batch API',
    );
  }

  function setUp() {
    // Set up an array of test cases, where the expected values are the keys,
    // and the values are arrays with the keys 'total' and 'current',
    // corresponding with the function parameters of _batch_api_percentage().
    $this->testCases = array(
      // 1/2 is 50%.
      '50' => array('total' => 2, 'current' => 1),
      // Though we should never encounter a case where the current set is set
      // 0, if we did, we should get 0%.
      '0' => array('total' => 3, 'current' => 0),
      // 1/3 is closer to 33% than to 34%.
      '33' => array('total' => 3, 'current' => 1),
      // 2/3 is closer to 67% than to 66%.
      '67' => array('total' => 3, 'current' => 2),
      // A full 3/3 should equal 100%.
      '100' => array('total' => 3, 'current' => 3),
      // 1/199 should round up to 1%.
      '1' => array('total' => 199, 'current' => 1),
      // 198/199 should round down to 99%.
      '99' => array('total' => 199, 'current' => 198),
      // 199/200 would have rounded up to 100%, which would give the false
      // impression of being finished, so we add another digit and should get
      // 99.5%.
      '99.5' => array('total' => 200, 'current' => 199),
      // The same logic holds for 1/200: we should get 0.5%.
      '0.5' => array('total' => 200, 'current' => 1),
      // Numbers that come out evenly, such as 50/200, should be forced to have
      // extra digits for consistancy.
      '25.0' => array('total' => 200, 'current' => 50),
      // Regardless of number of digits we're using, 100% should always just be
      // 100%.
      '100' => array('total' => 200, 'current' => 200),
      // 1998/1999 should similarly round down to 99.9%.
      '99.9' => array('total' => 1999, 'current' => 1998),
      // 1999/2000 should add another digit and go to 99.95%.
      '99.95' => array('total' => 2000, 'current' => 1999),
      // 19999/20000 should add yet another digit and go to 99.995%.
      '99.995' => array('total' => 20000, 'current' => 19999),
      // The next five test cases simulate a batch with a single operation
      // ('total' equals 1) that takes several steps to complete. Within the
      // operation, we imagine that there are 501 items to process, and 100 are
      // completed during each step. The percentages we get back should be
      // rounded the usual way for the first few passes (i.e., 20%, 40%, etc.),
      // but for the last pass through, when 500 out of 501 items have been
      // processed, we do not want to round up to 100%, since that would
      // erroneously indicate that the processing is complete.
      '20' => array('total' => 1, 'current' => 100/501),
      '40' => array('total' => 1, 'current' => 200/501),
      '60' => array('total' => 1, 'current' => 300/501),
      '80' => array('total' => 1, 'current' => 400/501),
      '99.8' => array('total' => 1, 'current' => 500/501),
    );
    require_once DRUPAL_ROOT . '/core/includes/batch.inc';
    parent::setUp();
  }

  /**
   * Test the _batch_api_percentage() function.
   */
  function testBatchPercentages() {
    foreach ($this->testCases as $expected_result => $arguments) {
      // PHP sometimes casts numeric strings that are array keys to integers,
      // cast them back here.
      $expected_result = (string) $expected_result;
      $total = $arguments['total'];
      $current = $arguments['current'];
      $actual_result = _batch_api_percentage($total, $current);
      if ($actual_result === $expected_result) {
        $this->pass(t('Expected the batch api percentage at the state @numerator/@denominator to be @expected%, and got @actual%.', array('@numerator' => $current, '@denominator' => $total, '@expected' => $expected_result, '@actual' => $actual_result)));
      }
      else {
        $this->fail(t('Expected the batch api percentage at the state @numerator/@denominator to be @expected%, but got @actual%.', array('@numerator' => $current, '@denominator' => $total, '@expected' => $expected_result, '@actual' => $actual_result)));
      }
    }
  }
}
