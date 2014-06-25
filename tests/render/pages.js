// Render Multiple URLs to file

var system = require("system");
var sizes = [
        [320, 480],
        [320, 568],
        [600, 1024],
        [1024, 768],
        [1280, 800],
        [1440, 900]
    ],
    count, url, filename;

var arrayOfUrls = [ 
    {"url":"http://localhost:8080/", "action":"hover", "element":".rubriques > ul.nav > li:nth-child(2)"}, 
    {"url":"http://localhost:8080/agenda"}, 
    {"url":"http://localhost:8080/annuaire"},
    {"url":"http://localhost:8080/cirque-arlette-gruss-2/"}
];


var renderResponsive = function(urlObj, callback) {
    //console.log("JSON : " + JSON.stringify(urlObj));
    var url = urlObj.url;
   var filename = url.replace('http://','');
   filename = filename.replace(':','');
   filename = filename.replace(/\./g,'_');
   filename = filename.replace(/\//g,'_');
   count = 0;
   sizes.forEach(function(vpSize){
    count++;
    var page = new WebPage();
    page.viewportSize = { width: vpSize[0], height: vpSize[1] };
    console.log("url " + url);
    page.open(url, function (status) {
        console.log('status ' + status);
      if (status !== 'success') {
        console.log('Unable to load the address!');
        return callback();
      } else {
 
        window.setTimeout(function () {
            console.log("simulate " + urlObj.action + " on " + urlObj.element);
            if (urlObj.action!==null && urlObj.action!=="") {
                page.includeJs("http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js", function() {
                    page.evaluate(function() {
                        $(".rubriques > ul.nav > li:nth-child(2)").trigger("hover");
                    });
                });
            }
          page.render(filename+'_'+page.viewportSize.width+ 'x' + page.viewportSize.height +'.png');
          page.close();
          count--;
          if(count == 0){
            return callback();
          }
        }, 4000);
      }
    });
  });
}


/*
Render given urls
@param array of URLs to render
@param callbackPerUrl Function called after finishing each URL, including the last URL
@param callbackFinal Function called after finishing everything
*/
var RenderUrlsToFile = function(urls, callbackPerUrl, callbackFinal) {
    var next, retrieve, urlIndex;
    urlIndex = 0;
    //webpage = require("webpage");
    //page = null;
    //getFilename = function() {
    //    return "results/page-" + urlIndex + ".png";
    //};
    next = function(url) {
       // page.close();
        callbackPerUrl(url);
        return retrieve();
    };
    retrieve = function() {
        var url;
        if (urls.length > 0) {
            url = urls.shift();
            urlIndex++;
            
            return renderResponsive(url, function() {
                return next(url);
            });
            //page = webpage.create();
            //page.viewportSize = {
            //    width: 800,
            //    height: 600
            //};
            //page.settings.userAgent = "Phantom.js bot";
            //return page.open("http://" + url, function(status) {
                //var file;
                //file = getFilename();
                //if (status === "success") {
                  //  return window.setTimeout((function() {
                    //    page.render(file);
                      //  return next(status, url, file);
                    //}), 200);
                //} else {
                    //return next(url);
                //}
            //});
        } else {
            return callbackFinal();
        }
    };
    return retrieve();
};

//arrayOfUrls = null;

if (system.args.length > 1) {
    arrayOfUrls = Array.prototype.slice.call(system.args, 1);
} else {
    console.log("Usage: phantomjs pages.js [domain.name1, domain.name2, ...]");
}

RenderUrlsToFile(arrayOfUrls,(function(o) {
    //if (status !== "success") {
    //    return console.log("Unable to render '" + url + "'");
    //} else {
        return console.log("Rendered '" + o.url );
    //}
}), function() {
    return phantom.exit();
});


