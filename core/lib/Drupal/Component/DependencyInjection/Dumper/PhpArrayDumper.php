<?php

/**
 * @file
 * Contains \Drupal\Component\DependencyInjection\Dumper\PhpArrayDumper.
 */

namespace Drupal\Component\DependencyInjection\Dumper;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * PhpArrayDumper dumps a service container as a PHP array.
 *
 * The format of this dumper is a human-readable serialized PHP array, which is
 * very similar to the YAML based format, but based on PHP arrays instead of
 * YAML strings.
 *
 * It is human-readable, for a machine-optimized version based on this one see
 * \Drupal\Component\DependencyInjection\Dumper\OptimizedPhpArrayDumper.
 *
 * @see \Drupal\Component\DependencyInjection\PhpArrayContainer
 */
class PhpArrayDumper extends OptimizedPhpArrayDumper {

  /**
   * {@inheritdoc}
   */
  public function getArray() {
    $this->serialize = FALSE;
    return parent::getArray();
  }

  /**
   * {@inheritdoc}
   */
  protected function dumpCollection($collection, &$resolve = FALSE) {
    $code = array();

    foreach ($collection as $key => $value) {
      if (is_array($value)) {
        $code[$key] = $this->dumpCollection($value);
      }
      else {
        $code[$key] = $this->dumpValue($value);
      }
    }

    return $code;
  }

  /**
   * {@inheritdoc}
   */
  protected function getServiceCall($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
    if ($invalid_behavior !== ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
      return '@?' . $id;
    }

    return '@' . $id;
  }

  /**
   * {@inheritdoc}
   */
  protected function getParameterCall($name) {
    return '%' . $name . '%';
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsMachineFormat() {
    return FALSE;
  }

}
