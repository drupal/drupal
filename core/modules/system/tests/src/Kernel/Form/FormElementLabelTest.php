<?php

namespace Drupal\Tests\system\Kernel\Form;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests for form_element_label theme hook.
 *
 * @group Form
 */
class FormElementLabelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Ensures that attributes can be placed for form element label.
   */
  public function testAttributes() {
    $render_array = [
      '#type' => 'label',
      '#attributes' => ['class' => ['kitten']],
      '#title' => 'Kittens',
      '#title_display' => 'above',
    ];
    $css_selector_converter = new CssSelectorConverter();
    $this->render($render_array);
    $elements = $this->xpath($css_selector_converter->toXPath('.kitten'));
    $this->assertCount(1, $elements);

    // Add label attributes to a form element.
    $render_array = [
      '#type' => 'textfield',
      '#label_attributes' => ['class' => ['meow']],
      '#title' => 'Kitten sounds',
    ];
    $this->render($render_array);
    $elements = $this->xpath($css_selector_converter->toXPath('label.meow'));
    $this->assertCount(1, $elements);
  }

}
