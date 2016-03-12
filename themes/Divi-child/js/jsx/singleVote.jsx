/** 
 *
 * Il faut monter le composant <Vote> dans le DOM pour qu'il puisse updater ses valeurs
 * car componentDidMount n'est pas appelé sur le serveur
 */
ReactDOM.render(
		<Vote context='single' 
              	ID={singleVote_jsvars.ID} 
				currentUserId={singleVote_jsvars.current_user_id}
              	apis={singleVote_jsvars.apis}  /> , 
		document.querySelector('#voteComponent')
	);