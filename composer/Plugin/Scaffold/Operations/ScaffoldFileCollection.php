<?php

namespace Drupal\Composer\Plugin\Scaffold\Operations;

use Composer\IO\IOInterface;
use Drupal\Composer\Plugin\Scaffold\Interpolator;
use Drupal\Composer\Plugin\Scaffold\ScaffoldFileInfo;
use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;
use Drupal\Composer\Plugin\Scaffold\ScaffoldOptions;

/**
 * Collection of scaffold files.
 *
 * @internal
 */
class ScaffoldFileCollection implements \IteratorAggregate {

  /**
   * Nested list of all scaffold files.
   *
   * The top level array maps from the package name to the collection of
   * scaffold files provided by that package. Each collection of scaffold files
   * is keyed by destination path.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\ScaffoldFileInfo[][]
   */
  protected $scaffoldFilesByProject = [];

  /**
   * ScaffoldFileCollection constructor.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\Operations\OperationInterface[][] $file_mappings
   *   A multidimensional array of file mappings.
   * @param \Drupal\Composer\Plugin\Scaffold\Interpolator $location_replacements
   *   An object with the location mappings (e.g. [web-root]).
   */
  public function __construct(array $file_mappings, Interpolator $location_replacements) {
    // Collection of all destination paths to be scaffolded. Used to determine
    // when two projects scaffold the same file and we have to either replace or
    // combine them together.
    // @see OperationInterface::scaffoldOverExistingTarget().
    $scaffoldFiles = [];

    // Build the list of ScaffoldFileInfo objects by project.
    foreach ($file_mappings as $package_name => $package_file_mappings) {
      foreach ($package_file_mappings as $destination_rel_path => $op) {
        $destination = ScaffoldFilePath::destinationPath($package_name, $destination_rel_path, $location_replacements);

        // If there was already a scaffolding operation happening at this path,
        // allow the new operation to decide how to handle the override.
        // Usually, the new operation will replace whatever was there before.
        if (isset($scaffoldFiles[$destination_rel_path])) {
          $previous_scaffold_file = $scaffoldFiles[$destination_rel_path];
          $op = $op->scaffoldOverExistingTarget($previous_scaffold_file->op());

          // Remove the previous op so we only touch the destination once.
          $message = "  - Skip <info>[dest-rel-path]</info>: overridden in <comment>{$package_name}</comment>";
          $this->scaffoldFilesByProject[$previous_scaffold_file->packageName()][$destination_rel_path] = new ScaffoldFileInfo($destination, new SkipOp($message));
        }
        // If there is NOT already a scaffolding operation happening at this
        // path, notify the scaffold operation of this fact.
        else {
          $op = $op->scaffoldAtNewLocation($destination);
        }

        // Combine the scaffold operation with the destination and record it.
        $scaffold_file = new ScaffoldFileInfo($destination, $op);
        $scaffoldFiles[$destination_rel_path] = $scaffold_file;
        $this->scaffoldFilesByProject[$package_name][$destination_rel_path] = $scaffold_file;
      }
    }
  }

  /**
   * Removes any item that has a path matching any path in the provided list.
   *
   * Matching is done via destination path.
   *
   * @param string[] $files_to_filter
   *   List of destination paths
   */
  public function filterFiles(array $files_to_filter) {
    foreach ($this->scaffoldFilesByProject as $project_name => $scaffold_files) {
      foreach ($scaffold_files as $destination_rel_path => $scaffold_file) {
        if (in_array($destination_rel_path, $files_to_filter, TRUE)) {
          unset($scaffold_files[$destination_rel_path]);
        }
      }
      $this->scaffoldFilesByProject[$project_name] = $scaffold_files;
      if (!$this->checkListHasItemWithContent($scaffold_files)) {
        unset($this->scaffoldFilesByProject[$project_name]);
      }
    }
  }

  /**
   * Scans through a list of scaffold files and determines if any has contents.
   *
   * @param Drupal\Composer\Plugin\Scaffold\ScaffoldFileInfo[] $scaffold_files
   *   List of scaffold files, path: ScaffoldFileInfo
   *
   * @return bool
   *   TRUE if at least one item in the list has content
   */
  protected function checkListHasItemWithContent(array $scaffold_files) {
    foreach ($scaffold_files as $destination_rel_path => $scaffold_file) {
      $contents = $scaffold_file->op()->contents();
      if (!empty($contents)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->scaffoldFilesByProject);
  }

  /**
   * Processes the files in our collection.
   *
   * @param \Composer\IO\IOInterface $io
   *   The Composer IO object.
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldOptions $scaffold_options
   *   The scaffold options.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldResult[]
   *   The results array.
   */
  public function processScaffoldFiles(IOInterface $io, ScaffoldOptions $scaffold_options) {
    $results = [];
    foreach ($this as $project_name => $scaffold_files) {
      $io->write("Scaffolding files for <comment>{$project_name}</comment>:");
      foreach ($scaffold_files as $scaffold_file) {
        $results[$scaffold_file->destination()->relativePath()] = $scaffold_file->process($io, $scaffold_options);
      }
    }
    return $results;
  }

  /**
   * Processes the iterator created by ScaffoldFileCollection::create().
   *
   * @param \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldFileCollection $collection
   *   The iterator to process.
   * @param \Composer\IO\IOInterface $io
   *   The Composer IO object.
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldOptions $scaffold_options
   *   The scaffold options.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldResult[]
   *   The results array.
   *
   * @deprecated. Called when upgrading from the Core Composer Scaffold plugin
   *   version 8.8.x due to a bug in the plugin and handler classes. Do not use
   *   in 8.9.x or 9.x, and remove in Drupal 10.x.
   */
  public static function process(ScaffoldFileCollection $collection, IOInterface $io, ScaffoldOptions $scaffold_options) {
    $results = [];
    foreach ($collection as $project_name => $scaffold_files) {
      $io->write("Scaffolding files for <comment>{$project_name}</comment>:");
      foreach ($scaffold_files as $scaffold_file) {
        $results[$scaffold_file->destination()->relativePath()] = $scaffold_file->process($io, $scaffold_options);
      }
    }
    return $results;
  }

  /**
   * Returns the list of files that have not changed since they were scaffolded.
   *
   * Note that there are two reasons a file may have changed:
   *   - The user modified it after it was scaffolded.
   *   - The package the file came to was updated, and the file is different in
   *     the new version.
   *
   * With the current scaffold code, we cannot tell the difference between the
   * two. @see https://www.drupal.org/project/drupal/issues/3092563
   *
   * @return string[]
   *   List of relative paths to unchanged files on disk.
   */
  public function checkUnchanged() {
    $results = [];
    foreach ($this as $project_name => $scaffold_files) {
      foreach ($scaffold_files as $scaffold_file) {
        if (!$scaffold_file->hasChanged()) {
          $results[] = $scaffold_file->destination()->relativePath();
        }
      }
    }
    return $results;
  }

}
