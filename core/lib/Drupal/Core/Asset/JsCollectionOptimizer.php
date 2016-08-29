<?php

namespace Drupal\Core\Asset;

use Drupal\Core\State\StateInterface;


/**
 * Optimizes JavaScript assets.
 */
class JsCollectionOptimizer implements AssetCollectionOptimizerInterface {

  /**
   * A JS asset grouper.
   *
   * @var \Drupal\Core\Asset\JsCollectionGrouper
   */
  protected $grouper;

  /**
   * A JS asset optimizer.
   *
   * @var \Drupal\Core\Asset\JsOptimizer
   */
  protected $optimizer;

  /**
   * An asset dumper.
   *
   * @var \Drupal\Core\Asset\AssetDumper
   */
  protected $dumper;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a JsCollectionOptimizer.
   *
   * @param \Drupal\Core\Asset\AssetCollectionGrouperInterface $grouper
   *   The grouper for JS assets.
   * @param \Drupal\Core\Asset\AssetOptimizerInterface $optimizer
   *   The optimizer for a single JS asset.
   * @param \Drupal\Core\Asset\AssetDumperInterface $dumper
   *   The dumper for optimized JS assets.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   */
  public function __construct(AssetCollectionGrouperInterface $grouper, AssetOptimizerInterface $optimizer, AssetDumperInterface $dumper, StateInterface $state) {
    $this->grouper = $grouper;
    $this->optimizer = $optimizer;
    $this->dumper = $dumper;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   *
   * The cache file name is retrieved on a page load via a lookup variable that
   * contains an associative array. The array key is the hash of the names in
   * $files while the value is the cache file name. The cache file is generated
   * in two cases. First, if there is no file name value for the key, which will
   * happen if a new file name has been added to $files or after the lookup
   * variable is emptied to force a rebuild of the cache. Second, the cache file
   * is generated if it is missing on disk. Old cache files are not deleted
   * immediately when the lookup variable is emptied, but are deleted after a
   * configurable period (@code system.performance.stale_file_threshold @endcode)
   * to ensure that files referenced by a cached page will still be available.
   */
  public function optimize(array $js_assets) {
    // Group the assets.
    $js_groups = $this->grouper->group($js_assets);

    // Now optimize (concatenate, not minify) and dump each asset group, unless
    // that was already done, in which case it should appear in
    // system.js_cache_files.
    // Drupal contrib can override this default JS aggregator to keep the same
    // grouping, optimizing and dumping, but change the strategy that is used to
    // determine when the aggregate should be rebuilt (e.g. mtime, HTTPS …).
    $map = $this->state->get('system.js_cache_files') ?: array();
    $js_assets = array();
    foreach ($js_groups as $order => $js_group) {
      // We have to return a single asset, not a group of assets. It is now up
      // to one of the pieces of code in the switch statement below to set the
      // 'data' property to the appropriate value.
      $js_assets[$order] = $js_group;
      unset($js_assets[$order]['items']);

      switch ($js_group['type']) {
        case 'file':
          // No preprocessing, single JS asset: just use the existing URI.
          if (!$js_group['preprocess']) {
            $uri = $js_group['items'][0]['data'];
            $js_assets[$order]['data'] = $uri;
          }
          // Preprocess (aggregate), unless the aggregate file already exists.
          else {
            $key = $this->generateHash($js_group);
            $uri = '';
            if (isset($map[$key])) {
              $uri = $map[$key];
            }
            if (empty($uri) || !file_exists($uri)) {
              // Concatenate each asset within the group.
              $data = '';
              foreach ($js_group['items'] as $js_asset) {
                // Optimize this JS file, but only if it's not yet minified.
                if (isset($js_asset['minified']) && $js_asset['minified']) {
                  $data .= file_get_contents($js_asset['data']);
                }
                else {
                  $data .= $this->optimizer->optimize($js_asset);
                }
                // Append a ';' and a newline after each JS file to prevent them
                // from running together.
                $data .= ";\n";
              }
              // Remove unwanted JS code that cause issues.
              $data = $this->optimizer->clean($data);
              // Dump the optimized JS for this group into an aggregate file.
              $uri = $this->dumper->dump($data, 'js');
              // Set the URI for this group's aggregate file.
              $js_assets[$order]['data'] = $uri;
              // Persist the URI for this aggregate file.
              $map[$key] = $uri;
              $this->state->set('system.js_cache_files', $map);
            }
            else {
              // Use the persisted URI for the optimized JS file.
              $js_assets[$order]['data'] = $uri;
            }
            $js_assets[$order]['preprocessed'] = TRUE;
          }
          break;

        case 'external':
          // We don't do any aggregation and hence also no caching for external
          // JS assets.
          $uri = $js_group['items'][0]['data'];
          $js_assets[$order]['data'] = $uri;
          break;
      }
    }

    return $js_assets;
  }

  /**
   * Generate a hash for a given group of JavaScript assets.
   *
   * @param array $js_group
   *   A group of JavaScript assets.
   *
   * @return string
   *   A hash to uniquely identify the given group of JavaScript assets.
   */
  protected function generateHash(array $js_group) {
    $js_data = array();
    foreach ($js_group['items'] as $js_file) {
      $js_data[] = $js_file['data'];
    }
    return hash('sha256', serialize($js_data));
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    return $this->state->get('system.js_cache_files');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->state->delete('system.js_cache_files');
    $delete_stale = function($uri) {
      // Default stale file threshold is 30 days.
      if (REQUEST_TIME - filemtime($uri) > \Drupal::config('system.performance')->get('stale_file_threshold')) {
        file_unmanaged_delete($uri);
      }
    };
    file_scan_directory('public://js', '/.*/', array('callback' => $delete_stale));
  }

}
