<?php

/*
 * @file
 * Definition of Drupal\field\FieldExeption.
 */

namespace Drupal\field;

use RuntimeException;

/**
 * Base class for all exceptions thrown by Field API functions.
 *
 * This class has no functionality of its own other than allowing all
 * Field API exceptions to be caught by a single catch block.
 */
class FieldException extends RuntimeException {}
