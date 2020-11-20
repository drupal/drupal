Poltergeist.InvalidSelector = (function (_super) {
  __extends(InvalidSelector, _super);

  function InvalidSelector(method, selector) {
    this.method = method;
    this.selector = selector;
  }

  InvalidSelector.prototype.name = "Poltergeist.InvalidSelector";

  InvalidSelector.prototype.args = function () {
    return [this.method, this.selector];
  };

  return InvalidSelector;

})(Poltergeist.Error);
