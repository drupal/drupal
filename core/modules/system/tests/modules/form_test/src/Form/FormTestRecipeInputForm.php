<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeInputFormTrait;
use Drupal\Core\Recipe\RecipeRunner;

class FormTestRecipeInputForm extends FormBase {

  use RecipeInputFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'form_test_recipe_input';
  }

  /**
   * Returns the recipe object under test.
   *
   * @return \Drupal\Core\Recipe\Recipe
   *   A Recipe object for the input_test recipe.
   */
  private function getRecipe(): Recipe {
    return Recipe::createFromDirectory('core/tests/fixtures/recipes/input_test');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form += $this->buildRecipeInputForm($this->getRecipe());

    $form['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply recipe'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $this->validateRecipeInput($this->getRecipe(), $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $recipe = $this->getRecipe();
    $this->setRecipeInput($recipe, $form_state);
    RecipeRunner::processRecipe($recipe);
  }

}
