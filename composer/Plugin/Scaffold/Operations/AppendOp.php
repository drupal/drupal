<?php

namespace Drupal\Composer\Plugin\Scaffold\Operations;

use Composer\IO\IOInterface;
use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;
use Drupal\Composer\Plugin\Scaffold\ScaffoldOptions;

/**
 * Scaffold operation to add to the beginning and/or end of a scaffold file.
 *
 * @internal
 */
class AppendOp extends AbstractOperation {

  /**
   * Identifies Append operations.
   */
  const ID = 'append';

  /**
   * Path to the source file to prepend, if any.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath
   */
  protected $prepend;

  /**
   * Path to the source file to append, if any.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath
   */
  protected $append;

  /**
   * Path to the default data to use when appending to an empty file.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath
   */
  protected $default;

  /**
   * An indicator of whether the file we are appending to is managed or not.
   *
   * @var bool
   */
  protected $managed;

  /**
   * An indicator of whether we are allowed to append to a non-scaffolded file.
   *
   * @var bool
   */
  protected $forceAppend;

  /**
   * The contents from the file that we are prepending / appending to.
   *
   * @var string
   */
  protected $originalContents;

  /**
   * Constructs an AppendOp.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath|null $prepend_path
   *   (optional) The relative path to the prepend file.
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath|null $append_path
   *   (optional) The relative path to the append file.
   * @param bool $force_append
   *   (optional) TRUE if is okay to append to a file that was not scaffolded.
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath|null $default_path
   *   (optional) The relative path to the default data.
   */
  public function __construct(
    ?ScaffoldFilePath $prepend_path = NULL,
    ?ScaffoldFilePath $append_path = NULL,
    $force_append = FALSE,
    ?ScaffoldFilePath $default_path = NULL,
  ) {
    $this->forceAppend = $force_append;
    $this->prepend = $prepend_path;
    $this->append = $append_path;
    $this->default = $default_path;
    $this->managed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateContents() {
    // Fetch the prepend contents, if provided.
    $prepend_contents = '';
    if (!empty($this->prepend)) {
      $prepend_contents = file_get_contents($this->prepend->fullPath()) . "\n";
    }
    // Fetch the append contents, if provided.
    $append_contents = '';
    if (!empty($this->append)) {
      $append_contents = "\n" . file_get_contents($this->append->fullPath());
    }

    // Get the original contents, or the default data if the original is empty.
    $original_contents = $this->originalContents;
    if (empty($original_contents) && !empty($this->default)) {
      $original_contents = file_get_contents($this->default->fullPath());
    }

    // Attach it all together.
    return $prepend_contents . $original_contents . $append_contents;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options) {
    $destination_path = $destination->fullPath();
    $interpolator = $destination->getInterpolator();

    // Be extra-noisy of creating a new file or appending to a non-scaffold
    // file. Note that if the file already has the append contents, then the
    // OperationFactory will make a SkipOp instead, and we will not get here.
    if (!$this->managed) {
      $message = '  - <info>NOTICE</info> Modifying existing file at <info>[dest-rel-path]</info>.';
      if (!file_exists($destination_path)) {
        $message = '  - <info>NOTICE</info> Creating a new file at <info>[dest-rel-path]</info>.';
      }
      $message .= ' Examine the contents and ensure that it came out correctly.';
      $io->write($interpolator->interpolate($message));
    }

    // Notify that we are prepending, if there is prepend data.
    if (!empty($this->prepend)) {
      $this->prepend->addInterpolationData($interpolator, 'prepend');
      $io->write($interpolator->interpolate("  - Prepend to <info>[dest-rel-path]</info> from <info>[prepend-rel-path]</info>"));
    }
    // Notify that we are appending, if there is append data.
    if (!empty($this->append)) {
      $this->append->addInterpolationData($interpolator, 'append');
      $io->write($interpolator->interpolate("  - Append to <info>[dest-rel-path]</info> from <info>[append-rel-path]</info>"));
    }

    // Write the resulting data
    file_put_contents($destination_path, $this->contents());

    // Return a ScaffoldResult with knowledge of whether this file is managed.
    return new ScaffoldResult($destination, $this->managed);
  }

  /**
   * {@inheritdoc}
   */
  public function scaffoldOverExistingTarget(OperationInterface $existing_target) {
    $this->originalContents = $existing_target->contents();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function scaffoldAtNewLocation(ScaffoldFilePath $destination) {
    // If there is no existing scaffold file at the target location, then any
    // append we do will be to an unmanaged file.
    $this->managed = FALSE;

    // Default: do not allow an append over a file that was not scaffolded.
    if (!$this->forceAppend) {
      $message = "  - Skip <info>[dest-rel-path]</info>: cannot append to a path that was not scaffolded unless 'force-append' property is set.";
      return new SkipOp($message);
    }

    // If the target file does not exist, then we will allow the append to
    // happen if we have default data to provide for it.
    if (!file_exists($destination->fullPath())) {
      if (!empty($this->default)) {
        return $this;
      }
      $message = "  - Skip <info>[dest-rel-path]</info>: no file exists at the target path, and no default data provided.";
      return new SkipOp($message);
    }

    // If the target file DOES exist, and it already contains the append/prepend
    // data, then we will skip the operation.
    $existingData = file_get_contents($destination->fullPath());
    if ($this->existingFileHasData($existingData, $this->append) || $this->existingFileHasData($existingData, $this->prepend)) {
      $message = "  - Skip <info>[dest-rel-path]</info>: the file already has the append/prepend data.";
      return new SkipOp($message);
    }

    // Cache the original data to use during append.
    $this->originalContents = $existingData;

    return $this;
  }

  /**
   * Check to see if the append/prepend data has already been applied.
   *
   * @param string $contents
   *   The contents of the target file.
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $data_path
   *   The path to the data to append or prepend.
   *
   * @return bool
   *   'TRUE' if the append/prepend data already exists in contents.
   */
  protected function existingFileHasData($contents, $data_path) {
    if (empty($data_path)) {
      return FALSE;
    }
    $data = file_get_contents($data_path->fullPath());

    return str_contains($contents, $data);
  }

}
