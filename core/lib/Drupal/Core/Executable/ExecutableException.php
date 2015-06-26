<?php
/**
 * @file
 * Contains \Drupal\Core\Executable\ExecutableException.
 */

namespace Drupal\Core\Executable;

use Drupal\Component\Plugin\Exception\ExceptionInterface;

/**
 * Generic executable plugin exception class.
 */
class ExecutableException extends \Exception implements ExceptionInterface {}
