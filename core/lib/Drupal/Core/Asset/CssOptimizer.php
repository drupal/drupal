<?php

/**
 * Contains \Drupal\Core\Asset\CssOptimizer.
 */

namespace Drupal\Core\Asset;

use Drupal\Core\Asset\AssetOptimizerInterface;

/**
 * Optimizes a CSS asset.
 */
class CssOptimizer implements AssetOptimizerInterface {

  /**
   * The base path used by rewriteFileURI().
   *
   * @var string
   */
  public $rewriteFileURIBasePath;

  /**
   * {@inheritdoc}
   */
  public function optimize(array $css_asset) {
    if (!in_array($css_asset['type'], array('file', 'inline'))) {
      throw new \Exception('Only file or inline CSS assets can be optimized.');
    }
    if ($css_asset['type'] === 'file' && !$css_asset['preprocess']) {
      throw new \Exception('Only file CSS assets with preprocessing enabled can be optimized.');
    }

    if ($css_asset['type'] === 'file') {
      return $this->processFile($css_asset);
    }
    else {
      return $this->processCss($css_asset['data'], $css_asset['preprocess']);
    }
  }

  /**
   * Build aggregate CSS file.
   */
  protected function processFile($css_asset) {
    $contents = $this->loadFile($css_asset['data'], TRUE);

    // Get the parent directory of this file, relative to the Drupal root.
    $css_base_path = substr($css_asset['data'], 0, strrpos($css_asset['data'], '/'));
    // Store base path.
    $this->rewriteFileURIBasePath = $css_base_path . '/';

    // Anchor all paths in the CSS with its base URL, ignoring external and absolute paths.
    return preg_replace_callback('/url\(\s*[\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\s*\)/i', array($this, 'rewriteFileURI'), $contents);
  }

  /**
   * Loads the stylesheet and resolves all @import commands.
   *
   * Loads a stylesheet and replaces @import commands with the contents of the
   * imported file. Use this instead of file_get_contents when processing
   * stylesheets.
   *
   * The returned contents are compressed removing white space and comments only
   * when CSS aggregation is enabled. This optimization will not apply for
   * color.module enabled themes with CSS aggregation turned off.
   *
   * Note: the only reason this method is public is so color.module can call it;
   * it is not on the AssetOptimizerInterface, so future refactorings can make
   * it protected.
   *
   * @param $file
   *   Name of the stylesheet to be processed.
   * @param $optimize
   *   Defines if CSS contents should be compressed or not.
   * @param $reset_basepath
   *   Used internally to facilitate recursive resolution of @import commands.
   *
   * @return
   *   Contents of the stylesheet, including any resolved @import commands.
   */
  public function loadFile($file, $optimize = NULL, $reset_basepath = TRUE) {
    // These statics are not cache variables, so we don't use drupal_static().
    static $_optimize, $basepath;
    if ($reset_basepath) {
      $basepath = '';
    }
    // Store the value of $optimize for preg_replace_callback with nested
    // @import loops.
    if (isset($optimize)) {
      $_optimize = $optimize;
    }

    // Stylesheets are relative one to each other. Start by adding a base path
    // prefix provided by the parent stylesheet (if necessary).
    if ($basepath && !file_uri_scheme($file)) {
      $file = $basepath . '/' . $file;
    }
    // Store the parent base path to restore it later.
    $parent_base_path = $basepath;
    // Set the current base path to process possible child imports.
    $basepath = dirname($file);

    // Load the CSS stylesheet. We suppress errors because themes may specify
    // stylesheets in their .info.yml file that don't exist in the theme's path,
    // but are merely there to disable certain module CSS files.
    $content = '';
    if ($contents = @file_get_contents($file)) {
      // Return the processed stylesheet.
      $content = $this->processCss($contents, $_optimize);
    }

    // Restore the parent base path as the file and its children are processed.
    $basepath = $parent_base_path;
    return $content;
  }

