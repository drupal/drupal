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
    var steps = document.querySelectorAll('.task-list li');
    if (steps.length) {
      var branding = document.querySelector('#branding');
      var stepIndicator = document.createElement('div');
      stepIndicator.className = 'step-indicator';
      stepIndicator.innerHTML = findActiveStep(steps) + '/' + steps.length;
      branding.appendChild(stepIndicator);
    }
  }

  if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', installStepsSetup);
  }

})();
