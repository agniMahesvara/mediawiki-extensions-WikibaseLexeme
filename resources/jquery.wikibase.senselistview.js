( function ( $ ) {
	'use strict';

	var PARENT = $.Widget;

	/**
	 * @class jQuery.wikibase.senselistview
	 * @extends jQuery.ui.Widget
	 * @license GPL-2.0+
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {jquery.wikibase.listview.ListItemAdapter} options.getListItemAdapter
	 * @param {jQuery.wikibase.addtoolbar} options.getAdder
	 * @param {wikibase.lexeme.datamodel.Sense} options.value
	 */
	$.widget( 'wikibase.senselistview', PARENT, {
		/**
		 * @inheritdoc
		 */
		options: {
			getListItemAdapter: null,
			value: null
		},

		/**
		 * @type {jQuery.wikibase.listview}
		 * @private
		 */
		_listview: null,

		/**
		 * @inheritdoc
		 */
		_create: function () {
			PARENT.prototype._create.call( this );

			this._listview = this._createListView();
			this.options.getAdder(
				this._listview.enterNewItem.bind( this._listview ),
				this.element
			);
		},

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			this._listview.destroy();
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Creates the `listview` widget managing the `senseview` widgets.
		 *
		 * @private
		 */
		_createListView: function () {
			return new $.wikibase.listview( {
				listItemAdapter: this.options.getListItemAdapter( this._removeItem.bind( this ) ),
				listItemNodeName: 'div',
				value: this.options.value
			}, this.element.find( '.wikibase-lexeme-senses' ) );
		},

		/**
		 * Removes a `senselistview` widget.
		 *
		 * @param {jQuery.wikibase.senselistview} senselistview
		 */
		_removeItem: function ( senselistview ) {
			this._listview.removeItem( senselistview.element );
			this._trigger( 'afterremove' );
		}

	} );
}( jQuery ) );
