((Drupal, once) => {
  Drupal.behaviors.htmx_test = {
    attach(context, settings) {
      once('htmx-init', '.ajax-content', context).forEach((el) => {
        el.innerText = 'initialized';
      });
    },
    detach(context, settings, trigger) {
      once.remove('htmx-init', '.ajax-content', context).forEach((el) => {
        el.remove();
      });
    },
  };
})(Drupal, once);
