<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Field\FieldFormatter\UserNameFormatter.
 */

namespace Drupal\user\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'user_name' formatter.
 *
 * @FieldFormatter(
 *   id = "user_name",
 *   label = @Translation("User name"),
 *   description = @Translation("Display the user or author name."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class UserNameFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['link_to_entity'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the user'),
      '#default_value' => $this->getSetting('link_to_entity'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = [];

    foreach ($items as $delta => $item) {
      /** @var $user \Drupal\user\UserInterface */
      if ($user = $item->getEntity()) {
        if ($this->getSetting('link_to_entity')) {
          $elements[$delta] = [
            '#theme' => 'username',
            '#account' => $user,
            '#link_options' => ['attributes' => ['rel' => 'user']],
            '#cache' => [
              'tags' => $user->getCacheTags(),
            ],
          ];
        }
        else {
          $elements[$delta] = [
            '#markup' => $user->getUsername(),
            '#cache' => [
              'tags' => $user->getCacheTags(),
            ],
          ];
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'user' && $field_definition->getName() === 'name';
  }

}
