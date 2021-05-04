<?php

namespace Drupal\js_webassert_test\Controller;

use Drupal\Core\Render\Markup;

class TestController {

  public function page() {
    $markup = <<<JS
<script>
var timesRun = 0;
var interval = setInterval(function() {
  timesRun += 1;
  // Clear the interval after 1.1 seconds as this is longer than the time
  // WebDriverCurlService would retry for if retries are enabled but shorter
  // than the 10 seconds JSWebAssert::waitForElementVisible() waits for.
  if (timesRun === 1100) {
    clearInterval(interval);
  }
  var p = document.createElement("p");
  var txt = document.createTextNode("New Text!! ".concat(timesRun));
  p.setAttribute("id", "test_text");
  p.appendChild(txt);

  var div = document.getElementById("test_container");
  div.replaceChild(p, div.childNodes[0]);
}, 1);
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
