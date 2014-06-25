function messageHandler(a){switch(a.data.cmd){case"refreshVotesCount":refreshVotesCount(a.data);break;case"syncVotes":syncVotes(a.data);break;case"stop":self.close();break;default:self.postMessage("Unknown command")}}function refreshVotesCount(a){getVotesCount(JSON.parse(a.votesData.posts_in),{API_VOTES_STATUS_URL:a.API_VOTES_STATUS_URL})}function syncVotes(a){diffVotes(JSON.parse(a.votesData.localVotes),a.votesData.user_hash,{API_USERVOTES_URL:a.API_USERVOTES_URL})}function getVotesCount(a,b){var c=new XMLHttpRequest;c.open("GET",b.API_VOTES_STATUS_URL+"?posts_in="+JSON.stringify(a),!1),c.setRequestHeader("Content-Type","application/json;  charset=utf-8"),c.onreadystatechange=function(){4==c.readyState&&200==c.status&&self.postMessage({cmd:"setVotesCount",response:JSON.parse(c.responseText)})},c.send(null)}function diffVotes(a,b,c){if(null===a||0===a.length){self.postMessage("localVotes null pour user_hash "+b),(null===b||"undefined"===b)&&(b="");var d=new XMLHttpRequest;d.open("GET",c.API_USERVOTES_URL+"?user_hash="+b,!1),d.setRequestHeader("Content-Type","application/json;  charset=utf-8"),d.onreadystatechange=function(){4==d.readyState&&200==d.status&&self.postMessage({cmd:"storeLocalVotes",response:JSON.parse(d.responseText)})},d.send(null)}else self.postMessage({cmd:"mapLocalVotes",response:a})}this.addEventListener("message",messageHandler,!1);