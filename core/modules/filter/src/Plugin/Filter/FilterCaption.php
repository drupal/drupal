<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterPluginManager;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to caption elements.
 *
 * When used in combination with the filter_align filter, this must run last.
 *
 * @Filter(
 *   id = "filter_caption",
 *   title = @Translation("Caption images"),
 *   description = @Translation("Uses a <code>data-caption</code> attribute on <code>&lt;img&gt;</code> tags to caption images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterCaption extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Filter manager.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterManager;

  /**
   * Constructs a new FilterCaption.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Definition.
   * @param \Drupal\filter\FilterPluginManager $filter_manager
   *   Filter plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FilterPluginManager $filter_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->filterManager = $filter_manager ?: \Drupal::service('plugin.manager.filter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-caption') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $html_filter = $this->filterManager->createInstance('filter_html', [
        'settings' => [
          'allowed_html' => '<a href hreflang target rel> <em> <strong> <cite> <code> <br>',
          'filter_html_help' => FALSE,
          'filter_html_nofollow' => FALSE,
        ],
      ]);
      foreach ($xpath->query('//*[@data-caption]') as $node) {
        // Read the data-caption attribute's value, then delete it.
        $caption = Html::escape($node->getAttribute('data-caption'));
        $node->removeAttribute('data-caption');

        // Sanitize caption: decode HTML encoding, limit allowed HTML tags; only
        // allow inline tags that are allowed by default, plus <br>.
        $caption = Html::decodeEntities($caption);
        $raw_caption = $caption;
        $filtered_caption = $html_filter->process($caption, $langcode);
        $result->addCacheableDependency($filtered_caption);
        $caption = FilteredMarkup::create($filtered_caption->getProcessedText());

        // The caption must be non-empty - however the Media Embed CKEditor
        // plugin uses a single space to represent a newly added caption. The
        // HTML filter will transform this into an empty string and prevent the
        // content editor from adding a new caption. To allow for this we treat
        // a raw caption value of ' ' as valid and adding the wrapping figure
        // element.
        // @see core/modules/media/js/plugins/drupalmedia/plugin.js
        if (mb_strlen($caption) === 0 && $raw_caption !== ' ') {
          continue;
        }

        // Given the updated node and caption: re-render it with a caption, but
        // bubble up the value of the class attribute of the captioned element,
        // this allows it to collaborate with e.g. the filter_align filter.
        $tag = $node->tagName;
        $classes = $node->getAttribute('class');
        $node->removeAttribute('class');
        $node = ($node->parentNode->tagName === 'a') ? $node->parentNode : $node;
        $filter_caption = [
          '#theme' => 'filter_caption',
          // We pass the unsanitized string because this is a text format
          // filter, and after filtering, we always assume the output is safe.
          // @see \Drupal\filter\Element\ProcessedText::preRenderText()
          '#node' => FilteredMarkup::create($node->C14N()),
          '#tag' => $tag,
          '#caption' => $caption,
          '#classes' => $classes,
        ];
        $altered_html = \Drupal::service('renderer')->render($filter_caption);

        // Load the altered HTML into a new DOMDocument and retrieve the element.
        $updated_nodes = Html::load($altered_html)->getElementsByTagName('body')
          ->item(0)
          ->childNodes;

        foreach ($updated_nodes as $updated_node) {
          // Import the updated node from the new DOMDocument into the original
          // one, importing also the child nodes of the updated node.
          $updated_node = $dom->importNode($updated_node, TRUE);
          $node->parentNode->insertBefore($updated_node, $node);
        }
        // Finally, remove the original data-caption node.
        $node->parentNode->removeChild($node);
      }

      $result->setProcessedText(Html::serialize($dom))
        ->addAttachments([
          'library' => [
            'filter/caption',
          ],
        ]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can caption images, videos, blockquotes, and so on. Examples:</p>
        <ul>
            <li><code>&lt;img src="" data-caption="This is a caption" /&gt;</code></li>
            <li><code>&lt;video src="" data-caption="The Drupal Dance" /&gt;</code></li>
            <li><code>&lt;blockquote data-caption="Dries Buytaert"&gt;Drupal is awesome!&lt;/blockquote&gt;</code></li>
            <li><code>&lt;code data-caption="Hello world in JavaScript."&gt;alert("Hello world!");&lt;/code&gt;</code></li>
        </ul>');
    }
    else {
      return $this->t('You can caption images (<code>data-caption="Text"</code>), but also videos, blockquotes, and so on.');
    }
  }

}
