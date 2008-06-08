<?php
// $Id$

/**
 * Minimal drupal displayer. Accumulates output to $_output.
 * Based on HtmlReporter by Marcus Baker
 */
class DrupalReporter extends SimpleReporter {
  var $_output_error = '';
  var $_character_set;
  var $_fails_stack      = array(0);
  var $_exceptions_stack = array(0);
  var $_passes_stack     = array(0);
  var $_progress_stack   = array(0);
  var $test_info_stack   = array();
  var $_output_stack_id  = -1;
  var $form;
  var $form_depth = array();
  var $current_field_set = array();
  var $content_count = 0;
  var $weight = -10;
  var $test_stack = array();

  function DrupalReporter($character_set = 'ISO-8859-1') {
    $this->SimpleReporter();
    drupal_add_css(drupal_get_path('module', 'simpletest') . '/simpletest.css');
    $this->_character_set = $character_set;
  }

  /**
   * Paints the top of the web page setting the
   * title to the name of the starting test.
   * @param string $test_name      Name class of test.
   * @access public
   **/
  function paintHeader($test_name) {

  }

  /**
   * Paints the end of the test with a summary of
   * the passes and failures.
   * @param string $test_name        Name class of test.
   * @access public
   */
  function paintFooter($test_name) {
    $ok = ($this->getFailCount() + $this->getExceptionCount() == 0);
    $class = $ok ? 'simpletest-pass' : 'simpletest-fail';
    $this->writeContent('<strong>' . $this->getPassCount() . '</strong> passes, <strong>' . $this->getFailCount() . '</strong> fails and <strong>' . $this->getExceptionCount() . '<strong> exceptions.');
  }

  /**
   * Paints the test passes
   * @param string $message    Failure message displayed in
   *                           the context of the other tests.
   * @access public
   **/
  function paintPass($message, $group) {
    parent::paintPass($message);
    if ($group == 'Other') {
      $group = t($group);
    }
    $this->test_stack[] = array(
      'data' => array($message,  "<strong>[$group]</strong>", t('Pass'), theme('image', 'misc/watchdog-ok.png')),
      'class' => 'simpletest-pass',
    );
  }

  /**
   * Paints the test failure with a breadcrumbs
   * trail of the nesting test suites below the
   * top level test.
   * @param string $message    Failure message displayed in
   *                           the context of the other tests.
   * @access public
   */
  function paintFail($message, $group) {
    parent::paintFail($message);
    if ($group == 'Other') {
      $group = t($group);
    }
    $this->test_stack[] = array(
      'data' => array($message, "<strong>[$group]</strong>", t('Fail'), theme('image', 'misc/watchdog-error.png')),
      'class' => 'simpletest-fail',
    );
  }


  /**
   * Paints a PHP error or exception.
   * @param string $message        Message is ignored.
   * @access public
   **/
  function paintError($message) {
    parent::paintError($message);
    $this->test_stack[] = array(
      'data' => array($message, '<strong>[PHP]</strong>', t('Exception'), theme('image', 'misc/watchdog-warning.png')),
      'class' => 'simpletest-exception',
    );
  }

  /**
   * Paints the start of a group test. Will also paint
   * the page header and footer if this is the
   * first test. Will stash the size if the first
   * start.
   * @param string  $test_name   Name of test that is starting.
   * @param integer $size       Number of test cases starting.
   * @access public
   */
  function paintGroupStart($test_name, $size, $extra = '') {
    $this->_progress_stack[] = $this->_progress;
    $this->_progress = 0;
    $this->_exceptions_stack[] = $this->_exceptions;
    $this->_exceptions = 0;
    $this->_fails_stack[] = $this->_fails;
    $this->_fails = 0;
    $this->_passes_stack[] = $this->_passes;
    $this->_passes = 0;
    $this->form_depth[] = $test_name;
    $this->writeToLastField($this->form, array(
      '#type' => 'fieldset',
      '#title' => $test_name,
      '#weight' => $this->weight++,
    ), $this->form_depth);

    if (! isset($this->_size)) {
      $this->_size = $size;
    }

    if (($c = count($this->test_info_stack)) > 0) {
      $info = $this->test_info_stack[$c - 1];
      $this->writeContent('<strong>' . $info['name'] . '</strong>: ' . $info['description'], $this->getParentWeight() );
    }

    $this->_test_stack[] = $test_name;
  }

