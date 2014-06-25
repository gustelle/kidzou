

this.addEventListener('message', messageHandler, false);

function messageHandler(event) {

	switch (event.data.cmd) {
		case 'refreshVotesCount':
			refreshVotesCount(event.data);
			break;
		case 'syncVotes':
			syncVotes(event.data);
			break;
		case 'stop':
			self.close(); // Terminates the worker.
			break;	
		default:
			self.postMessage('Unknown command');
	}
}

function refreshVotesCount(d) 
{	
    getVotesCount( JSON.parse(d.votesData.posts_in), {
    	API_VOTES_STATUS_URL   : d.API_VOTES_STATUS_URL
    });
} 

function syncVotes(d) 
{	
    diffVotes( JSON.parse(d.votesData.localVotes), d.votesData.user_hash,  {
    	API_USERVOTES_URL   : d.API_USERVOTES_URL
    });
} 

function getVotesCount(posts_in, options)
{
	
	var req 	 = new XMLHttpRequest();
    req.open('GET', options.API_VOTES_STATUS_URL + "?posts_in=" + JSON.stringify(posts_in) , false);
    req.setRequestHeader('Content-Type', 'application/json;  charset=utf-8');
    req.onreadystatechange = function () {
        if (req.readyState == 4 && req.status == 200) { 

        	//renvoie pour stockage dans le localStorage
        	self.postMessage({
				cmd  : "setVotesCount", 
				response : JSON.parse(req.responseText) 
			});
        	
        }    
    }; 
    req.send(null);
}


/* parcours des votes locaux
 * - si les votes locaux n'existent pas, récupération des votes sur le serveur et stockage en local
 * - si les votes locaux existent, on ne fait rien 
 *
 */
function diffVotes(localVotes, user_hash, options)
{
	
	if (localVotes===null || localVotes.length===0) 
	{
		self.postMessage("localVotes null pour user_hash " + user_hash);

		//assurer de ne pas passer la valeur "null" dans la requete
		//renvoyer dans ce cas une chaine vide
		//cela peut arriver à cause du legacy ou lorsque le user est identifié
		if (user_hash===null || user_hash==="undefined" ) {
			user_hash="";
		}

		var req 	 = new XMLHttpRequest();
	    req.open('GET', options.API_USERVOTES_URL + "?user_hash=" + user_hash, false);
	    req.setRequestHeader('Content-Type', 'application/json;  charset=utf-8');
	    req.onreadystatechange = function () {
	        if (req.readyState == 4 && req.status == 200) { 

	        	//renvoie pour stockage dans le localStorage
	        	self.postMessage({
					cmd  : "storeLocalVotes", 
					response : JSON.parse(req.responseText) 
				});
	        	
	        }    
	    }; 
	    req.send(null);
	}
	else
	{
		self.postMessage({
			cmd  : "mapLocalVotes",
			response : localVotes
		});
	}

}




