( function( api ) {

	// Extends our custom "vw-kids" section.
	api.sectionConstructor['vw-kids'] = api.Section.extend( {

		// No events for this type of section.
		attachEvents: function () {},

		// Always make the section active.
		isContextuallyActive: function () {
			return true;
		}
	} );

} )( wp.customize );