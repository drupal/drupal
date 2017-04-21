<?php

// Should use placeholder in t() instead.
$x = t('Test') . ': ' . $variable;

// Concatenating markup is fine.
$x = '<p>' . t('Test') . '</p>';

class Test {

  public function doSomething() {
    return $this->t('This string should avoid backslashes for \'');
  }

}
