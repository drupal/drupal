(($, once) => {
  const oldOnce = once;
  const newOnce = (id, selector, context) => {
    $(selector, context).once(id);
    return oldOnce(id, selector, context);
  };
  newOnce.remove = (id, selector, context) => {
    $(selector, context).removeOnce(id);
    return oldOnce.remove(id, selector, context);
  };
  newOnce.filter = once.filter;
  newOnce.find = once.find;
  // Replace the library.
  window.once = newOnce;
})(jQuery, once);
