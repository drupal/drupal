<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit7;

/**
 * Makes Drupal's test API forward compatible with multiple versions of PHPUnit.
 */
trait StubTestSuiteBaseTrait {

  /**
   * {@inheritdoc}
   */
  public function addTestFiles($filenames): void {
    // We stub addTestFiles() because the parent implementation can't deal with
    // vfsStream-based filesystems due to an error in
    // stream_resolve_include_path(). See
    // https://github.com/mikey179/vfsStream/issues/5 Here we just store the
    // test file being added in $this->testFiles.
    $this->testFiles = array_merge($this->testFiles, $filenames);
  }

}
