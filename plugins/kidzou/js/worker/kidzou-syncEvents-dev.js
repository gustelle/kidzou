

this.addEventListener('message', messageHandler, false);

function messageHandler(event) {

	switch (event.data.cmd) {
		case 'syncEvents':
			syncEvents(event.data);
			break;
		case 'stop':
			self.close(); // Terminates the worker.
			break;	
		default:
			self.postMessage('Unknown command');
	}
}

function syncEvents(d) 
{	
    diffEvents( JSON.parse(d.eventsData.page_events), JSON.parse(d.eventsData.local_events), {API_EVENTS_FETCH_URL : d.API_EVENTS_FETCH_URL});
} 

//
//Parcourt de tous les events stockés dans le DOM (page_events)
//	-> pour chaque page_event, on vérifie qu'un localStorage event existe (local_events)
//		-> si son timestamp n'est pas le même, on invalide le local_event
//
//	-> si le local_event n'existe pas, on le pre-fetch pour le stocker dans le localStorage 
//
// on suppose que les local_events s'invalideront par eux même au bout de leur temps d'expiration (à checker)
// grâce au module local-cache
//
function diffEvents(page_events, local_events, options)
{
	var prefetchList = [];

	for (var i=0; i<page_events.length; i++)
	{
		var currentEvent_id 		= parseInt(page_events[i].id);
		var currentEvent_timestamp  = parseInt(page_events[i].event_timestamp);
		var currentEvent_connections_id			= parseInt(page_events[i].connections_id);
		var currentEvent_connections_timestamp	= parseInt(page_events[i].connections_timestamp);

		if (local_events.length===0)
			prefetchList.pushUnique(currentEvent_id);
		else 
		{
			for (var j=0; j<local_events.length; j++)
			{
				if (!local_events[j]) break;

				var localEvent_id 			= parseInt(local_events[j].id);
				var localEvent_timestamp 	= parseInt(local_events[j].event_timestamp);
				var localEvent_connections_id			= parseInt(local_events[j].connections_id);
				var localEvent_connections_timestamp	= parseInt(local_events[j].connections_timestamp);

				self.postMessage("local_events[" + localEvent_id + "] : " + localEvent_timestamp + " " + localEvent_connections_id + " " + localEvent_connections_timestamp);

				//reprise de l'historique : certains items du localStorage ne contenaient pas de timestamp
				//ils etaient constitués comme suit : event-113 au lieu de event-113-1234567890
				//ce check est déjà fait en amont mais par sécu...
				if (typeof local_events[j].event_timestamp==="undefined")
					localEvent_timestamp = 0;
				else
					localEvent_timestamp = parseInt(local_events[j].event_timestamp);
				
			 	//2.1 : les evenements locaux existent
				if (localEvent_id===currentEvent_id)
				{
					//suppression de la liste des evenements à prefecther si stocké précédemment
					prefetchList.unset(currentEvent_id);

					// suppression des entrées qui ne disposent pas de timestamp dans leur clé
					// (reprise de l'historique)
					// Attention : le localEvent_connections_id peut être = "0" si aucune fiche n'est attachée à l'evenement
					if (localEvent_timestamp===0 || 
						localEvent_connections_timestamp===0) 
					{
						self.postMessage("local_events[" + localEvent_id + "] sans timestamp, à supprimer" + " / " + localEvent_timestamp );
						//supprimer cette entrée locale, elle n'est pas cohérente
						self.postMessage({
							cmd : "removeLocalEvent", 
							id	: localEvent_id,
							event_timestamp	: localEvent_timestamp,
							connections_id 	: localEvent_connections_id,
							connections_timestamp : localEvent_connections_timestamp
						});
						prefetchList.pushUnique(localEvent_id);
					}
					else 
					{
						//supprimer cet evenement local, il n'est plus d'actualité
						//et le prefetcher
						if (localEvent_timestamp != currentEvent_timestamp || 
							localEvent_connections_id != currentEvent_connections_id || 
							localEvent_connections_timestamp != currentEvent_connections_timestamp) {
							
							self.postMessage("removeLocalEvent " + localEvent_id + "[" + localEvent_timestamp + "]");
							self.postMessage({
								cmd : "removeLocalEvent", 
								id	: localEvent_id,
								event_timestamp : localEvent_timestamp,
								connections_id 	: localEvent_connections_id,
								connections_timestamp : localEvent_connections_timestamp
							});
							prefetchList.pushUnique(localEvent_id);
						}
					}
					break;
				}
				//2.2 l'evenement n'existe pas en local, on le met dans la liste des evenements à prefetcher
				//cette liste sera retournée dans prefetchEvents pour stockage dans le localStorage
				else
				{
					prefetchList.pushUnique(currentEvent_id);
				}
			}
		}
		
	}

	prefetchEvents(prefetchList, options);
}

function prefetchEvents(list, options)
{
	if (list!==null && list.length>0)
	{
		self.postMessage("prefetchEvents " + list.toString());
		// prefetcher les events qui n'existent pas
		var req 	 = new XMLHttpRequest();
	    req.open('GET', options.API_EVENTS_FETCH_URL + "?events_in=" + list.toString(), false);
	    req.setRequestHeader('Content-Type', 'application/json;  charset=utf-8');
	    req.onreadystatechange = function () {
	        if (req.readyState == 4 && req.status == 200) { 

	        	//renvoie pour stockage dans le localStorage
	        	self.postMessage({
					cmd  : "storeLocalEvents", 
					data : JSON.parse(req.responseText) 
				});
	        	
	        }    
	    }; 
	    req.send(null);
	}
	
}

Array.prototype.pushUnique = function (item){
    if(this.indexOf(item) == -1) {
        this.push(item);
        return true;
    }
    return false;
};

Array.prototype.unset = function (item){
    var index = this.indexOf(item);
    if(index > -1) {
        this.splice(index,1);
    }
};

