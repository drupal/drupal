<?php

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Component\Utility\Html;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests table sorting.
 *
 * @group Common
 */
class TableSortExtenderTest extends KernelTestBase {

  /**
   * Tests tablesort_init().
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
    $ts = tablesort_init($headers);
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
    $ts = tablesort_init($headers);
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
    $ts = tablesort_init($headers);
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
    $ts = tablesort_init($headers);
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
    $ts = tablesort_init($headers);
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
    $ts = tablesort_init($headers);
    $this->verbose(strtr('$ts: <pre>!ts</pre>', ['!ts' => Html::escape(var_export($ts, TRUE))]));
    $this->assertEqual($ts, $expected_ts, 'Complex table headers plus $_GET parameters sorted correctly.');
  }

}
