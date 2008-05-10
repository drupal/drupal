<?php
// $Id$

/**
 * Standard unit test class for day to day testing
 * of PHP code XP style. Adds some useful standard
 * assertions.
 */
class UnitTestCase extends SimpleTestCase {

  /**
   *    Creates an empty test case. Should be subclassed
   *    with test methods for a functional test case.
   *    @param string $label     Name of test case. Will use
   *                             the class name if none specified.
   *    @access public
   */
  function UnitTestCase($label = false) {
    if (!$label) {
      $label = get_class($this);
    }
    $this->SimpleTestCase($label);
  }

  /**
   *    Called from within the test methods to register
   *    passes and failures.
   *    @param boolean $result    Pass on true.
   *    @param string $message    Message to display describing
   *                              the test state.
   *    @return boolean           True on pass
   *    @access public
   */
  function assertTrue($result, $message = FALSE, $group = 'Other') {
    return $this->assert(new TrueExpectation(), $result, $message, $group);
  }

  /**
   *    Will be true on false and vice versa. False
   *    is the PHP definition of false, so that null,
   *    empty strings, zero and an empty array all count
   *    as false.
   *    @param boolean $result    Pass on false.
   *    @param string $message    Message to display.
   *    @return boolean           True on pass
   *    @access public
   */
  function assertFalse($result, $message = '%s', $group = 'Other') {
    $dumper = &new SimpleDumper();
    $message = sprintf($message, 'Expected false, got [' . $dumper->describeValue($result) . ']');
    return $this->assertTrue(!$result, $message, $group);
  }

  /**
   *    Will be true if the value is null.
   *    @param null $value       Supposedly null value.
   *    @param string $message   Message to display.
   *    @return boolean                        True on pass
   *    @access public
   */
  function assertNull($value, $message = '%s', $group = 'Other') {
    $dumper = &new SimpleDumper();
    $message = sprintf($message, '[' . $dumper->describeValue($value) . '] should be null');
    return $this->assertTrue(!isset($value), $message, $group);
  }

  /**
   *    Will be true if the value is set.
   *    @param mixed $value           Supposedly set value.
   *    @param string $message        Message to display.
   *    @return boolean               True on pass.
   *    @access public
   */
  function assertNotNull($value, $message = '%s', $group = 'Other') {
    $dumper = &new SimpleDumper();
    $message = sprintf($message, '[' . $dumper->describeValue($value) . '] should not be null');
    return $this->assertTrue(isset($value), $message, $group);
  }

  /**
   *    Will trigger a pass if the two parameters have
   *    the same value only. Otherwise a fail.
   *    @param mixed $first          Value to compare.
   *    @param mixed $second         Value to compare.
   *    @param string $message       Message to display.
   *    @return boolean              True on pass
   *    @access public
   */
  function assertEqual($first, $second, $message = '%s', $group = 'Other') {
    $dumper = &new SimpleDumper();
    $message = sprintf($message, 'Expected ' . $dumper->describeValue($first) . ', got [' . $dumper->describeValue($second) . ']');
    $this->assertTrue($first == $second, $message, $group);
  }

  /**
   *    Will trigger a pass if the two parameters have
   *    a different value. Otherwise a fail.
   *    @param mixed $first           Value to compare.
   *    @param mixed $second          Value to compare.
   *    @param string $message        Message to display.
   *    @return boolean               True on pass
   *    @access public
   */
  function assertNotEqual($first, $second, $message = '%s', $group = 'Other') {
    $dumper = &new SimpleDumper();
    $message = sprintf($message, 'Expected ' . $dumper->describeValue($first) . ', not equal to ' . $dumper->describeValue($second));
    $this->assertTrue($first != $second, $message, $group);
  }

  /**
   *    Will trigger a pass if the two parameters have
   *    the same value and same type. Otherwise a fail.
   *    @param mixed $first           Value to compare.
   *    @param mixed $second          Value to compare.
   *    @param string $message        Message to display.
   *    @return boolean               True on pass
   *    @access public
   */
  function assertIdentical($first, $second, $message = '%s', $group = 'Other') {
    $dumper = &new SimpleDumper();
    $message = sprintf($message, 'Expected ' . $dumper->describeValue($first) . ', got [' . $dumper->describeValue($second) . ']');
    $this->assertTrue($first === $second, $message, $group);
  }

  /**
   *    Will trigger a pass if the two parameters have
   *    the different value or different type.
   *    @param mixed $first           Value to compare.
   *    @param mixed $second          Value to compare.
   *    @param string $message        Message to display.
   *    @return boolean               True on pass
   *    @access public
   */
  function assertNotIdentical($first, $second, $message = '%s', $group = 'Other') {
    $dumper = &new SimpleDumper();
    $message = sprintf($message, 'Expected ' . $dumper->describeValue($first) . ', not identical to ' . $dumper->describeValue($second));
    $this->assertTrue($first !== $second, $message, $group);
  }

  /**
   *    Will trigger a pass if the Perl regex pattern
   *    is found in the subject. Fail otherwise.
   *    @param string $pattern    Perl regex to look for including
   *                              the regex delimiters.
   *    @param string $subject    String to search in.
   *    @param string $message    Message to display.
   *    @return boolean           True on pass
   *    @access public
   */
  function assertPattern($pattern, $subject, $message = '%s', $group = 'Other') {
    $dumper  = &new SimpleDumper();
    $replace = 'Pattern ' . $pattern . ' detected in [' . $dumper->describeValue($subject) . ']';
    $found   = preg_match($pattern, $subject, $matches);
    if ($found) {
      $position = strpos($subject, $matches[0]);
      $replace .= ' in region [' . $dumper->clipString($subject, 100, $position) . ']';
    }
    $message = sprintf($message, $replace);
    $this->assertTrue($found, $message, $group);
  }

  /**
   *    Will trigger a pass if the perl regex pattern
   *    is not present in subject. Fail if found.
   *    @param string $pattern    Perl regex to look for including
   *                              the regex delimiters.
   *    @param string $subject    String to search in.
   *    @param string $message    Message to display.
   *    @return boolean           True on pass
   *    @access public
   */
  function assertNoPattern($pattern, $subject, $message = '%s', $group = 'Other') {
    $dumper  = &new SimpleDumper();
    $found   = preg_match($pattern, $subject);
    $message = sprintf($message, 'Pattern ' . $pattern . ' not detected in [' . $dumper->describeValue($subject) . ']');
    $this->assertFalse($found, $message, $group = 'Other');
  }
}