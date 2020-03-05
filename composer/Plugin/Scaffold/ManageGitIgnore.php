<?php

namespace Drupal\Composer\Plugin\Scaffold;

use Composer\IO\IOInterface;

/**
 * Manage the .gitignore file.
 *
 * @internal
 */
class ManageGitIgnore {

  /**
   * Composer's I/O service.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * The directory where the project is located.
   *
   * @var string
   */
  protected $dir;

  /**
   * ManageGitIgnore constructor.
   *
   * @param string $dir
   *   The directory where the project is located.
   */
  public function __construct(IOInterface $io, $dir) {
    $this->io = $io;
    $this->dir = $dir;
  }

  /**
   * Manages gitignore files.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldResult[] $files
   *   A list of scaffold results, each of which holds a path and whether
   *   or not that file is managed.
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldOptions $options
   *   Configuration options from the composer.json extras section.
   */
  public function manageIgnored(array $files, ScaffoldOptions $options) {
    if (!$this->managementOfGitIgnoreEnabled($options)) {
      return;
    }

    // Accumulate entries to add to .gitignore, sorted into buckets based on the
    // location of the .gitignore file the entry should be added to.
    $add_to_git_ignore = [];
    foreach ($files as $scaffoldResult) {
      $path = $scaffoldResult->destination()->fullPath();
      $is_ignored = Git::checkIgnore($this->io, $path, $this->dir);
      if (!$is_ignored) {
        $is_tracked = Git::checkTracked($this->io, $path, $this->dir);
        if (!$is_tracked && $scaffoldResult->isManaged()) {
          $dir = realpath(dirname($path));
          $name = basename($path);
          $add_to_git_ignore[$dir][] = '/' . $name;
        }
      }
    }
    // Write out the .gitignore files one at a time.
    foreach ($add_to_git_ignore as $dir => $entries) {
      $this->addToGitIgnore($dir, $entries);
    }
  }

  /**
   * Determines whether we should manage gitignore files.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldOptions $options
   *   Configuration options from the composer.json extras section.
   *
   * @return bool
   *   Whether or not gitignore files should be managed.
   */
  protected function managementOfGitIgnoreEnabled(ScaffoldOptions $options) {
    // If the composer.json stipulates whether gitignore is managed or not, then
    // follow its recommendation.
    if ($options->hasGitIgnore()) {
      return $options->gitIgnore();
    }

    // Do not manage .gitignore if there is no repository here.
    if (!Git::isRepository($this->io, $this->dir)) {
      return FALSE;
    }

    // If the composer.json did not specify whether or not .gitignore files
    // should be managed, then manage them if the vendor directory is ignored.
    return Git::checkIgnore($this->io, 'vendor', $this->dir);
  }

  /**
   * Adds a set of entries to the specified .gitignore file.
   *
   * @param string $dir
   *   Path to directory where gitignore should be written.
   * @param string[] $entries
   *   Entries to write to .gitignore file.
   */
  protected function addToGitIgnore($dir, array $entries) {
    sort($entries);
    $git_ignore_path = $dir . '/.gitignore';
    $contents = '';

    // Appending to existing .gitignore files.
    if (file_exists($git_ignore_path)) {
      $contents = file_get_contents($git_ignore_path);
      if (!empty($contents) && substr($contents, -1) != "\n") {
        $contents .= "\n";
      }
    }

    $contents .= implode("\n", $entries);
    file_put_contents($git_ignore_path, $contents);
  }

}
