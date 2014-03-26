(function () {

  "use strict";

  function findActiveStep (steps) {
    for (var i = 0; i < steps.length; i++) {
      if (steps[i].className === 'active') {
        return i + 1;
      }
    }
    // The final "Finished" step is never "active".
    if (steps[steps.length - 1].className === 'done') {
      return steps.length;
    }
    return 0;
  }

  function installStepsSetup () {
    var steps = document.querySelectorAll('.install-task-list li');
    if (steps.length) {
      var header = document.querySelector('header[role="banner"]');
      var stepIndicator = document.createElement('div');
      stepIndicator.className = 'step-indicator';
      stepIndicator.innerHTML = findActiveStep(steps) + '/' + steps.length;
      header.appendChild(stepIndicator);
    }
  }

  if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', installStepsSetup);
  }

})();
