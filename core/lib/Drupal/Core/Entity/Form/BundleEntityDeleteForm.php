<?php

declare(strict_types=1);

namespace Drupal\Core\Entity\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityDeleteForm;

/**
 * A generic entity delete form for entities which are bundles.
 *
 * @ingroup entity_api
 */
class BundleEntityDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bundle_entity_type = $this->entity->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    if ($content_entity_type_id === NULL) {
      throw new \LogicException(sprintf('Entity type %s uses BundleEntityDeleteForm but is not a bundle entity type.', $this->entity->getEntityTypeId()));
    }

    $content_entity_type = $this->entityTypeManager->getDefinition($content_entity_type_id);

    $prevent_deletion = FALSE;

    $num_content_entities = $this->getContentEntityCount();
    if ($num_content_entities > 0) {
      $prevent_deletion = TRUE;

      $form['existing_entities'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->formatPlural(
          $num_content_entities,
          '%type is used by 1 @singular-content-label on your site. You may not remove this @bundle-label until you have removed all of the %type @plural-content-label.',
          '%type is used by @count @plural-content-label on your site. You may not remove this @bundle-label until you have removed all of the %type @plural-content-label.', [
            '%type' => $this->entity->label(),
            '@bundle-label' => $bundle_entity_type->getSingularLabel(),
            '@singular-content-label' => $content_entity_type->getSingularLabel(),
            '@plural-content-label' => $content_entity_type->getPluralLabel(),
          ]
        ),
      ];
    }

    $additional_reasons = $this->getAdditionalReasonsToPreventDeletion();
    if (count($additional_reasons) > 0) {
      $prevent_deletion = TRUE;

      foreach ($additional_reasons as $reason) {
        $form['additional_reasons'][] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $reason,
        ];
      }
    }

    // If we are blocking deletion, don't show the delete button or the config
    // changes that the parent class adds.
    if ($prevent_deletion) {
      // Change the title, as the user can't delete.
      $form['#title'] = $this->t("Unable to delete @bundle-label %type", [
        '%type' => $this->entity->label(),
        '@bundle-label' => $bundle_entity_type->getSingularLabel(),
      ]);

      // Retrieve and add the form actions array but without the submit button.
      // We need to show the cancel button for non-JS users showing the form
      // on a standalone page instead of a modal.
      $actions = $this->actionsElement($form, $form_state);
      unset($actions['submit']);
      if (!empty($actions)) {

        $form['actions'] = $actions;
      }

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Gets the count of content entities of this form's bundle.
   *
   * This is handled separately from additional reasons to make it easier for
   * entity types to customize.
   *
   * @return int
   *   The number of entities.
   */
  protected function getContentEntityCount(): int {
    $bundle_entity_type = $this->entity->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    $content_entity_type = $this->entityTypeManager->getDefinition($content_entity_type_id);

    $num_content_entities = $this->entityTypeManager->getStorage($content_entity_type_id)->getQuery()
      ->accessCheck(FALSE)
      ->condition($content_entity_type->getKey('bundle'), $this->entity->id())
      ->count()
      ->execute();

    return $num_content_entities;
  }

  /**
   * Gets a list of additional reasons preventing deletion of the bundle entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of translated strings, each one giving a separate reason for
   *   preventing deletion. These are shown each in a paragraph on the form. If
   *   the array is empty, and there are no content entities, then deletion is
   *   allowed.
   */
  protected function getAdditionalReasonsToPreventDeletion(): array {
    return [];
  }

}
