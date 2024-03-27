(function (Drupal, once) {
  Drupal.behaviors.button = {
    attach: function attach(context) {
      let counter = 0;
      const [button] = once(
        'component--my-button',
        '[data-component-id="sdc_test:my-button"]',
        context,
      );
      if (!button) {
        return;
      }
      button.addEventListener('click', function (event) {
        event.preventDefault();
        counter++;
        this.innerHTML = `${this.innerHTML.replace(
          / \([0-9]*\)$/,
          '',
        )} (${counter})`;
      });
    },
  };
})(Drupal, once);
