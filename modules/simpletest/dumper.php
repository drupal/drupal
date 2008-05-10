<?php
// $Id$

/**
 * Displays variables as text and does diffs.
 */
class SimpleDumper {
  /**
   * Renders a variable in a shorter form than print_r().
   *
   * @param mixed $value      Variable to render as a string.
   *
   * @return string           Human readable string form.
   * @access public
   */
  function describeValue($value) {
    $type = $this->getType($value);
    switch ($type) {
      case "Null":
        return "NULL";

      case "Bool":
        return "Boolean: ". ($value ? "true" : "false");

      case "Array":
        return "Array: ". count($value) ." items";

      case "Object":
        return "Object: of ". get_class($value);

      case "String":
        return "String: ". $this->clipString($value, 200);

      default:
        return "$type: $value";
    }
  }

  /**
   *    Gets the string representation of a type.
   *    @param mixed $value    Variable to check against.
   *    @return string         Type.
   *    @access public
   */
  function getType($value) {
    if (!isset($value)) {
      return "Null";
    }
    $functions = array('bool', 'string', 'integer', 'float', 'array', 'resource', 'object');
    foreach ($functions as $function) {
      $function_name = 'is_' . $function;
      if ($function_name($value)) {
        return ucfirst($function);
      }
    }
    return "Unknown";
  }

 /**
  *    Clips a string to a maximum length.
  *    @param string $value         String to truncate.
  *    @param integer $size         Minimum string size to show.
  *    @param integer $position     Centre of string section.
  *    @return string               Shortened version.
  *    @access public
  */
  function clipString($value, $size, $position = 0) {
    $length = strlen($value);
    if ($length <= $size) {
      return $value;
    }
    $position = min($position, $length);
    $start = ($size / 2 > $position ? 0 : $position - $size / 2);
    if ($start + $size > $length) {
      $start = $length - $size;
    }
    $value = substr($value, $start, $size);
    return ($start > 0 ? "..." : "") . $value . ($start + $size < $length ? "..." : "");
  }
}
