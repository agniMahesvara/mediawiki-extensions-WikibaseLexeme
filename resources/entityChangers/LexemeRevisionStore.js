/**
 * @license GPL-2.0-or-later
 */
( function ( wb, $ ) {
	'use strict';

	var SELF = wb.lexeme.RevisionStore = function WbLexemeRevisionStore( baseStore ) {
		this.baseStore = baseStore;
		this.formRevisions = {};
		this.formStatementRevisions = {};
		this.senseRevisions = {};
		this.senseStatementRevisions = {};
	};

	$.extend( SELF.prototype, {
		/**
		 * @param {string} claimGuid
		 * @return {number}
		 */
		getClaimRevision: function ( claimGuid ) {
			var formId = this.getFormIdFromStatementId( claimGuid );
			var senseId = this.getSenseIdFromStatementId( claimGuid );

			if ( formId !== null ) {
				if ( this.formStatementRevisions.hasOwnProperty( claimGuid ) ) {
					return this.formStatementRevisions[ claimGuid ];
				}

				return this.getFormRevision( formId );
			}

			if ( senseId !== null ) {
				if ( this.senseStatementRevisions.hasOwnProperty( claimGuid ) ) {
					return this.senseStatementRevisions[ claimGuid ];
				}

				return this.getSenseRevision( senseId );
			}

			return this.baseStore.getClaimRevision( claimGuid );
		},

		/**
		 * @param {number} rev
		 * @param {string} claimGuid
		 */
		setClaimRevision: function ( rev, claimGuid ) {
			var formId = this.getFormIdFromStatementId( claimGuid );
			var senseId = this.getSenseIdFromStatementId( claimGuid );

			if ( formId !== null ) {
				this.formStatementRevisions[ claimGuid ] = rev;
				return;
			}

			if ( senseId !== null ) {
				this.senseStatementRevisions[ claimGuid ] = rev;
				return;
			}

			this.baseStore.setClaimRevision( rev, claimGuid );
		},

		/**
		 * @private
		 * @param {string} statementGuid
		 * @return {number|null}
		 */
		getFormIdFromStatementId: function ( statementGuid ) {
			var matchResult = statementGuid.match( /^(L\d+-F\d+)\$/ );

			if ( matchResult !== null ) {
				return matchResult[ 1 ];
			}

			return null;
		},

		/**
		 * @private
		 * @param {string} statementGuid
		 * @return {number|null}
		 */
		getSenseIdFromStatementId: function ( statementGuid ) {
			var matchResult = statementGuid.match( /^(L\d+-S\d+)\$/ );

			if ( matchResult !== null ) {
				return matchResult[ 1 ];
			}

			return null;
		},

		/**
		 * @return {number}
		 */
		getBaseRevision: function () {
			return this.baseStore.getBaseRevision();
		},

		/**
		 * @param {number} revision
		 * @param {string} formId
		 */
		setFormRevision: function ( revision, formId ) {
			this.formRevisions[ formId ] = revision;
		},

		/**
		 * @param {string} formId
		 * @return {number}
		 */
		getFormRevision: function ( formId ) {
			if ( this.formRevisions.hasOwnProperty( formId ) ) {
				return this.formRevisions[ formId ];
			}

			return this.baseStore.getBaseRevision();
		},

		/**
		 * @param {number} revision
		 * @param {string} senseId
		 */
		setSenseRevision: function ( revision, senseId ) {
			this.senseRevisions[ senseId ] = revision;
		},

		/**
		 * @param {string} senseId
		 * @return {number}
		 */
		getSenseRevision: function ( senseId ) {
			if ( this.senseRevisions.hasOwnProperty( senseId ) ) {
				return this.senseRevisions[ senseId ];
			}

			return this.baseStore.getBaseRevision();
		}

	} );

}( wikibase, jQuery ) );