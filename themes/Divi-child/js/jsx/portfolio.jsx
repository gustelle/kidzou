
/**
 *
 * Helper qui permet d'exposer les <Vote /> à l'exterieur, notamment utile dans les single pour les boites de Notif
 */
var kidzouVoteModule = (function(){

  var components = [];

  function addComponent(_comp) {
    components.push(_comp);
  }

  function getComponents() {
    return components;
  }

  return {
    registerComponent : addComponent,
    getComponents : getComponents
  }

}());
 
/**
 * 
 * Quelques fonctions support pour la suite
 */
var voteSupportModule = (function(storageSupport) {
  /**
  * permet d'identifier un user anonyme
  * le hash est fourni par le serveur, voir hash_anonymous() dans kidzou_utils
  **/
  function setUserHash (hash) {

    if (hash===null || hash==="" || hash==="undefined") //prevention des cas ou le user est identifié : son user_hash est null
      return;

    if (getUserHash()===null || getUserHash()==="" || getUserHash()==="undefined") {
      // logger.debug("setUserHash : " + hash);
      storageSupport.setLocal("user_hash", hash);
    }
  }

  /**
  * permet d'identifier un user anonyme
  * le hash est fourni par le serveur, voir hash_anonymous() dans kidzou_utils
  **/
  function getUserHash ( ) {

    if (storageSupport.getLocal("user_hash")==="undefined") { //pour le legacy
      // logger.debug("user_hash undefined" );
      storageSupport.removeLocal("user_hash");
    }

    return storageSupport.getLocal("user_hash");
  }

  function removeLocalData (key) {
    storageSupport.removeLocalData(key);
  }

  return {
    getUserHash : getUserHash,
    setUserHash : setUserHash,
    removeLocalData : removeLocalData
  };

}(window.storageSupport));

/**
 * Composant de Vote, réutilisé dans plusieurs contextes dont le <PostPreview />
 *
 */
var Vote = React.createClass({

  getInitialState: function() {

    return {
      votes       : 0,
      isLoaded    : false, //marker pour le rafraichissement des votes au départ
      voted       : false, //le user a t il voté ce post ?
      display     : 'none'
    };
  },

  /**
   * dans le cas d'un single, ce composant est indépendant du <Portfolio />
   * ainsi le nombre de votes n'est pas mis à jour par le <Portfolio /> mais à l'intérieur du composant
   *
   */
  componentDidMount: function() {
    
    var self = this;

    if (self.props.context=='single') {

      kidzouVoteModule.registerComponent(this);

      //recupération des votes pour ce post
      jQuery.get(self.props.apis.getVotes + '?post_id=' + self.props.ID, function (result) {
        self.setState({
          votes : result.votes
        });

        //le user a-t-il voté ce post ?
        jQuery.get(self.props.apis.isVotedByUser + '?post_id=' + self.props.ID + '&user_hash=' + voteSupportModule.getUserHash(), function (res) {
          self.setState({
            voted : res.voted,
            isLoaded : true
          }, function() {
            //animer l'arrivée du block de vote
            TweenMax.fromTo('.popMe', 1.5,{scale:0.1},{scale:1,ease:Elastic.easeOut, force3D: true});
          });
        });
      
      }); 
    }
  },

  handleVoteAction: function(e, x) {

    e.preventDefault(); //stopper le click
    this.voteUpOrDown('Recommandation');

  },

  voteUpOrDown: function(_context) {
    var self = this;
    var upOrdown = '+1';
    if (self.state.voted)
      upOrdown = '-1';

    if (window.kidzouTracker) kidzouTracker.trackEvent(_context, upOrdown, self.props.slug , self.props.currentUserId);

    if (self.state.voted) 
      self.doWithdraw();
    else
      self.doVote();

    if (self.props.context=='single') TweenMax.fromTo('.popMe', 1.5,{scale:0.1},{scale:1,ease:Elastic.easeOut, force3D: true});

  },

  doVote: function() {

    var self = this;
    if (self.state.voted) return;

    var _id = self.props.ID;

    //update the UI immediatly and proceed to the vote in back-office
    var count = parseInt(self.state.votes)+1;
    self.setState({
      voted : true,
      votes : count
    });

    //get nonce for voting and proceed to vote
    jQuery.get(self.props.apis.getNonce, {controller: 'vote', method: 'up'}, function(data){

      if (data!==null) {
           var nonce =  data.nonce;
           //vote with the nonce
           jQuery.get(self.props.apis.voteUp, {
              post_id: _id, 
              nonce: nonce,
              user_hash : voteSupportModule.getUserHash()
            }, function(data) {
              //cas des users loggués, le user_hash n'est aps renvoyé
              if (data.user_hash!==null && data.user_hash!=="undefined")
                voteSupportModule.setUserHash(data.user_hash); //pour reuntilisation ultérieure
              
              voteSupportModule.removeLocalData("voted"); //pour rafraichissement à la prochaine requete
            }); 
        }

    });  
  },

  //retrait du vote ('Je ne recommande plus')
  doWithdraw : function() {

    var self = this;
    if (!self.state.voted) return;

    var _id = self.props.ID;

    //update the UI immediatly and proceed to the withdraw in back-office
    var count = parseInt(self.state.votes)-1;
    self.setState({
      voted : false,
      votes : count
    });

    //get nonce for voting and proceed to vote
    jQuery.get(self.props.apis.getNonce, {controller: 'vote', method: 'down'}, function(data){

        var nonce =  data.nonce;
         //vote with the nonce
         jQuery.get(self.props.apis.voteDown, {
            post_id: _id, 
            nonce: nonce,
            user_hash : voteSupportModule.getUserHash()
          }, function(data) {
            //cas des users loggués, le user_hash n'est aps renvoyé
            if (data.user_hash!==null && data.user_hash!=="undefined")
              voteSupportModule.setUserHash(data.user_hash); //pour reuntilisation ultérieure
            
            voteSupportModule.removeLocalData("voted"); //pour rafraichissement à la prochaine requete
          });

    });
  },

  render: function () {

    var self = this;

    var votedClass = classNames( 'popMe', {
        'fa fa-heart' : self.state.voted ,
        'fa fa-heart-o' : !self.state.voted
    });

    var spanClass = classNames( 'voteBlock', {
        'hovertext' : self.props.context=='portfolio' ,
        'font-2x' : self.props.featured || self.props.context=='single',
    });

    /**
     * Pour les 'single', pas de souci on affiche direct
     * Mais pour les Portfolio, on attend que les votes soient raffraichis avant d'afficher pour une meilleure UX
     */
     var _display = (self.props.context=='single' ? 'inline' : self.state.display);

    return (
      <span style={{display: _display}} className={spanClass} onClick={self.handleVoteAction}>
      {
       self.state.isLoaded && 
        <span className='vote'>
          <i className={votedClass}></i>
          <span className='popMe'>{self.state.votes}</span>
        </span>
      }
      </span>
    );
  }
  
});

