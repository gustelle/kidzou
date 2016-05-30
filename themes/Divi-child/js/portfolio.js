"use strict";

/*
 *
 * Helper qui permet d'exposer les <Vote /> à l'exterieur, notamment utile dans les single pour les boites de Notif
 */
var kidzouVoteModule = function () {

  var components = [];

  function addComponent(_comp) {
    components.push(_comp);
  }

  function getComponents() {
    return components;
  }

  return {
    registerComponent: addComponent,
    getComponents: getComponents
  };
}();

/**
 * 
 * Quelques fonctions support pour la suite
 */
var voteSupportModule = function (storageSupport) {
  /**
  * permet d'identifier un user anonyme
  * le hash est fourni par le serveur, voir hash_anonymous() dans kidzou_utils
  **/
  function setUserHash(hash) {

    if (hash === null || hash === "" || hash === "undefined") //prevention des cas ou le user est identifié : son user_hash est null
      return;

    if (getUserHash() === null || getUserHash() === "" || getUserHash() === "undefined") {
      // logger.debug("setUserHash : " + hash);
      storageSupport.setLocal("user_hash", hash);
    }
  }

  /**
  * permet d'identifier un user anonyme
  * le hash est fourni par le serveur, voir hash_anonymous() dans kidzou_utils
  **/
  function getUserHash() {

    if (storageSupport.getLocal("user_hash") === "undefined") {
      //pour le legacy
      // logger.debug("user_hash undefined" );
      storageSupport.removeLocal("user_hash");
    }

    return storageSupport.getLocal("user_hash");
  }

  function removeLocalData(key) {
    storageSupport.removeLocalData(key);
  }

  return {
    getUserHash: getUserHash,
    setUserHash: setUserHash,
    removeLocalData: removeLocalData
  };
}(window.storageSupport);

/**
 * Composant de Vote, réutilisé dans plusieurs contextes dont le <PostPreview />
 *
 */
