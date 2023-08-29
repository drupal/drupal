<?php

namespace Drupal\Core\Asset;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Utility\Error;
use Peast\Formatter\Compact as CompactFormatter;
use Peast\Peast;
use Peast\Renderer;
use Peast\Syntax\Exception as PeastSyntaxException;
use Psr\Log\LoggerInterface;

/**
 * Optimizes a JavaScript asset.
 */
class JsOptimizer implements AssetOptimizerInterface {

  /**
   * Constructs a new JsOptimizer object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(protected readonly LoggerInterface $logger) {}

  /**
   * {@inheritdoc}
   */
  public function optimize(array $js_asset) {
    if ($js_asset['type'] !== 'file') {
      throw new \Exception('Only file JavaScript assets can be optimized.');
    }
    if (!$js_asset['preprocess']) {
      throw new \Exception('Only file JavaScript assets with preprocessing enabled can be optimized.');
    }

    // If a BOM is found, convert the file to UTF-8, then use substr() to
    // remove the BOM from the result.
    $data = file_get_contents($js_asset['data']);
    if ($encoding = (Unicode::encodingFromBOM($data))) {
      $data = mb_substr(Unicode::convertToUtf8($data, $encoding), 1);
    }
    // If no BOM is found, check for the charset attribute.
    elseif (isset($js_asset['attributes']['charset'])) {
      $data = Unicode::convertToUtf8($data, $js_asset['attributes']['charset']);
    }
    // Remove comments, whitespace, and optional braces.
    try {
      $ast = Peast::latest($data)->parse();
      $renderer = new Renderer();
      $renderer->setFormatter(new CompactFormatter());
      return $renderer->render($ast);
    }
    catch (\Exception $exception) {
      if ($exception instanceof PeastSyntaxException) {
        $position = $exception->getPosition();
        Error::logException($this->logger, $exception, 'Syntax error:  @message, File: @asset_file, Line: @asset_line, Column: @asset_column, Index: @asset_index', [
          '@asset_file' => $js_asset['data'],
          '@asset_line' => $position->getLine(),
          '@asset_column' => $position->getColumn(),
          '@asset_index' => $position->getIndex(),
        ]);
      }
      else {
        Error::logException($this->logger, $exception);
      }
      return $data;
    }
  }

  /**
   * Processes the contents of a javascript asset for cleanup.
   *
   * @param string $contents
   *   The contents of the javascript asset.
   *
   * @return string
   *   Contents of the javascript asset.
   */
  public function clean($contents) {
    // Remove JS source and source mapping URLs or these may cause 404 errors.
    $contents = preg_replace('/\/\/(#|@)\s(sourceURL|sourceMappingURL)=\s*(\S*?)\s*$/m', '', $contents);

    return $contents;
  }

}
