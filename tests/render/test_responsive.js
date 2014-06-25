/*
    requires: phantomjs
    usage: phantomjs screenshot.js URL
*/
var sizes = [
        [320, 480],
        [320, 568],
        [600, 1024],
        [1024, 768],
        [1280, 800],
        [1440, 900]
    ],
    count, url, filename;
 
if (phantom.args.length < 1) {
    console.log('Usage: screenshot.js URL');
    phantom.exit();
} else {
   url = phantom.args[0];
   var filename = url.replace('http://','');
   filename = filename.replace('www.','');
   filename = filename.replace(/\./g,'_');
   filename = filename.replace(/\//g,'_');
   count = 0;
   sizes.forEach(function(vpSize){
    count++;
    var page = new WebPage();
    page.viewportSize = { width: vpSize[0], height: vpSize[1] };
    page.open(url, function (status) {
      if (status !== 'success') {
        console.log('Unable to load the address!');
      } else {
 
        window.setTimeout(function () {
          page.render(filename+'_'+page.viewportSize.width+ 'x' + page.viewportSize.height +'.png');
          page.close();
          count--;
          if(count == 0){
            phantom.exit();
          }
        }, 2000);
      }
    });
  });
}