((Drupal, once) => {
  Drupal.behaviors.htmx_test = {
    attach(context, settings) {
      once('htmx-init', '.ajax-content', context).forEach((el) => {
        el.innerText = 'initialized';
      });
      document.body.dataset.htmxBehaviorTest = 'attached';
    },
    detach(context, settings, trigger) {
      once.remove('htmx-init', '.ajax-content', context);
      document.body.dataset.htmxBehaviorTest = 'detached';
    },
  };
})(Drupal, once);
