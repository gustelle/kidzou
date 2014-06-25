function messageHandler(a){switch(a.data.cmd){case"syncEvents":syncEvents(a.data);break;case"stop":self.close();break;default:self.postMessage("Unknown command")}}function syncEvents(a){diffEvents(JSON.parse(a.eventsData.page_events),JSON.parse(a.eventsData.local_events),{API_EVENTS_FETCH_URL:a.API_EVENTS_FETCH_URL})}function diffEvents(a,b,c){for(var d=[],e=0;e<a.length;e++){var f=parseInt(a[e].id),g=parseInt(a[e].event_timestamp),h=parseInt(a[e].connections_id),i=parseInt(a[e].connections_timestamp);if(0===b.length)d.pushUnique(f);else for(var j=0;j<b.length&&b[j];j++){var k=parseInt(b[j].id),l=parseInt(b[j].event_timestamp),m=parseInt(b[j].connections_id),n=parseInt(b[j].connections_timestamp);if(self.postMessage("local_events["+k+"] : "+l+" "+m+" "+n),l="undefined"==typeof b[j].event_timestamp?0:parseInt(b[j].event_timestamp),k===f){d.unset(f),0===l||0===n?(self.postMessage("local_events["+k+"] sans timestamp, à supprimer"+" / "+l),self.postMessage({cmd:"removeLocalEvent",id:k,event_timestamp:l,connections_id:m,connections_timestamp:n}),d.pushUnique(k)):(l!=g||m!=h||n!=i)&&(self.postMessage("removeLocalEvent "+k+"["+l+"]"),self.postMessage({cmd:"removeLocalEvent",id:k,event_timestamp:l,connections_id:m,connections_timestamp:n}),d.pushUnique(k));break}d.pushUnique(f)}}prefetchEvents(d,c)}function prefetchEvents(a,b){if(null!==a&&a.length>0){self.postMessage("prefetchEvents "+a.toString());var c=new XMLHttpRequest;c.open("GET",b.API_EVENTS_FETCH_URL+"?events_in="+a.toString(),!1),c.setRequestHeader("Content-Type","application/json;  charset=utf-8"),c.onreadystatechange=function(){4==c.readyState&&200==c.status&&self.postMessage({cmd:"storeLocalEvents",data:JSON.parse(c.responseText)})},c.send(null)}}this.addEventListener("message",messageHandler,!1),Array.prototype.pushUnique=function(a){return-1==this.indexOf(a)?(this.push(a),!0):!1},Array.prototype.unset=function(a){var b=this.indexOf(a);b>-1&&this.splice(b,1)};