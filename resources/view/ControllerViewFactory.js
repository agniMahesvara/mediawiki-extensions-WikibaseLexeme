( function ( mw, wb, $ ) {
	'use strict';

	var PARENT = wb.view.ControllerViewFactory;

	var SELF = util.inherit(
		PARENT,
		function (
			toolbarFactory,
			entityChangersFactory,
			structureEditorFactory,
			contentLanguages,
			dataTypeStore,
			entityIdHtmlFormatter,
			entityIdPlainFormatter,
			entityStore,
			expertStore,
			formatterFactory,
			messageProvider,
			parserStore,
			userLanguages,
			vocabularyLookupApiUrl,
			commonsApiUrl
		) {
			var repoConfig = mw.config.get( 'wbRepo' ),
				repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';
			this._api = wb.api.getLocationAgnosticMwApi( repoApiUrl );

			this._revisionStore = new wb.lexeme.RevisionStore(
				entityChangersFactory.getRevisionStore()
			);
			var changersFactory = new wb.entityChangers.EntityChangersFactory(
				new wb.api.RepoApi( this._api ),
				this._revisionStore,
				entityChangersFactory.getEntity(),
				function ( hookName ) {
					var hook = mw.hook( hookName );
					hook.fire.apply( hook, Array.prototype.slice.call( arguments, 1 ) );
				}
			);

			PARENT.apply( this, [
				toolbarFactory,
				changersFactory,
				structureEditorFactory,
				contentLanguages,
				dataTypeStore,
				entityIdHtmlFormatter,
				entityIdPlainFormatter,
				entityStore,
				expertStore,
				formatterFactory,
				messageProvider,
				parserStore,
				userLanguages,
				vocabularyLookupApiUrl,
				commonsApiUrl
			] );
		}
	);

	/**
	 * @type {mediaWiki.Api}
	 * @private
	 */
	SELF.prototype._api = null;

	/**
	 * @type {wikibase.lexeme.RevisionStore}
	 * @private
	 */
	SELF.prototype._revisionStore = null;

	SELF.prototype.getEntityView = function ( startEditingCallback, lexeme, $entityview ) {
		return this._getView(
			'lexemeview',
			$entityview,
			{
				buildEntityTermsView: this.getEntityTermsView.bind( this, startEditingCallback ),
				buildSitelinkGroupListView: this.getSitelinkGroupListView.bind( this, startEditingCallback ),
				buildStatementGroupListView: this.getStatementGroupListView.bind( this, startEditingCallback ),
				buildFormListView: this.getFormListView.bind( this, lexeme, startEditingCallback ),
				buildSenseListView: this.getSenseListView.bind( this, lexeme, startEditingCallback ),
				buildLexemeHeader: wb.lexeme.widgets.buildLexemeHeader,
				value: lexeme
			}
		);
	};

	SELF.prototype.getEntityTermsView = function () {
		return null; // Don't render terms view
	};

	SELF.prototype.getFormListView = function ( lexeme, startEditingCallback ) {
		return this._getView(
			'lexemeformlistview',
			$( '.wikibase-lexeme-forms-section' ),
			{
				getListItemAdapter: this.getListItemAdapterForFormListView.bind( this, lexeme, startEditingCallback ),
				getAdder: this.getAdderWithStartEditing( startEditingCallback ),
				getMessage: $.proxy( mw.messages.get, mw.messages ),
				value: lexeme.getForms()
			}
		);
	};

	SELF.prototype.getSenseListView = function ( lexeme, startEditingCallback ) {
		return this._getView(
			'senselistview',
			$( '.wikibase-lexeme-senses-section' ),
			{
				getListItemAdapter: this.getListItemAdapterForSenseListView.bind( this, lexeme, startEditingCallback ),
				getMessage: $.proxy( mw.messages.get, mw.messages ),
				getAdder: this.getAdderWithStartEditing( startEditingCallback ),
				value: lexeme.getSenses()
			}
		);
	};

	SELF.prototype.getFormView = function (
		lexeme,
		form,
		labelFormattingService,
		$dom,
		startEditingCallback,
		removeCallback
	) {
		var formView = this._getView(
				'lexemeformview',
				$dom,
				{
					value: form || new wb.lexeme.datamodel.Form(),
					labelFormattingService: labelFormattingService,
					api: this._api,
					buildStatementGroupListView: this.getStatementGroupListView.bind(
						this,
						startEditingCallback
					),
					lexeme: lexeme
				}
			),
			formSerializer = new wb.lexeme.serialization.FormSerializer(),
			formData = form ? formSerializer.serialize( form ) : null,
			controller = this._getController(
				this._toolbarFactory.getToolbarContainer( formView.element ),
				formView,
				new wb.lexeme.entityChangers.FormChanger(
					new wb.api.RepoApi( this._api ),
					this._revisionStore,
					lexeme.getId(),
					formData
				),
				removeCallback.bind( null, formView ),
				form,
				startEditingCallback
			);

		// Empty formviews (added with the "add" button) should start in edit mode
		if ( !form ) {
			controller.startEditing().done( formView.focus.bind( formView ) );
		}

		return formView;
	};

	SELF.prototype.getSenseView = function (
		lexeme,
		sense,
		labelFormattingService,
		$dom,
		startEditingCallback,
		removeCallback
	) {
		var senseView = this._getView(
				'senseview',
				$dom,
				{
					value: sense || new wb.lexeme.datamodel.Sense(),
					labelFormattingService: labelFormattingService,
					api: this._api,
					buildStatementGroupListView: this.getStatementGroupListView.bind(
						this,
						startEditingCallback
					),
					lexeme: lexeme
				}
			),
			senseSerializer = new wb.lexeme.serialization.SenseSerializer(),
			senseData = sense ? senseSerializer.serialize( sense ) : null,
			controller = this._getController(
				this._toolbarFactory.getToolbarContainer( senseView.element ),
				senseView,
				new wb.lexeme.entityChangers.SenseChanger(
					new wb.api.RepoApi( this._api ),
					this._revisionStore,
					lexeme.getId(),
					senseData
				),
				removeCallback.bind( null, senseView ),
				sense,
				startEditingCallback
			);

		if ( !sense ) {
			controller.startEditing().done( senseView.focus.bind( senseView ) );
		}

		return senseView;
	};

	/**
	 * @class wikibase.LabelFormattingService
	 * @param {mediaWiki.Api} api
	 * @param {Object} cachedData
	 * @constructor
	 */
	var FakeLabelFormattingService = function ( api, cachedData ) {
		this._api = api;
		this._cachedData = cachedData;
	};

	/**
	 * @type {mediaWiki.Api}
	 * @private
	 */
	FakeLabelFormattingService.prototype._api = null;

	/**
	 * @type {Object}
	 * @private
	 */
	FakeLabelFormattingService.prototype._cachedData = null;

	FakeLabelFormattingService.prototype.getHtml = function getHtml( entityId ) {
		var deferred = $.Deferred(),
			self = this;

		if ( this._cachedData[ entityId ] ) {
			return $.Deferred().resolve( this._cachedData[ entityId ] ).promise();
		}

		this._api.get( {
			action: 'wbformatvalue',
			datavalue: JSON.stringify( {
				value: {
					id: entityId
				},
				type: 'wikibase-entityid'
			} ), // FIXME use data value
			format: 'json',
			language: mw.config.get( 'wgUserLanguage' ),
			uselang: mw.config.get( 'wgUserLanguage' ),
			generate: 'text/html'
		} ).then( function ( data ) {
			self._cachedData[ entityId ] = data.result;
			deferred.resolve( data.result );
		} );

		return deferred.promise();
	};

	SELF.prototype.getListItemAdapterForFormListView = function ( lexeme, startEditingCallback, removeCallback ) {
		var self = this;

		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.lexemeformview,
			getNewItem: function ( form, element ) {
				var $element = $( element );

				return self.getFormView(
					lexeme,
					form || null,
					new FakeLabelFormattingService( self._api, self._getExistingGrammaticalFormattedFeatures( $element ) ),
					$element,
					startEditingCallback,
					removeCallback
				);
			}
		} );
	};

	SELF.prototype._getExistingGrammaticalFormattedFeatures = function ( $element ) {
		var features = {};

		$.each( $element.find( '.wikibase-lexeme-form-grammatical-features-values > a' ), function ( i, el ) {
			features[ el.title.replace( 'Item:', '' ) ] = el.outerHTML; // TODO Find proper way to get Item ID here
		} );

		return features;
	};

	SELF.prototype.getListItemAdapterForSenseListView = function ( lexeme, startEditingCallback, removeCallback ) {
		var self = this;

		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: $.wikibase.senseview,
			getNewItem: function ( sense, element ) {
				var $element = $( element );

				return self.getSenseView(
					lexeme,
					sense || null,
					new FakeLabelFormattingService( self._api ),
					$element,
					startEditingCallback,
					removeCallback
				);
			}
		} );
	};

	wb.lexeme.view.ControllerViewFactory = SELF;

}( mediaWiki, wikibase, jQuery ) );
