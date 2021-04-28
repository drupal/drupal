<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Url;

trait AjaxableElementTrait {

  protected function setAjaxProperty(string $name, $value) {
    $this->renderable['#ajax'][$name] = $value;
    return $this;
  }

  public function setAjaxCallback(callable $callback) {
    return $this->setAjaxProperty('callback', $callback);
  }

  public function setAjaxWrapperId(string $wrapper_id) {
    return $this->setAjaxProperty('wrapper', $wrapper_id);
  }

  public function setAjaxMethod(string $method = 'replaceWith') {
    return $this->setAjaxProperty('method', $method);
  }

  public function setAjaxEffect(string $effect) {
    return $this->setAjaxProperty('effect', $effect);
  }

  public function setAjaxEffectSped($speed) {
    return $this->setAjaxProperty('speed', $speed);
  }

  public function setAjaxEvent(string $event) {
    return $this->setAjaxProperty('event', $event);
  }

  public function setAjaxPreventEvent(string $event_to_prevent) {
    return $this->setAjaxProperty('prevent', $event_to_prevent);
  }

  public function setAjaxProgress(string $type, string $message = NULL, string $url = NULL, int $interval = NULL) {
    return $this->setAjaxProperty('progress', [
      'type' => $type,
      'message' => $message,
      'url' => $url,
      'interval' => $interval,
    ]);
  }

  public function setAjaxUrl(Url $url) {
    return $this->setAjaxProperty('url', $url);
  }

}
