<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Defines helper methods for forms which collect input on behalf of recipes.
 */
trait RecipeInputFormTrait {

  /**
   * Generates a tree of form elements for a recipe's inputs.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   A recipe.
   *
   * @return array[]
   *   A nested array of form elements for collecting input values for the given
   *   recipe and its dependencies. The elements will be grouped by the recipe
   *   that defined the input -- for example, $return['recipe_name']['input1'],
   *   $return['recipe_name']['input2'], $return['dependency']['input_name'],
   *   and so forth. The returned array will have the `#tree` property set to
   *   TRUE.
   */
  protected function buildRecipeInputForm(Recipe $recipe): array {
    $collector = new class () implements InputCollectorInterface {

      /**
       * A form array containing the input elements for the given recipe.
       *
       * This will be a tree of input elements, grouped by the name of the
       * recipe that defines them. For example:
       *
       * @code
       * $form = [
       *   'recipe_1' => [
       *     'input_1' => [
       *       '#type' => 'textfield',
       *       '#title' => 'Some input value',
       *     ],
       *     'input_2' => [
       *       '#type' => 'checkbox',
       *       '#title' => 'Enable some feature or other?',
       *     ],
       *   ],
       *   'dependency_recipe' => [
       *     'input_1' => [
       *       '#type' => 'textarea',
       *       '#title' => 'An input defined by a dependency of recipe_1',
       *     ],
       *   ],
       *   '#tree' => TRUE,
       * ];
       * @endcode
       *
       * The `#tree` property will always be set to TRUE.
       *
       * @var array
       */
      // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
      public array $form = [];

      /**
       * {@inheritdoc}
       */
      public function collectValue(string $name, DataDefinitionInterface $definition, mixed $default_value): mixed {
        $element = $definition->getSetting('form');
        if ($element) {
          $element += [
            '#description' => $definition->getDescription(),
            '#default_value' => $default_value,
            '#type' => 'value',
          ];
          // Recipe inputs are required by default, unless they are single
          // checkboxes, in which case the `#required` behavior doesn't make
          // a lot of sense because it forces the user to check the box.
          $element['#required'] ??= ($element['#type'] !== 'checkbox');
          NestedArray::setValue($this->form, explode('.', $name, 2), $element);

          // Always return the input elements as a tree.
          $this->form['#tree'] = TRUE;
        }
        return $default_value;
      }

    };
    $recipe->input->collectAll($collector);
    return $collector->form;
  }

  /**
   * Validates user-inputted values to a recipe and its dependencies.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   A recipe.
   * @param array $form
   *   The form being validated, which should include the tree of elements
   *   returned by ::buildRecipeInputForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state. The values should be organized in the tree
   *   structure that was returned by ::buildRecipeInputForm().
   */
  protected function validateRecipeInput(Recipe $recipe, array &$form, FormStateInterface $form_state): void {
    try {
      $this->setRecipeInput($recipe, $form_state);
    }
    catch (ValidationFailedException $e) {
      $data = $e->getValue();

      if ($data instanceof TypedDataInterface) {
        $element = NestedArray::getValue($form, explode('.', $data->getName(), 2));
        $form_state->setError($element, $e->getMessage());
      }
      else {
        // If the data isn't a typed data object, we have no idea how to handle
        // the situation, so just re-throw the exception.
        throw $e;
      }
    }
  }

  /**
   * Supplies user-inputted values to a recipe and its dependencies.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   A recipe.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state. The values should be organized in the tree
   *   structure that was returned by ::buildRecipeInputForm().
   */
  protected function setRecipeInput(Recipe $recipe, FormStateInterface $form_state): void {
    $recipe->input->collectAll(new class ($form_state) implements InputCollectorInterface {

      public function __construct(private readonly FormStateInterface $formState) {
      }

      /**
       * {@inheritdoc}
       */
      public function collectValue(string $name, DataDefinitionInterface $definition, mixed $default_value): mixed {
        return $this->formState->getValue(explode('.', $name, 2), $default_value);
      }

    });
  }

}
