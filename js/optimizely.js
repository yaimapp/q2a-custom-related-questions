function optSendEvent(index) {
  window['optimizely'] = window['optimizely'] || [];
  window.optimizely.push(["trackEvent", "item_"+index]);
}
