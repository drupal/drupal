<?php

namespace Drupal\Component\Scaffold\Operations;

use Composer\IO\IOInterface;
use Drupal\Component\Scaffold\ScaffoldFilePath;
use Drupal\Component\Scaffold\ScaffoldOptions;

/**
 * Scaffold operation to add to the beginning and/or end of a scaffold file.
 */
class AppendOp implements OperationInterface, ConjoinableInterface {

  /**
   * Identifies Append operations.
   */
  const ID = 'append';

  /**
   * Path to the source file to prepend, if any.
   *
   * @var \Drupal\Component\Scaffold\ScaffoldFilePath
   */
  protected $prepend;

  /**
   * Path to the source file to append, if any.
   *
   * @var \Drupal\Component\Scaffold\ScaffoldFilePath
   */
  protected $append;

  /**
   * Constructs an AppendOp.
   *
   * @param \Drupal\Component\Scaffold\ScaffoldFilePath $prepend_path
   *   The relative path to the prepend file.
   * @param \Drupal\Component\Scaffold\ScaffoldFilePath $append_path
   *   The relative path to the append file.
   */
  public function __construct(ScaffoldFilePath $prepend_path = NULL, ScaffoldFilePath $append_path = NULL) {
    $this->prepend = $prepend_path;
    $this->append = $append_path;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options) {
    $destination_path = $destination->fullPath();
    if (!file_exists($destination_path)) {
      throw new \RuntimeException($destination->getInterpolator()->interpolate("Cannot append/prepend because no prior package provided a scaffold file at that [dest-rel-path]."));
    }
    $interpolator = $destination->getInterpolator();

    // Fetch the prepend contents, if provided.
    $prepend_contents = '';
    if (!empty($this->prepend)) {
      $this->prepend->addInterpolationData($interpolator, 'prepend');
      $prepend_contents = file_get_contents($this->prepend->fullPath()) . "\n";
      $io->write($interpolator->interpolate("  - Prepend to <info>[dest-rel-path]</info> from <info>[prepend-rel-path]</info>"));
    }
    // Fetch the append contents, if provided.
    $append_contents = '';
    if (!empty($this->append)) {
      $this->append->addInterpolationData($interpolator, 'append');
      $append_contents = "\n" . file_get_contents($this->append->fullPath());
      $io->write($interpolator->interpolate("  - Append to <info>[dest-rel-path]</info> from <info>[append-rel-path]</info>"));
    }
    if (!empty(trim($prepend_contents)) || !empty(trim($append_contents))) {
      // None of our asset files are very large, so we will load each one into
      // memory for processing.
      $original_contents = file_get_contents($destination_path);
      // Write the appended and prepended contents back to the file.
      $altered_contents = $prepend_contents . $original_contents . $append_contents;
      file_put_contents($destination_path, $altered_contents);
    }
    else {
      $io->write($interpolator->interpolate("  - Keep <info>[dest-rel-path]</info> unchanged: no content to prepend / append was provided."));
    }
    return new ScaffoldResult($destination, TRUE);
  }

}
