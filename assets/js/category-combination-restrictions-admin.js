( function () {
	var selectA      = document.querySelector( 'select[name="trigger_category"]' );
	var selectB      = document.querySelector( 'select[name="restricted_category"]' );
	var parentMap    = window.categoryCombinationRestrictionsAdmin.parentMap;
	var existingRules = window.categoryCombinationRestrictionsAdmin.existingRules;

	if ( ! selectA || ! selectB ) {
		return;
	}

	// Build a children map (parent_id => [child_ids]) from the parent map.
	var childrenMap = {};
	Object.keys( parentMap ).forEach( function ( termId ) {
		var parentId = parentMap[ termId ];
		if ( ! childrenMap[ parentId ] ) {
			childrenMap[ parentId ] = [];
		}
		childrenMap[ parentId ].push( parseInt( termId, 10 ) );
	} );

	// Recursively collect all descendant term IDs for a given term.
	function getDescendants( termId ) {
		var result   = [];
		var children = childrenMap[ termId ] || [];
		children.forEach( function ( childId ) {
			result.push( childId );
			getDescendants( childId ).forEach( function ( d ) {
				result.push( d );
			} );
		} );
		return result;
	}

	// Collect all ancestor term IDs for a given term.
	function getAncestors( termId ) {
		var ancestors = [];
		var current   = termId;
		while ( parentMap[ current ] ) {
			var parentId = parentMap[ current ];
			if ( ! parentId ) {
				break;
			}
			ancestors.push( parentId );
			current = parentId;
		}
		return ancestors;
	}

	function syncDisabled( source, target ) {
		// Re-enable every option in the target dropdown.
		Array.prototype.forEach.call( target.options, function ( opt ) {
			opt.disabled = false;
		} );

		var val = parseInt( source.value, 10 );
		if ( val ) {
			// Disable the selected category, all its descendants, and all its ancestors.
			var toDisable = [ val ].concat( getDescendants( val ) ).concat( getAncestors( val ) );
			toDisable.forEach( function ( termId ) {
				var match = target.querySelector( 'option[value="' + termId + '"]' );
				if ( match ) {
					match.disabled = true;
					// If target already has this value selected, reset it.
					if ( parseInt( target.value, 10 ) === termId ) {
						target.value = '';
					}
				}
			} );

			// Disable categories that already have a rule paired with the selected category.
			existingRules.forEach( function ( rule ) {
				var ruleA  = parseInt( rule.trigger, 10 );
				var ruleB  = parseInt( rule.restricted, 10 );
				var paired = null;
				if ( ruleA === val ) {
					paired = ruleB;
				} else if ( ruleB === val ) {
					paired = ruleA;
				}
				if ( paired ) {
					var match = target.querySelector( 'option[value="' + paired + '"]' );
					if ( match ) {
						match.disabled = true;
						if ( parseInt( target.value, 10 ) === paired ) {
							target.value = '';
						}
					}
				}
			} );
		}
	}

	selectA.addEventListener( 'change', function () {
		syncDisabled( selectA, selectB );
	} );

	selectB.addEventListener( 'change', function () {
		syncDisabled( selectB, selectA );
	} );

	// Apply on page load in case a value is pre-selected (e.g. after a failed save).
	syncDisabled( selectA, selectB );
	syncDisabled( selectB, selectA );
} )();
