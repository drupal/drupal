<?php

/*
 * @file
 * Definition of Drupal\field\FieldValidationExeption.
 */

namespace Drupal\field;

/**
 * Exception thrown by field_attach_validate() on field validation errors.
 */
class FieldValidationException extends FieldException {

  /**
   * An array of field validation errors.
   *
   * @var array
   */
  public $errors;

 /**
  * Constructor for FieldValidationException.
  *
  * @param $errors
  *   An array of field validation errors, keyed by field name and
  *   delta that contains two keys:
  *   - 'error': A machine-readable error code string, prefixed by
  *     the field module name. A field widget may use this code to decide
  *     how to report the error.
  *   - 'message': A human-readable error message such as to be
  *     passed to form_error() for the appropriate form element.
  */
  function __construct($errors) {
    $this->errors = $errors;
    parent::__construct(t('Field validation errors'));
  }
}