var Vote = React.createClass({
  displayName: "Vote",

  getInitialState: function getInitialState() {

    return {
      votes: 0,
      isLoaded: false, //marker pour le rafraichissement des votes au départ
      voted: false, //le user a t il voté ce post ?
      display: 'none'
    };
  },

  /**
   * dans le cas d'un single, ce composant est indépendant du <Portfolio />
   * ainsi le nombre de votes n'est pas mis à jour par le <Portfolio /> mais à l'intérieur du composant
   * 
   * Cette fonction est donc a appeler sur le composant depuis l'exterieur
   *
   */
  componentWillMount: function componentWillMount() {

    var self = this;

    if (self.props.context == 'single') {

      // console.debug('Vote updateComponent');

      kidzouVoteModule.registerComponent(this);

      //recupération des votes pour ce post
      jQuery.getJSON(self.props.apis.getVotes + '?post_id=' + self.props.ID, function (result) {
        self.setState({
          votes: result.votes
        });

        //le user a-t-il voté ce post ?
        jQuery.getJSON(self.props.apis.isVotedByUser + '?post_id=' + self.props.ID + '&user_hash=' + voteSupportModule.getUserHash(), function (res) {
          self.setState({
            voted: res.voted,
            isLoaded: true
          }, function () {
            //animer l'arrivée du block de vote
            TweenMax.fromTo('.popMe', 1.5, { scale: 0.1 }, { scale: 1, ease: Elastic.easeOut, force3D: true });
          });
        });
      });
    }
  },

  handleVoteAction: function handleVoteAction(e, x) {

    e.preventDefault(); //stopper le click
    this.voteUpOrDown('Recommandation');

    //fermer la boite de notification pour ne pas proposer de voter
    if (window.kidzouNotifier) {
      kidzouNotifier.close();
    }
  },

  voteUpOrDown: function voteUpOrDown(_context) {
    var self = this;
    var upOrdown = '+1';
    if (self.state.voted) upOrdown = '-1';

    if (window.kidzouTracker) kidzouTracker.trackEvent(_context, upOrdown, self.props.slug, self.props.currentUserId);

    if (self.state.voted) self.doWithdraw();else self.doVote();

    if (self.props.context == 'single') TweenMax.fromTo('.popMe', 1.5, { scale: 0.1 }, { scale: 1, ease: Elastic.easeOut, force3D: true });
  },

  doVote: function doVote() {

    var self = this;
    if (self.state.voted) return;

    var _id = self.props.ID;

    //update the UI immediatly and proceed to the vote in back-office
    var count = parseInt(self.state.votes) + 1;
    self.setState({
      voted: true,
      votes: count
    });

    //get nonce for voting and proceed to vote
    jQuery.getJSON(self.props.apis.getNonce, { controller: 'vote', method: 'up' }, function (data) {

      if (data !== null) {
        var nonce = data.nonce;
        //vote with the nonce
        jQuery.getJSON(self.props.apis.voteUp, {
          post_id: _id,
          nonce: nonce,
          user_hash: voteSupportModule.getUserHash()
        }, function (data) {
          //cas des users loggués, le user_hash n'est aps renvoyé
          if (data.user_hash !== null && data.user_hash !== "undefined") voteSupportModule.setUserHash(data.user_hash); //pour reuntilisation ultérieure

          voteSupportModule.removeLocalData("voted"); //pour rafraichissement à la prochaine requete
        });
      }
    });
  },

  //retrait du vote ('Je ne recommande plus')
  doWithdraw: function doWithdraw() {

    var self = this;
    if (!self.state.voted) return;

    var _id = self.props.ID;

    //update the UI immediatly and proceed to the withdraw in back-office
    var count = parseInt(self.state.votes) - 1;
    self.setState({
      voted: false,
      votes: count
    });

    //get nonce for voting and proceed to vote
    jQuery.getJSON(self.props.apis.getNonce, { controller: 'vote', method: 'down' }, function (data) {

      var nonce = data.nonce;
      //vote with the nonce
      jQuery.getJSON(self.props.apis.voteDown, {
        post_id: _id,
        nonce: nonce,
        user_hash: voteSupportModule.getUserHash()
      }, function (data) {
        //cas des users loggués, le user_hash n'est aps renvoyé
        if (data.user_hash !== null && data.user_hash !== "undefined") voteSupportModule.setUserHash(data.user_hash); //pour reuntilisation ultérieure

        voteSupportModule.removeLocalData("voted"); //pour rafraichissement à la prochaine requete
      });
    });
  },

  render: function render() {

    var self = this;

    var votedClass = classNames('popMe', {
      'fa fa-heart': self.state.voted,
      'fa fa-heart-o': !self.state.voted
    });

    var spanClass = classNames('voteBlock', {
      'hovertext': self.props.context == 'portfolio',
      'font-2x': self.props.featured || self.props.context == 'single'
    });

    /**
     * Pour les 'single', pas de souci on affiche direct
     * Mais pour les Portfolio, on attend que les votes soient raffraichis avant d'afficher pour une meilleure UX
     */
    var _display = self.props.context == 'single' ? 'inline' : self.state.display;

    return React.createElement(
      "span",
      { style: { display: _display }, className: spanClass, onClick: self.handleVoteAction },
      self.state.isLoaded && React.createElement(
        "span",
        { className: "vote" },
        React.createElement("i", { className: votedClass }),
        React.createElement(
          "span",
          { className: "popMe" },
          self.state.votes
        )
      )
    );
  }

});

/**
 * Préview d'un post 
 *
 */