/**
 * Préview d'un post 
 *
 */
var PostPreview = React.createClass({
  
  getInitialState: function() {

    //si la prop data.__html est positonnée, il s'agit d'un contenu qui va être injecté en HTML
    //exemple d'une pub
    if (typeof this.props.data.__html!=='undefined') return {};
      
    var self = this;
    var hasLocation   = self.props.data.location.location_name!=='';
    var isTypeEvent   = typeof self.props.data.dates=='undefined' ? false : (typeof self.props.data.dates.start_date !== 'undefined' && self.props.data.dates.start_date!=='');
    var noEndDate     = typeof self.props.data.dates=='undefined' ? true : (typeof self.props.data.dates.end_date == 'undefined' || self.props.data.dates.end_date=='');

    return {
      hasLocation : hasLocation,
      isTypeEvent : isTypeEvent,
      startDate   : isTypeEvent ? moment(self.props.data.dates.start_date, 'YYYY-MM-DD HH:mm:ss') : '',
      endDate     : isTypeEvent ? moment(self.props.data.dates.end_date, 'YYYY-MM-DD HH:mm:ss') : '',
      singleDay   : (isTypeEvent && noEndDate) || (isTypeEvent && self.props.data.dates.start_date===self.props.data.dates.end_date),
      isLoaded    : false, //marker pour le rafraichissement des votes au départ
    };
  },
  
  
  render: function () {
    
    var self = this;

    return (
      <div className='preview'>
        {
         (typeof self.props.data.__html!=='undefined') &&
         <div className="et_pb_portfolio_item kz_portfolio_item ad" dangerouslySetInnerHTML={{__html: self.props.data.__html}} data-content="Publicite"></div>
        }
        {
         (typeof self.props.data.__html=='undefined') &&
          <div>
            {
              self.props.data.featured && 
              <div className="et_pb_portfolio_item kz_portfolio_item kz_portfolio_item_featured">
                <div className='kz_portfolio_featured_hover'>
                  <a href={self.props.data.permalink}>
                    
                    {
                      self.props.render_votes && 
                      <Vote context='portfolio' 
                          featured={true} 
                          ref={(c) => this._voteComponent = c} 
                          ID={self.props.data.ID} 
                          slug={self.props.data.slug}
                          apis={self.props.apis} 
                          currentUserId={self.props.currentUserId} />  
                    }
                    
                    <h2>{self.props.data.title}</h2>
                  </a>
                  {
                    self.props.show_categories && 
                    <div dangerouslySetInnerHTML={{__html: self.props.data.post_meta}}></div>
                  }
                  {
                    self.state.isTypeEvent &&
                    <div className="portfolio_meta">
                      <i className="fa fa-calendar"></i>
                      { self.state.singleDay && 
                        <span>Le {moment(self.state.startDate).format('DD MMM')}</span>
                      }
                      { !self.state.singleDay && 
                        <span>Du {moment(self.state.startDate).format('DD MMM')} au {moment(self.state.endDate).format('DD MMM')}</span>
                      }
                    </div>
                  }
                  {
                    self.state.hasLocation && 
                    <div className="portfolio_meta">
                      <i className="fa fa-map-marker"></i>
                      {self.props.data.location.location_city}
                    </div>
                  }

                </div>
                
                <a href={self.props.data.permalink}><span dangerouslySetInnerHTML={{__html: self.props.data.thumbnail}}></span></a>
              </div>   
            }
            {
              !self.props.data.featured && 
              <div className="et_pb_portfolio_item kz_portfolio_item">
                <a href={self.props.data.permalink}>
                  <span className='et_portfolio_image'>

                    {
                      self.props.render_votes && 
                      <Vote context='portfolio' 
                          ref={(c) => this._voteComponent = c} 
                          ID={self.props.data.ID} 
                          slug={self.props.data.slug} 
                          apis={self.props.apis} 
                          currentUserId={self.props.currentUserId} />
                    }

                    <span dangerouslySetInnerHTML={{__html: self.props.data.thumbnail}}></span>
                    <span className='et_overlay'></span>
                  </span>
                </a>

                <h2><a href={self.props.data.permalink}>{self.props.data.title}</a></h2>
                {
                  self.props.show_categories && 
                  <p className="post-meta" dangerouslySetInnerHTML={{__html: self.props.data.terms}}></p>
                }
                
                {
                  self.state.isTypeEvent &&
                  <div className="portfolio_meta">
                    <i className="fa fa-calendar"></i>
                    { self.state.singleDay && 
                      <span>Le {moment(self.state.startDate).format('DD MMM')}</span>
                    }
                    { !self.state.singleDay && 
                      <span>Du {moment(self.state.startDate).format('DD MMM')} au {moment(self.state.endDate).format('DD MMM')}</span>
                    }
                  </div>
                }

                {
                  self.state.hasLocation && 
                  <div className="portfolio_meta">
                    <i className="fa fa-map-marker"></i>
                    {self.props.data.location.location_city}
                  </div>
                }
              </div>
            }
            <div style={{display:'none'}} dangerouslySetInnerHTML={{__html: self.props.data.excerpt}}></div>
          </div>
        }
      </div>
    );
  },

  setVotesCount: function (_count) {
    var self = this;
    self._voteComponent.setState({
      votes : _count,
      isLoaded : true,
      display : 'inline' 
    });
  },

  setVoted: function(_bool) {
    var self = this;
    self._voteComponent.setState({
      voted : _bool
    });
  }

});



