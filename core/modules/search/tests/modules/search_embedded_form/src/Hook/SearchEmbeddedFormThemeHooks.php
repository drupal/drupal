<?php

declare(strict_types=1);

namespace Drupal\search_embedded_form\Hook;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for search_embedded_form module.
 *
 * A sample use of an embedded form is an e-commerce site where each search
 * result may include an embedded form with buttons like "Add to cart" for each
 * individual product (node) listed in the search results.
 */
class SearchEmbeddedFormThemeHooks {

  public function __construct(
    protected FormBuilderInterface $formBuilder,
  ) {}

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_search_result')]
  public function preprocessSearchResult(&$variables): void {
    $form = $this->formBuilder->getForm('Drupal\search_embedded_form\Form\SearchEmbeddedForm');
    $variables['snippet'] = array_merge($variables['snippet'], $form);
  }

}