var PostPreview = React.createClass({
  displayName: "PostPreview",

  getInitialState: function getInitialState() {

    //si la prop data.__html est positonnée, il s'agit d'un contenu qui va être injecté en HTML
    //exemple d'une pub
    if (typeof this.props.data.__html !== 'undefined') return {};

    var self = this;
    var hasLocation = self.props.data.location.location_name !== '';
    var isTypeEvent = typeof self.props.data.dates == 'undefined' ? false : typeof self.props.data.dates.start_date !== 'undefined' && self.props.data.dates.start_date !== '';
    var noEndDate = typeof self.props.data.dates == 'undefined' ? true : typeof self.props.data.dates.end_date == 'undefined' || self.props.data.dates.end_date == '';

    return {
      hasLocation: hasLocation,
      isTypeEvent: isTypeEvent,
      startDate: isTypeEvent ? moment(self.props.data.dates.start_date, 'YYYY-MM-DD HH:mm:ss') : '',
      endDate: isTypeEvent ? moment(self.props.data.dates.end_date, 'YYYY-MM-DD HH:mm:ss') : '',
      singleDay: isTypeEvent && noEndDate || isTypeEvent && moment(self.props.data.dates.start_date, 'YYYY-MM-DD HH:mm:ss').dayOfYear() === moment(self.props.data.dates.end_date, 'YYYY-MM-DD HH:mm:ss').dayOfYear(),
      isLoaded: false };
  },

  //marker pour le rafraichissement des votes au départ
  render: function render() {
    var _this = this;

    var self = this;

    return React.createElement(
      "div",
      { className: "preview" },
      typeof self.props.data.__html !== 'undefined' && React.createElement("div", { className: "et_pb_portfolio_item kz_portfolio_item ad", dangerouslySetInnerHTML: { __html: self.props.data.__html }, "data-content": "Publicite" }),
      typeof self.props.data.__html == 'undefined' && React.createElement(
        "div",
        null,
        self.props.data.featured && React.createElement(
          "div",
          { className: "et_pb_portfolio_item kz_portfolio_item kz_portfolio_item_featured" },
          React.createElement(
            "div",
            { className: "kz_portfolio_featured_hover" },
            React.createElement(
              "a",
              { href: self.props.data.permalink },
              self.props.render_votes && React.createElement(Vote, { context: "portfolio",
                featured: true,
                ref: function ref(c) {
                  return _this._voteComponent = c;
                },
                ID: self.props.data.ID,
                slug: self.props.data.slug,
                apis: self.props.apis,
                currentUserId: self.props.currentUserId }),
              React.createElement(
                "h2",
                null,
                self.props.data.title
              )
            ),
            self.props.show_categories && React.createElement("div", { dangerouslySetInnerHTML: { __html: self.props.data.post_meta } }),
            self.state.isTypeEvent && React.createElement(
              "div",
              { className: "portfolio_meta" },
              React.createElement("i", { className: "fa fa-calendar" }),
              self.state.singleDay && React.createElement(
                "span",
                null,
                "Le ",
                moment(self.state.startDate).format('DD MMM')
              ),
              !self.state.singleDay && React.createElement(
                "span",
                null,
                "Du ",
                moment(self.state.startDate).format('DD MMM'),
                " au ",
                moment(self.state.endDate).format('DD MMM')
              )
            ),
            self.state.hasLocation && React.createElement(
              "div",
              { className: "portfolio_meta" },
              React.createElement("i", { className: "fa fa-map-marker" }),
              self.props.data.location.location_city
            )
          ),
          React.createElement(
            "a",
            { href: self.props.data.permalink },
            React.createElement("span", { dangerouslySetInnerHTML: { __html: self.props.data.thumbnail } })
          )
        ),
        !self.props.data.featured && React.createElement(
          "div",
          { className: "et_pb_portfolio_item kz_portfolio_item" },
          React.createElement(
            "a",
            { href: self.props.data.permalink },
            React.createElement(
              "span",
              { className: "et_portfolio_image" },
              self.props.render_votes && React.createElement(Vote, { context: "portfolio",
                ref: function ref(c) {
                  return _this._voteComponent = c;
                },
                ID: self.props.data.ID,
                slug: self.props.data.slug,
                apis: self.props.apis,
                currentUserId: self.props.currentUserId }),
              React.createElement("span", { dangerouslySetInnerHTML: { __html: self.props.data.thumbnail } }),
              React.createElement("span", { className: "et_overlay" })
            )
          ),
          React.createElement(
            "h2",
            null,
            React.createElement(
              "a",
              { href: self.props.data.permalink },
              self.props.data.title
            )
          ),
          self.props.show_categories && React.createElement("p", { className: "post-meta", dangerouslySetInnerHTML: { __html: self.props.data.terms } }),
          self.state.isTypeEvent && React.createElement(
            "div",
            { className: "portfolio_meta" },
            React.createElement("i", { className: "fa fa-calendar" }),
            self.state.singleDay && React.createElement(
              "span",
              null,
              "Le ",
              moment(self.state.startDate).format('DD MMM')
            ),
            !self.state.singleDay && React.createElement(
              "span",
              null,
              "Du ",
              moment(self.state.startDate).format('DD MMM'),
              " au ",
              moment(self.state.endDate).format('DD MMM')
            )
          ),
          self.state.hasLocation && React.createElement(
            "div",
            { className: "portfolio_meta" },
            React.createElement("i", { className: "fa fa-map-marker" }),
            self.props.data.location.location_city
          )
        ),
        React.createElement("div", { style: { display: 'none' }, dangerouslySetInnerHTML: { __html: self.props.data.excerpt } })
      )
    );
  },

  setVotesCount: function setVotesCount(_count) {
    var self = this;
    self._voteComponent.setState({
      votes: _count,
      isLoaded: true,
      display: 'inline'
    });
  },

  setVoted: function setVoted(_bool) {
    var self = this;
    self._voteComponent.setState({
      voted: _bool
    });
  }

});

