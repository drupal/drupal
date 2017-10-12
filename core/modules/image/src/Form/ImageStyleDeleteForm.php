<?php

namespace Drupal\image\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Creates a form to delete an image style.
 *
 * @internal
 */
class ImageStyleDeleteForm extends EntityDeleteForm {

  /**
   * Replacement options.
   *
   * @var array
   */
  protected $replacementOptions;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Optionally select a style before deleting %style', ['%style' => $this->entity->label()]);
  }
  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (count($this->getReplacementOptions()) > 1) {
      return $this->t('If this style is in use on the site, you may select another style to replace it. All images that have been generated for this style will be permanently deleted. If no replacement style is selected, the dependent configurations might need manual reconfiguration.');
    }
    return $this->t('All images that have been generated for this style will be permanently deleted. The dependent configurations might need manual reconfiguration.');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $replacement_styles = $this->getReplacementOptions();
    // If there are non-empty options in the list, allow the user to optionally
    // pick up a replacement.
    if (count($replacement_styles) > 1) {
      $form['replacement'] = [
        '#type' => 'select',
        '#title' => $this->t('Replacement style'),
        '#options' => $replacement_styles,
        '#empty_option' => $this->t('- No replacement -'),
        '#weight' => -5,
      ];
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save a selected replacement in the image style storage. It will be used
    // later, in the same request, when resolving dependencies.
    if ($replacement = $form_state->getValue('replacement')) {
      /** @var \Drupal\image\ImageStyleStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($this->entity->getEntityTypeId());
      $storage->setReplacementId($this->entity->id(), $replacement);
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Returns a list of image style replacement options.
   *
   * @return array
   *   An option list suitable for the form select '#options'.
   */
  protected function getReplacementOptions() {
    if (!isset($this->replacementOptions)) {
      $this->replacementOptions = array_diff_key(image_style_options(), [$this->getEntity()->id() => '']);
    }
    return $this->replacementOptions;
  }

}
