<?php

declare(strict_types=1);

namespace Drupal\form_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block containing a simple redirect form.
 *
 * @see \Drupal\form_test\Form\RedirectBlockForm
 */
#[Block(
  id: "redirect_form_block",
  admin_label: new TranslatableMarkup("Redirecting form"),
  category: new TranslatableMarkup("Forms"),
)]
class RedirectFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new RedirectFormBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FormBuilderInterface $formBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm('Drupal\form_test\Form\RedirectBlockForm');
  }

}
