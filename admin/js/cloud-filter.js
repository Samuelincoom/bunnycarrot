(function(){
	var MediaLibraryCloudFilter = wp.media.view.AttachmentFilters.extend({
		id: 'media-attachment-cloud-filter',
		createFilters: function() {
			var filters = {};
			_.each( carrot_bunnycdn_incoom_plugin_params.filter_cloud_served || {}, function( value, index ) {
				filters[ index ] = {
					text: value.name,
					props: {
						carrot_served: value.slug
					}
				};
			});
			filters.all = {
				text:  carrot_bunnycdn_incoom_plugin_params.filter_all,
				props: {
					carrot_served: 'all'
				},
				priority: 10
			};
			this.filters = filters;
		}
	});
	/**
	 * Extend and override wp.media.view.AttachmentsBrowser to include our new filter
	 */
	var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		createToolbar: function() {
			// Make sure to load the original toolbar
			AttachmentsBrowser.prototype.createToolbar.call( this );
			this.toolbar.set( 'MediaLibraryCloudFilter', new MediaLibraryCloudFilter({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );
		}
	});
})()