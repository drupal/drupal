<?php

namespace Drupal\text;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Serialization\Attribute\JsonSchema;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Render\FilteredMarkup;

/**
 * A computed property for processing text with a format.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - text source: The text property containing the to be processed text.
 */
class TextProcessed extends TypedData implements CacheableDependencyInterface {

  /**
   * Cached processed text.
   *
   * @var \Drupal\filter\FilterProcessResult|null
   */
  protected $processed = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, ?TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    if ($definition->getSetting('text source') === NULL) {
      throw new \InvalidArgumentException("The definition's 'text source' key has to specify the name of the text property to be processed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  #[JsonSchema(['type' => 'string', 'description' => 'May contain HTML markup.'])]
  public function getValue() {
    if ($this->processed !== NULL) {
      return FilteredMarkup::create($this->processed->getProcessedText());
    }

    $item = $this->getParent();
    $text = $item->{($this->definition->getSetting('text source'))};

    // Avoid doing unnecessary work on empty strings.
    if (!isset($text) || $text === '') {
      $this->processed = new FilterProcessResult('');
    }
    else {
      $build = [
        '#type' => 'processed_text',
        '#text' => $text,
        '#format' => $item->format,
        '#filter_types_to_skip' => [],
        '#langcode' => $item->getLangcode(),
      ];
      // Capture the cacheability metadata associated with the processed text.
      $processed_text = $this->getRenderer()->renderInIsolation($build);
      $this->processed = FilterProcessResult::createFromRenderArray($build)->setProcessedText((string) $processed_text);
    }
    return FilteredMarkup::create($this->processed->getProcessedText());
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->processed = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $this->getValue();
    return $this->processed->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $this->getValue();
    return $this->processed->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $this->getValue();
    return $this->processed->getCacheMaxAge();
  }

  /**
   * Returns the renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer service.
   */
  protected function getRenderer() {
    return \Drupal::service('renderer');
  }

}
