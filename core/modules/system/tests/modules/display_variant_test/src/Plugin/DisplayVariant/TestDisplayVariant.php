<?php

namespace Drupal\display_variant_test\Plugin\DisplayVariant;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Display\Attribute\DisplayVariant;
use Drupal\Core\Display\VariantBase;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a display variant that requires configuration.
 */
#[DisplayVariant(
  id: 'display_variant_test',
  admin_label: new TranslatableMarkup('Test display variant')
)]
class TestDisplayVariant extends VariantBase implements PageVariantInterface, ContextAwareVariantInterface {

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent = [];

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * An array of collected contexts.
   *
   * This is only used on runtime, and is not stored.
   *
   * @var \Drupal\Component\Plugin\Context\ContextInterface[]
   */
  protected $contexts = [];

  /**
   * Gets the contexts.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   * Sets the contexts.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts, keyed by context name.
   *
   * @return $this
   */
  public function setContexts(array $contexts) {
    $this->contexts = $contexts;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    assert(!empty($this->getConfiguration()['required_configuration']), 'Ensure that ::setMainContent() is called with the variant configuration');
    $this->mainContent = $main_content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    assert(!empty($this->getConfiguration()['required_configuration']), 'Ensure that ::setTitle() is called with the variant configuration');
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    if (empty($config['required_configuration'])) {
      throw new \Exception('Required configuration is missing!');
    }

    $contexts = $this->getContexts();
    if (!isset($contexts['context'])) {
      throw new \Exception('Required context is missing!');
    }

    $build = [];
    $build['content']['default'] = [
      '#markup' => $config['required_configuration'] . ' ' . $contexts['context']->getContextValue(),
    ];

    CacheableMetadata::createFromObject($this)->applyTo($build);

    return $build;
  }

}
