<?php

namespace Drupal\Component\Scaffold\Operations;

use Composer\IO\IOInterface;
use Drupal\Component\Scaffold\Interpolator;
use Drupal\Component\Scaffold\ScaffoldFileInfo;
use Drupal\Component\Scaffold\ScaffoldFilePath;
use Drupal\Component\Scaffold\ScaffoldOptions;

/**
 * Collection of scaffold files.
 */
class ScaffoldFileCollection implements \IteratorAggregate {

  /**
   * Nested list of all scaffold files.
   *
   * The top level array maps from the package name to the collection of
   * scaffold files provided by that package. Each collection of scaffold files
   * is keyed by destination path.
   *
   * @var \Drupal\Component\Scaffold\ScaffoldFileInfo[][]
   */
  protected $scaffoldFilesByProject = [];

  /**
   * ScaffoldFileCollection constructor.
   *
   * @param array $file_mappings
   *   A multidimensional array of file mappings.
   * @param \Drupal\Component\Scaffold\Interpolator $location_replacements
   *   An object with the location mappings (e.g. [web-root]).
   */
  public function __construct(array $file_mappings, Interpolator $location_replacements) {
    // Collection of all destination paths to be scaffolded. Used to determine
    // when two project scaffold the same file and we have to skip or use a
    // ConjunctionOp.
    $scaffoldFiles = [];

    // Build the list of ScaffoldFileInfo objects by project.
    foreach ($file_mappings as $package_name => $package_file_mappings) {
      foreach ($package_file_mappings as $destination_rel_path => $op) {
        $destination = ScaffoldFilePath::destinationPath($package_name, $destination_rel_path, $location_replacements);
        // If there was already a scaffolding operation happening at this path,
        // and the new operation is Conjoinable, then use a ConjunctionOp to
        // join together both operations. This will cause both operations to
        // run, one after the other. At the moment, only AppendOp is
        // conjoinable; all other operations simply replace anything at the same
        // path.
        if (isset($scaffoldFiles[$destination_rel_path])) {
          $previous_scaffold_file = $scaffoldFiles[$destination_rel_path];
          if ($op instanceof ConjoinableInterface) {
            $op = new ConjunctionOp($previous_scaffold_file->op(), $op);
          }
          // Remove the previous op so we only touch the destination once.
          $message = "  - Skip <info>[dest-rel-path]</info>: overridden in <comment>{$package_name}</comment>";
          $this->scaffoldFilesByProject[$previous_scaffold_file->packageName()][$destination_rel_path] = new ScaffoldFileInfo($destination, new SkipOp($message));
        }
        $scaffold_file = new ScaffoldFileInfo($destination, $op);
        $scaffoldFiles[$destination_rel_path] = $scaffold_file;
        $this->scaffoldFilesByProject[$package_name][$destination_rel_path] = $scaffold_file;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \RecursiveArrayIterator($this->scaffoldFilesByProject, \RecursiveArrayIterator::CHILD_ARRAYS_ONLY);
  }

  /**
   * Processes the iterator created by ScaffoldFileCollection::create().
   *
   * @param \Drupal\Component\Scaffold\Operations\ScaffoldFileCollection $collection
   *   The iterator to process.
   * @param \Composer\IO\IOInterface $io
   *   The Composer IO object.
   * @param \Drupal\Component\Scaffold\ScaffoldOptions $scaffold_options
   *   The scaffold options.
   *
   * @return \Drupal\Component\Scaffold\Operations\ScaffoldResult[]
   *   The results array.
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

}
