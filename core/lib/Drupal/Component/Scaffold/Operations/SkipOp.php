<?php

namespace Drupal\Component\Scaffold\Operations;

use Composer\IO\IOInterface;
use Drupal\Component\Scaffold\ScaffoldFilePath;
use Drupal\Component\Scaffold\ScaffoldOptions;

/**
 * Scaffold operation to skip a scaffold file (do nothing).
 */
class SkipOp implements OperationInterface {

  /**
   * Identifies Skip operations.
   */
  const ID = 'skip';

  /**
   * The message to output while processing.
   *
   * @var string
   */
  protected $message;

  /**
   * SkipOp constructor.
   *
   * @param string $message
   *   (optional) A custom message to output while skipping.
   */
  public function __construct($message = "  - Skip <info>[dest-rel-path]</info>: disabled") {
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options) {
    $interpolator = $destination->getInterpolator();
    $io->write($interpolator->interpolate($this->message));
    return new ScaffoldResult($destination, FALSE);
  }

}
