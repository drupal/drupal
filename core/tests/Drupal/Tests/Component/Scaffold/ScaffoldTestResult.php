<?php

namespace Drupal\Tests\Component\Scaffold;

/**
 * Holds result of a scaffold test.
 */
class ScaffoldTestResult {

  protected $docroot;
  protected $scaffoldOutput;

  /**
   * Holds the location of the scaffold fixture and the stdout from the test.
   *
   * @param string $docroot
   * @param string $scaffoldOutput
   */
  public function __construct($docroot, $scaffoldOutput) {
    $this->docroot = $docroot;
    $this->scaffoldOutput = $scaffoldOutput;
  }

  /**
   * Returns the location of the docroot from the scaffold test.
   *
   * @return string
   */
  public function docroot() {
    return $this->docroot;
  }

  /**
   * Returns the standard output from the scaffold test.
   *
   * @return string
   */
  public function scaffoldOutput() {
    return $this->scaffoldOutput;
  }

}