  /**
   * Loads stylesheets recursively and returns contents with corrected paths.
   *
   * This function is used for recursive loading of stylesheets and
   * returns the stylesheet content with all url() paths corrected.
   *
   * @param array $matches
   *   An array of matches by a preg_replace_callback() call that scans for
   *   @import-ed CSS files, except for external CSS files.
   * @return
   *   The contents of the CSS file at $matches[1], with corrected paths.
   *
   * @see \Drupal\Core\Asset\AssetOptimizerInterface::loadFile()
   */
  protected function loadNestedFile($matches) {
    $filename = $matches[1];
    // Load the imported stylesheet and replace @import commands in there as
    // well.
    $file = $this->loadFile($filename, NULL, FALSE);

    // Determine the file's directory.
    $directory = dirname($filename);
    // If the file is in the current directory, make sure '.' doesn't appear in
    // the url() path.
    $directory = $directory == '.' ? '' : $directory .'/';

    // Alter all internal url() paths. Leave external paths alone. We don't need
    // to normalize absolute paths here (i.e. remove folder/... segments)
    // because that will be done later.
    return preg_replace('/url\(\s*([\'"]?)(?![a-z]+:|\/+)/i', 'url(\1'. $directory, $file);
  }

  /**
   * Processes the contents of a stylesheet for aggregation.
   *
   * @param $contents
   *   The contents of the stylesheet.
   * @param $optimize
   *   (optional) Boolean whether CSS contents should be minified. Defaults to
   *   FALSE.
   *
   * @return
   *   Contents of the stylesheet including the imported stylesheets.
   */
  protected function processCss($contents, $optimize = FALSE) {
    // Remove multiple charset declarations for standards compliance (and fixing Safari problems).
    $contents = preg_replace('/^@charset\s+[\'"](\S*?)\b[\'"];/i', '', $contents);

    if ($optimize) {
      // Perform some safe CSS optimizations.
      // Regexp to match comment blocks.
      $comment     = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';
      // Regexp to match double quoted strings.
      $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
      // Regexp to match single quoted strings.
      $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";
      // Strip all comment blocks, but keep double/single quoted strings.
      $contents = preg_replace(
        "<($double_quot|$single_quot)|$comment>Ss",
        "$1",
        $contents
      );
      // Remove certain whitespace.
      // There are different conditions for removing leading and trailing
      // whitespace.
      // @see http://php.net/manual/regexp.reference.subpatterns.php
      $contents = preg_replace('<
        # Strip leading and trailing whitespace.
          \s*([@{};,])\s*
        # Strip only leading whitespace from:
        # - Closing parenthesis: Retain "@media (bar) and foo".
        | \s+([\)])
        # Strip only trailing whitespace from:
        # - Opening parenthesis: Retain "@media (bar) and foo".
        # - Colon: Retain :pseudo-selectors.
        | ([\(:])\s+
      >xS',
        // Only one of the three capturing groups will match, so its reference
        // will contain the wanted value and the references for the
        // two non-matching groups will be replaced with empty strings.
        '$1$2$3',
        $contents
      );
      // End the file with a new line.
      $contents = trim($contents);
      $contents .= "\n";
    }

    // Replaces @import commands with the actual stylesheet content.
    // This happens recursively but omits external files.
    $contents = preg_replace_callback('/@import\s*(?:url\(\s*)?[\'"]?(?![a-z]+:)(?!\/\/)([^\'"\()]+)[\'"]?\s*\)?\s*;/', array($this, 'loadNestedFile'), $contents);

    return $contents;
  }

  /**
   * Prefixes all paths within a CSS file for processFile().
   *
   * @param array $matches
   *   An array of matches by a preg_replace_callback() call that scans for
   *   url() references in CSS files, except for external or absolute ones.
   *
   * Note: the only reason this method is public is so color.module can call it;
   * it is not on the AssetOptimizerInterface, so future refactorings can make
   * it protected.
   */
  public function rewriteFileURI($matches) {
    // Prefix with base and remove '../' segments where possible.
    $path = $this->rewriteFileURIBasePath . $matches[1];
    $last = '';
    while ($path != $last) {
      $last = $path;
      $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
    }
    return 'url(' . file_create_url($path) . ')';
  }

}
