<?php

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Utility\TableSort;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests table sorting.
 *
 * @group Common
 */
class TableSortExtenderTest extends KernelTestBase {

  /**
   * Tests \Drupal\Core\Utility\TableSort::getContextFromRequest().
   */
  public function testTableSortInit() {

    // Test simple table headers.

    $headers = ['foo', 'bar', 'baz'];
    // Reset $request->query to prevent parameters from Simpletest and Batch API
    // ending up in $ts['query'].
    $expected_ts = [
      'name' => 'foo',
      'sql' => '',
      'sort' => 'asc',
      'query' => [],
    ];
    $request = Request::createFromGlobals();
    $request->query->replace([]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEqual($ts, $expected_ts, 'Simple table headers sorted correctly.');

    // Test with simple table headers plus $_GET parameters that should _not_
    // override the default.
    $request = Request::createFromGlobals();
    $request->query->replace([
      // This should not override the table order because only complex
      // headers are overridable.
      'order' => 'bar',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEqual($ts, $expected_ts, 'Simple table headers plus non-overriding $_GET parameters sorted correctly.');

    // Test with simple table headers plus $_GET parameters that _should_
    // override the default.
    $request = Request::createFromGlobals();
    $request->query->replace([
      'sort' => 'DESC',
      // Add an unrelated parameter to ensure that tablesort will include
      // it in the links that it creates.
      'alpha' => 'beta',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $expected_ts['sort'] = 'desc';
    $expected_ts['query'] = ['alpha' => 'beta'];
    $ts = TableSort::getContextFromRequest($headers, $request);
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEqual($ts, $expected_ts, 'Simple table headers plus $_GET parameters sorted correctly.');

    // Test complex table headers.

    $headers = [
      'foo',
      [
        'data' => '1',
        'field' => 'one',
        'sort' => 'asc',
        'colspan' => 1,
      ],
      [
        'data' => '2',
        'field' => 'two',
        'sort' => 'desc',
      ],
    ];
    // Reset $_GET from previous assertion.
    $request = Request::createFromGlobals();
    $request->query->replace([
      'order' => '2',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $expected_ts = [
      'name' => '2',
      'sql' => 'two',
      'sort' => 'desc',
      'query' => [],
    ];
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEqual($ts, $expected_ts, 'Complex table headers sorted correctly.');

    // Test complex table headers plus $_GET parameters that should _not_
    // override the default.
    $request = Request::createFromGlobals();
    $request->query->replace([
      // This should not override the table order because this header does not
      // exist.
      'order' => 'bar',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $expected_ts = [
      'name' => '1',
      'sql' => 'one',
      'sort' => 'asc',
      'query' => [],
    ];
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEqual($ts, $expected_ts, 'Complex table headers plus non-overriding $_GET parameters sorted correctly.');

    // Test complex table headers plus $_GET parameters that _should_
    // override the default.
    $request = Request::createFromGlobals();
    $request->query->replace([
      'order' => '1',
      'sort' => 'ASC',
      // Add an unrelated parameter to ensure that tablesort will include
      // it in the links that it creates.
      'alpha' => 'beta',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $expected_ts = [
      'name' => '1',
      'sql' => 'one',
      'sort' => 'asc',
      'query' => ['alpha' => 'beta'],
    ];
    $ts = TableSort::getContextFromRequest($headers, $request);
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEquals($expected_ts, $ts, 'Complex table headers plus $_GET parameters sorted correctly.');

    // Test the initial_click_sort parameter.
    $headers = [
      'foo',
      [
        'data' => '1',
        'field' => 'one',
        'initial_click_sort' => 'desc',
        'colspan' => 1,
      ],
      [
        'data' => '2',
        'field' => 'two',
      ],
      [
        'data' => '3',
        'field' => 'three',
        'initial_click_sort' => 'desc',
        'sort' => 'asc',
      ],
      [
        'data' => '4',
        'field' => 'four',
        'initial_click_sort' => 'asc',
      ],
      [
        'data' => '5',
        'field' => 'five',
        'initial_click_sort' => 'foo',
      ],
    ];
    $request = Request::createFromGlobals();
    $request->query->replace([
      'order' => '1',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $expected_ts = [
      'name' => '1',
      'sql' => 'one',
      'sort' => 'desc',
      'query' => [],
    ];
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEquals($expected_ts, $ts, 'Complex table headers using the initial_click_sort parameter are sorted correctly.');

    // Test that if the initial_click_sort parameter is not defined, the default
    // must be used instead (which is "asc").
    $request = Request::createFromGlobals();
    $request->query->replace([
      'order' => '2',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $expected_ts = [
      'name' => '2',
      'sql' => 'two',
      'sort' => 'asc',
      'query' => [],
    ];
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEquals($expected_ts, $ts, 'Complex table headers without using the initial_click_sort parameter are sorted correctly.');

    // Test that if the initial_click_sort parameter is defined, and the sort
    // parameter is defined as well, the sort parameter has precedence.
    $request = Request::createFromGlobals();
    $request->query->replace([
      'order' => '3',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $expected_ts = [
      'name' => '3',
      'sql' => 'three',
      'sort' => 'asc',
      'query' => [],
    ];
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEquals($expected_ts, $ts, 'Complex table headers using the initial_click_sort and sort parameters are sorted correctly.');

    // Test that if the initial_click_sort parameter is defined and the value
    // is "asc" it should be sorted correctly.
    $request = Request::createFromGlobals();
    $request->query->replace([
      'order' => '4',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $expected_ts = [
      'name' => '4',
      'sql' => 'four',
      'sort' => 'asc',
      'query' => [],
    ];
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEquals($expected_ts, $ts, 'Complex table headers with the initial_click_sort set as ASC are sorted correctly.');

    // Tests that if the initial_click_sort is defined with a non expected value
    // that value will be passed as the "sort" value.
    $request = Request::createFromGlobals();
    $request->query->replace([
      'order' => '5',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $ts = TableSort::getContextFromRequest($headers, $request);
    $expected_ts = [
      'name' => '5',
      'sql' => 'five',
      'sort' => 'foo',
      'query' => [],
    ];
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEquals($expected_ts, $ts, 'Complex table headers with the initial_click_sort set as foo are sorted correctly.');
  }

}