/**
 * Portfolio de <PostPreview />
 *
 */
var Portfolio = React.createClass({

  componentDidMount: function() {
    
    var self = this;

    if (self.props.render_votes) {

      var post_ids = self.props.posts.map(function(row) {
        return row.ID;
      });

      //recupération des votes pour les posts
      jQuery.get(self.props.apis.getVotes, { posts_in : post_ids}, function (result) {
        var votesData = result.status;
        for (var i=0, iLen=votesData.length; i<iLen; i++) { 
          self.refs[votesData[i].id].setVotesCount(votesData[i].votes);
        }

        //recupération des votes du user
        jQuery.get(self.props.apis.userVotes + '?user_hash=' + voteSupportModule.getUserHash(), function (res) {
          var userVotes = res.voted;
          for (var j=0, jLen=userVotes.length; j<jLen; j++) { 
            //il est vraisemblable que tous les posts votés par le user ne soient pas sur la page...
            if (typeof self.refs[userVotes[j].id]!=='undefined') {
              self.refs[userVotes[j].id].setVoted(true);
            }
          }
        });
      }); 
    }

    if (self.props.animate) {
      //Animation speciale sur les featured pour faire un "waoo"
      TweenMax.staggerFrom('.kz_portfolio_item_featured', 2, {scale:0.2, opacity:0, delay:0.5, ease:Elastic.easeOut, force3D:true}, 0.2);
    } 

  },

  render: function () {

    var self = this;

    //pour les pubs
    var ad      = self.props.ad;
    var showAd  = self.props.show_ad;

    //le portfolio en lui même
    var list    = self.props.posts.map(function (row) {

      return <PostPreview data={row} 
                          ref={row.ID} 
                          apis={self.props.apis} 
                          currentUserId={self.props.current_user_id}
                          key={row.ID}
                          render_votes={self.props.render_votes}
                          show_categories={self.props.show_categories} />;
    });

    //inserer la pub en 3e position sauf si le 1er est featured
    //dans le cas d'un premier post featured, on insert la pub en 2e
    if (showAd && ad!=='') {
      var index = list[0].props.data.featured ? 1 : 2;
      var insertedPost = <PostPreview data={{__html: ad}} /> 
      list.splice( index, 0, insertedPost );
    }

    return (
      <div>{list}</div>
    );
  }
  
});



  






