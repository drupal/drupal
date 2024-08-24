<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\ToConfig;
use Drupal\Core\Form\FormStateInterface;

/**
 * Test form for testing config targets that are not 1:1.
 *
 * Note this is extending TreeConfigTargetForm to ensure that the presence of
 * both 1:1 config targets and ones that aren't work together in the same form.
 */
class NestedConfigTargetForm extends TreeConfigTargetForm {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_test.object'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_nested_config_target_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['favorites'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => 'Favorite fruits',
    ];
    $form['favorites']['first'] = [
      '#type' => 'textfield',
      '#title' => 'First choice',
      '#config_target' => new ConfigTarget(
        'form_test.object',
        'favorite_fruits',
        fromConfig: fn (?array $favorite_fruits): string => $favorite_fruits[0] ?? 'Mango',
        toConfig: fn (string $first, FormStateInterface $form_state): array => [
          0 => $first,
          1 => $form_state->getValue(['favorites', 'second']),
        ],
      ),
    ];
    $form['favorites']['second'] = [
      '#type' => 'textfield',
      '#title' => 'Second choice',
      '#config_target' => new ConfigTarget(
        'form_test.object',
        'favorite_fruits.1',
        fn (?string $second_favorite_fruit) : string => $second_favorite_fruit ?? 'Orange',
        // The "toConfig" callable for the first choice sets all choices.
        fn () => ToConfig::NoOp,
      ),
    ];
    $form['could_not_live_without'] = [
      '#weight' => 10,
      '#type' => 'textfield',
      '#title' => 'I could not live without',
      '#placeholder' => 'vegetables',
      '#wrapper_attributes' => ['class' => ['container-inline']],
      '#config_target' => new ConfigTarget(
        'form_test.object',
        'could_not_live_without',
        toConfig: function (string $could_not_live_without, FormStateInterface $form_state): ToConfig|string {
          if (empty($form_state->getValue(['favorites', 'first']))) {
            return ToConfig::DeleteKey;
          }
          if (empty($form_state->getValue(['favorites', 'second']))) {
            return ToConfig::DeleteKey;
          }
          if (empty($form_state->getValue(['vegetables', 'favorite']))) {
            return ToConfig::DeleteKey;
          }
          if (empty($form_state->getValue(['vegetables', 'nemesis']))) {
            return ToConfig::DeleteKey;
          }
          return $could_not_live_without;
        },

      ),
      // Only if everything else is answered will this be asked.
      '#states' => [
        'visible' => [
          // 2 favorite fruits.
          ':input[name="favorites[first]"]' => ['empty' => FALSE],
          ':input[name="favorites[second]"]' => ['empty' => FALSE],
          // Favorite & nemesis vegetable.
          ':input[name="vegetables[favorite]"]' => ['empty' => FALSE],
          ':input[name="vegetables[nemesis]"]' => ['empty' => FALSE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

}
