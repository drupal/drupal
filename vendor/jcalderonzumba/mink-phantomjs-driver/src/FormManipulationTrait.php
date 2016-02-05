<?php

namespace Zumba\Mink\Driver;

use Behat\Mink\Exception\DriverException;

/**
 * Trait FormManipulationTrait
 * @package Zumba\Mink\Driver
 */
trait FormManipulationTrait {


  /**
   * Returns the value of a given xpath element
   * @param string $xpath
   * @return string
   * @throws DriverException
   */
  public function getValue($xpath) {
    $this->findElement($xpath, 1);
    $javascript = $this->javascriptTemplateRender("get_value.js.twig", array("xpath" => $xpath));
    return $this->browser->evaluate($javascript);
  }

  /**
   * @param string $xpath
   * @param string $value
   * @throws DriverException
   */
  public function setValue($xpath, $value) {
    $this->findElement($xpath, 1);
    //This stuff is BECAUSE the way the driver works for setting values when being checkboxes, radios, etc.
    if (is_bool($value)) {
      $value = $this->boolToString($value);
    }

    $javascript = $this->javascriptTemplateRender("set_value.js.twig", array("xpath" => $xpath, "value" => json_encode($value)));
    $this->browser->evaluate($javascript);
  }


  /**
   * Submits a form given an xpath selector
   * @param string $xpath
   * @throws DriverException
   */
  public function submitForm($xpath) {
    $element = $this->findElement($xpath, 1);
    $tagName = $this->browser->tagName($element["page_id"], $element["ids"][0]);
    if (strcmp(strtolower($tagName), "form") !== 0) {
      throw new DriverException("Can not submit something that is not a form");
    }
    $this->browser->trigger($element["page_id"], $element["ids"][0], "submit");
  }

  /**
   * Helper method needed for twig and javascript stuff
   * @param $boolValue
   * @return string
   */
  protected function boolToString($boolValue) {
    if ($boolValue === true) {
      return "1";
    }
    return "0";
  }

  /**
   * Selects an option
   * @param string $xpath
   * @param string $value
   * @param bool   $multiple
   * @return bool
   * @throws DriverException
   */
  public function selectOption($xpath, $value, $multiple = false) {
    $element = $this->findElement($xpath, 1);
    $tagName = strtolower($this->browser->tagName($element["page_id"], $element["ids"][0]));
    $attributes = $this->browser->attributes($element["page_id"], $element["ids"][0]);

    if (!in_array($tagName, array("input", "select"))) {
      throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
    }

    if ($tagName === "input" && $attributes["type"] != "radio") {
      throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
    }

    return $this->browser->selectOption($element["page_id"], $element["ids"][0], $value, $multiple);
  }

  /**
   * Check control over an input element of radio or checkbox type
   * @param $xpath
   * @return bool
   * @throws DriverException
   */
  protected function inputCheckableControl($xpath) {
    $element = $this->findElement($xpath, 1);
    $tagName = strtolower($this->browser->tagName($element["page_id"], $element["ids"][0]));
    $attributes = $this->browser->attributes($element["page_id"], $element["ids"][0]);
    if ($tagName != "input") {
      throw new DriverException("Can not check when the element is not of the input type");
    }
    if (!in_array($attributes["type"], array("checkbox", "radio"))) {
      throw new DriverException("Can not check when the element is not checkbox or radio");
    }
    return true;
  }

  /**
   * We click on the checkbox or radio when possible and needed
   * @param string $xpath
   * @throws DriverException
   */
  public function check($xpath) {
    $this->inputCheckableControl($xpath);
    $javascript = $this->javascriptTemplateRender("check_element.js.twig", array("xpath" => $xpath, "check" => "true"));
    $this->browser->evaluate($javascript);
  }

  /**
   * We click on the checkbox or radio when possible and needed
   * @param string $xpath
   * @throws DriverException
   */
  public function uncheck($xpath) {
    $this->inputCheckableControl($xpath);
    $javascript = $this->javascriptTemplateRender("check_element.js.twig", array("xpath" => $xpath, "check" => "false"));
    $this->browser->evaluate($javascript);
  }

  /**
   * Checks if the radio or checkbox is checked
   * @param string $xpath
   * @return bool
   * @throws DriverException
   */
  public function isChecked($xpath) {
    $this->findElement($xpath, 1);
    $javascript = $this->javascriptTemplateRender("is_checked.js.twig", array("xpath" => $xpath));
    $checked = $this->browser->evaluate($javascript);

    if ($checked === null) {
      throw new DriverException("Can not check when the element is not checkbox or radio");
    }

    return $checked;
  }

  /**
   * Checks if the option is selected or not
   * @param string $xpath
   * @return bool
   * @throws DriverException
   */
  public function isSelected($xpath) {
    $elements = $this->findElement($xpath, 1);
    $javascript = $this->javascriptTemplateRender("is_selected.js.twig", array("xpath" => $xpath));
    $tagName = $this->browser->tagName($elements["page_id"], $elements["ids"][0]);
    if (strcmp(strtolower($tagName), "option") !== 0) {
      throw new DriverException("Can not assert on element that is not an option");
    }

    return $this->browser->evaluate($javascript);
  }
}
