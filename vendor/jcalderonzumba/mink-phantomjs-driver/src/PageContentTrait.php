<?php

namespace Zumba\Mink\Driver;

use Behat\Mink\Exception\DriverException;

/**
 * Class PageContentTrait
 * @package Zumba\Mink\Driver
 */
trait PageContentTrait {

  /**
   * @return string
   */
  public function getContent() {
    return $this->browser->getBody();
  }

  /**
   * Given xpath, will try to get ALL the text, visible and not visible from such xpath
   * @param string $xpath
   * @return string
   * @throws DriverException
   */
  public function getText($xpath) {
    $elements = $this->findElement($xpath, 1);
    //allText works only with ONE element so it will be the first one and also returns new lines that we will remove
    $text = $this->browser->allText($elements["page_id"], $elements["ids"][0]);
    $text = trim(str_replace(array("\r", "\r\n", "\n"), ' ', $text));
    $text = preg_replace('/ {2,}/', ' ', $text);
    return $text;
  }

  /**
   * Returns the inner html of a given xpath
   * @param string $xpath
   * @return string
   * @throws DriverException
   */
  public function getHtml($xpath) {
    $elements = $this->findElement($xpath, 1);
    //allText works only with ONE element so it will be the first one
    return $this->browser->allHtml($elements["page_id"], $elements["ids"][0], "inner");
  }

  /**
   * Gets the outer html of a given xpath
   * @param string $xpath
   * @return string
   * @throws DriverException
   */
  public function getOuterHtml($xpath) {
    $elements = $this->findElement($xpath, 1);
    //allText works only with ONE element so it will be the first one
    return $this->browser->allHtml($elements["page_id"], $elements["ids"][0], "outer");
  }

  /**
   * Returns the binary representation of the current page we are in
   * @throws DriverException
   * @return string
   */
  public function getScreenshot() {
    $options = array("full" => true, "selector" => null);
    $b64ScreenShot = $this->browser->renderBase64("JPEG", $options);
    if (($binaryScreenShot = base64_decode($b64ScreenShot, true)) === false) {
      throw new DriverException("There was a problem while doing the screenshot of the current page");
    }
    return $binaryScreenShot;
  }
}