/**
 * Portfolio de <PostPreview />
 *
 */
var Portfolio = React.createClass({
  displayName: "Portfolio",

  componentDidMount: function componentDidMount() {

    var self = this;

    if (self.props.render_votes) {

      var post_ids = self.props.posts.map(function (row) {
        return row.ID;
      });

      //recupération des votes pour les posts
      jQuery.getJSON(self.props.apis.getVotes, { posts_in: post_ids }, function (result) {
        var votesData = result.status;
        for (var i = 0, iLen = votesData.length; i < iLen; i++) {
          self.refs[votesData[i].id].setVotesCount(votesData[i].votes);
        }

        //recupération des votes du user
        jQuery.getJSON(self.props.apis.userVotes + '?user_hash=' + voteSupportModule.getUserHash(), function (res) {
          var userVotes = res.voted;
          for (var j = 0, jLen = userVotes.length; j < jLen; j++) {
            //il est vraisemblable que tous les posts votés par le user ne soient pas sur la page...
            if (typeof self.refs[userVotes[j].id] !== 'undefined') {
              self.refs[userVotes[j].id].setVoted(true);
            }
          }
        });
      });
    }

    if (self.props.animate) {
      //Animation speciale sur les featured pour faire un "waoo"
      TweenMax.staggerFrom('.kz_portfolio_item_featured', 2, { scale: 0.2, opacity: 0, delay: 0.5, ease: Elastic.easeOut, force3D: true }, 0.2);
    }
  },

  render: function render() {

    var self = this;

    //pour les pubs
    var ad = self.props.ad;
    var showAd = self.props.show_ad;

    //le portfolio en lui même
    var list = self.props.posts.map(function (row) {

      return React.createElement(PostPreview, { data: row,
        ref: row.ID,
        apis: self.props.apis,
        currentUserId: self.props.current_user_id,
        key: row.ID,
        render_votes: self.props.render_votes,
        show_categories: self.props.show_categories });
    });

    //inserer la pub en 3e position sauf si le 1er est featured
    //dans le cas d'un premier post featured, on insert la pub en 2e
    if (showAd && ad !== '') {
      var index = list[0].props.data.featured ? 1 : 2;
      var insertedPost = React.createElement(PostPreview, { data: { __html: ad } });
      list.splice(index, 0, insertedPost);
    }

    return React.createElement(
      "div",
      null,
      list
    );
  }

});