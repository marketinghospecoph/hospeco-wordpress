/* global frmcalcs, __FRMCALC */

/*
 * Merges one form's calculation rules into __FRMCALC. Printed inline by
 * FrmProFormsHelper::echo_calc_rules_merge_js(), whose docblock explains the merge rules.
 * Not enqueued, and kept free of long comments because every form prints a copy.
 *
 * Block comments only, never line comments: this is printed inline, and an optimisation plugin
 * that combines inline scripts without keeping the newlines would turn a line comment into a
 * comment over the rest of the script.
 */
if ( typeof __FRMCALC === 'undefined' ) {
	__FRMCALC = frmcalcs;
} else {
	const frmCalcRefLists = new Set( [ 'triggers', 'total' ] );

	const frmMergeCalcRules = function( target, source ) {
		Object.entries( source ).forEach( function( [ key, value ] ) {
			if ( Array.isArray( value ) ) {
				if ( ! frmCalcRefLists.has( key ) ) {
					target[ key ] = value;
					return;
				}

				const previous = Array.isArray( target[ key ] ) ? target[ key ] : [];
				target[ key ] = [ ...new Set( [ ...previous, ...value ] ) ];
			} else if ( value !== null && typeof value === 'object' ) {
				const isMergeable = target[ key ] !== null && typeof target[ key ] === 'object' && ! Array.isArray( target[ key ] );
				target[ key ] = frmMergeCalcRules( isMergeable ? target[ key ] : {}, value );
			} else {
				target[ key ] = value;
			}
		} );

		return target;
	};

	/*
	 * Merged in place. Copying first would be pointless: the merge is synchronous, so no consumer
	 * can observe a half merged object, and every reader takes a fresh reference to __FRMCALC when
	 * it runs. frmcalcs is a new object literal on each print, so this never merges into itself.
	 */
	frmMergeCalcRules( __FRMCALC, frmcalcs );
}
