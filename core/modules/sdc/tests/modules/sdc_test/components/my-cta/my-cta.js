((console, Drupal, once) => {
  Drupal.behaviors.cta = {
    attach: function attach(context) {
      const [cta] = once('component--my-cta', '.component--my-cta', context);
      if (cta) {
        console.log(cta);
      }
    },
  };
})(console, Drupal, once);