  function paintCaseStart($test_name) {
    $this->_progress++;
    $this->paintGroupStart($test_name, 1);
  }


  /**
   * Paints the end of a group test. Will paint the page
   * footer if the stack of tests has unwound.
   * @param string $test_name   Name of test that is ending.
   * @param integer $progress   Number of test cases ending.
   * @access public
   */
  function paintGroupEnd($test_name) {
    array_pop($this->_test_stack);
    $ok = ($this->getFailCount() + $this->getExceptionCount() == 0);
    $class = $ok ? "simpletest-pass" : "simpletest-fail";
    $parent_weight = $this->getParentWeight() - 0.5;
    /* Exception for the top groups, no subgrouping for singles */
    if (($this->_output_stack_id == 2) && ($this->_output_stack[$this->_output_stack_id]['size'] == 1)) {
      $this->writeContent(format_plural($this->getTestCaseProgress(), '1 test case complete: ', '@count test cases complete: '), -10);
      $parent_weight = $this->getParentWeight() - 0.5;
      $this->writeContent('<strong>' . $this->getPassCount() . '</strong> passes, <strong>' . $this->getFailCount() . '</strong> fails and <strong>' . $this->getExceptionCount() . '</strong> exceptions.', $parent_weight, $class);
      array_pop($this->form_depth);
    }
    else {
      $collapsed = $ok ? TRUE : FALSE;
      if ($this->getTestCaseProgress()) {
        $this->writeContent(format_plural($this->getTestCaseProgress(), '1 test case complete: ', '@count test cases complete: '), -10);
        $use_grouping = FALSE;
      }
      else {
        $use_grouping = TRUE;
      }
      $write = array('#collapsible' => $use_grouping, '#collapsed' => $collapsed);
      $this->writeToLastField($this->form, $write, $this->form_depth);
    $this->writeContent('<strong>' . $this->getPassCount() . '</strong> passes, <strong>' . $this->getFailCount() . '</strong> fails and <strong>' . $this->getExceptionCount() . '</strong> exceptions.', $parent_weight, $class);
    if (count($this->test_stack) != 0) {
        $this->writeContent(theme('table', array(), $this->test_stack));
        $this->test_stack = array();
      }
    array_pop($this->form_depth);
    }

    $this->_progress   += array_pop($this->_progress_stack);
    $this->_exceptions += array_pop($this->_exceptions_stack);
    $this->_fails      += array_pop($this->_fails_stack);
    $this->_passes     += array_pop($this->_passes_stack);
  }

  function paintCaseEnd($test_name) {
    $this->paintGroupEnd($test_name);
  }

  /**
   * Could be extended to show more headers or whatever?
   **/
  function getOutput() {
    return drupal_get_form('unit_tests', $this);
  }

  /**
   * Recursive function that writes attr to the deepest array
   */
  function writeToLastField(&$form, $attr, $keys) {
    while(count($keys) != 0) {
      $value = array_shift($keys);
      if (isset($form[$value])) {
        if (count($keys) == 0) {
          $form[$value] += $attr;
        }
        else {
          $this->writeToLastField($form[$value], $attr, $keys);
        }
        $keys = array();
      }
      else {
        $form[$value] = $attr;
      }
    }
  }

  /**
   * writes $msg into the deepest fieldset
   * @param $msg content to write
   */
  function writeContent($msg, $weight = NULL, $class = 'simpletest') {
    if (!$weight) {
      $weight = $this->weight++;
    }
    $write['content' . $this->content_count++] = array(
      '#value' => '<div class=' . $class .'>' . $msg . '</div>',
      '#weight' => $weight,
    );
    $this->writeToLastField($this->form, $write, $this->form_depth);
  }

  /**
   * Retrieves weight of the currently deepest fieldset
   */
  function getParentWeight($form = NULL, $keys = NULL ) {
    if (!isset($form)) {
      $form = $this->form;
    }
    if (!isset($keys)) {
      $keys = $this->form_depth;
    }
    if(count($keys) != 0) {
      $value = array_shift($keys);
      return $this->getParentWeight($form[$value], $keys);
    }
    return $form['#weight'];
  }
}

function unit_tests($args, $reporter) {
  return $reporter->form['Drupal unit tests'];
}
?>
