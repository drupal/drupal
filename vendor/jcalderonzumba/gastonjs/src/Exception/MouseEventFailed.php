<?php

namespace Zumba\GastonJS\Exception;

/**
 * Class MouseEventFailed
 * @package Zumba\GastonJS\Exception
 */
class MouseEventFailed extends NodeError {

  /**
   * Gets the name of the event
   * @return string
   */
  public function getName() {
    return $this->response["args"][0];
  }

  /**
   * Selector of the element to act with the mouse
   * @return string
   */
  public function getSelector() {
    return $this->response["args"][1];
  }

  /**
   * Returns the position where the click was done
   * @return array
   */
  public function getPosition() {
    $position = array();
    $position[0] = $this->response["args"][1]['x'];
    $position[1] = $this->response["args"][2]['y'];
    return $position;
  }

  /**
   * @return string
   */
  public function message() {
    $name = $this->getName();
    $position = implode(",", $this->getPosition());
    return "Firing a $name at co-ordinates [$position] failed. Poltergeist detected
            another element with CSS selector '#{selector}' at this position.
            It may be overlapping the element you are trying to interact with.
            If you don't care about overlapping elements, try using node.trigger('$name').";
  }
}
