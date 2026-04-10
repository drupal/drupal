<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides a filter to restrict images to site.
 */
#[Filter(
  id: "filter_html_image_secure",
  title: new TranslatableMarkup("Restrict images to this site"),
  description: new TranslatableMarkup("Disallows usage of &lt;img&gt; tag sources that are not hosted on this site by replacing them with a placeholder image."),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
  weight: 9
)]
class FilterHtmlImageSecure extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The file URL generator service.
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * The module handler service.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The app root directory path.
   */
  protected string $root;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ?FileUrlGeneratorInterface $file_url_generator = NULL,
    ?ModuleHandlerInterface $module_handler = NULL,
    #[Autowire(param: 'app.root')]
    ?string $root = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!$file_url_generator) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $file_url_generator argument is deprecated in drupal:11.4.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
      $file_url_generator = \Drupal::service('file_url_generator');
    }
    $this->fileUrlGenerator = $file_url_generator;
    if (!$module_handler) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $module_handler argument is deprecated in drupal:11.4.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
      $module_handler = \Drupal::moduleHandler();
    }
    $this->moduleHandler = $module_handler;
    if (!$root) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $root argument is deprecated in drupal:11.4.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
      $root = \Drupal::root();
    }
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Find the path (e.g. '/') to Drupal root.
    $base_path = base_path();
    $base_path_length = mb_strlen($base_path);

    // Find the directory on the server where index.php resides.
    $local_dir = $this->root . '/';

    $html_dom = Html::load($text);
    $images = $html_dom->getElementsByTagName('img');

    foreach ($images as $image) {
      $src = $image->getAttribute('src');
      // Transform absolute image URLs to relative image URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $image->setAttribute('src', $this->fileUrlGenerator->transformRelative($src));

      // Verify that $src starts with $base_path.
      // This also ensures that external images cannot be referenced.
      $src = $image->getAttribute('src');
      if (mb_substr($src, 0, $base_path_length) === $base_path) {
        // Remove the $base_path to get the path relative to the Drupal root.
        // Ensure the path refers to an actual image by prefixing the image
        // source with the Drupal root and running getimagesize() on it.
        $local_image_path = $local_dir . mb_substr($src, $base_path_length);
        $local_image_path = rawurldecode($local_image_path);
        if (@getimagesize($local_image_path)) {
          // The image has the right path. Invalid images are handled below.
          continue;
        }
      }
      // Allow modules and themes to replace an invalid image with an error
      // indicator.
      // @see \Drupal\filter\Hook\FilterHooks::filterSecureImageAlter()
      $this->moduleHandler->alter('filter_secure_image', $image);
    }

    return new FilterProcessResult(Html::serialize($html_dom));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Only images hosted on this site may be used in &lt;img&gt; tags.');
  }

}
