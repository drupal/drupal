Poltergeist.MouseEventFailed = (function (_super) {
  __extends(MouseEventFailed, _super);

  function MouseEventFailed(eventName, selector, position) {
    this.eventName = eventName;
    this.selector = selector;
    this.position = position;
  }

  MouseEventFailed.prototype.name = "Poltergeist.MouseEventFailed";

  MouseEventFailed.prototype.args = function () {
    return [this.eventName, this.selector, this.position];
  };

  return MouseEventFailed;

})(Poltergeist.Error);
