<?php

declare(strict_types=1);

namespace Drupal\jswebassert_test\Controller;

use Drupal\Core\Render\Markup;

/**
 * Provides a test page for JavaScript assertions.
 */
class TestController {

  public function page() {
    $markup = <<<JS
<script>
var timesRun = 0;
var interval = setInterval(function() {
  timesRun += 1;
  // Clear the interval after ~1500ms seconds as this is shorter than the 10
  // seconds JSWebAssert::waitForElementVisible() waits for.
  if (timesRun === 3) {
    clearInterval(interval);
  }
  var p = document.createElement("p");
  var txt = document.createTextNode("New Text!! ".concat(timesRun));
  p.setAttribute("id", "test_text");
  p.appendChild(txt);

  var div = document.getElementById("test_container");
  div.replaceChild(p, div.childNodes[0]);
}, 500);
</script>
<div id="test_container">
  <p id="test_text"></p>
</div>
JS;
    return [
      // Javascript should not be injected into a page this way unless in test
      // code.
      '#markup' => Markup::create($markup),
    ];
  }

}
