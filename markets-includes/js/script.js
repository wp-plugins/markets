var markets = {
	
	/**
	 * Toggle forms
	 */
	toggle : function(id){
		jQuery('.logintable').hide();
		jQuery('.registrationtable').hide();
		jQuery('.'+id).show();
	},

	/**
	 * Restore
	 */
	restore : function(elem){
		jQuery("div.process_status").html(mrkts.processing);
		var plugin_slug = jQuery(elem).attr("data-slug");
		var security = jQuery(elem).attr("data-nonce");
		var data = {
			action: 'markets_restore_'+plugin_slug,
			security: security
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("div.process_status").html(response.message);
		});
	},

	/**
	 * Sync
	 */
	sync : function(elem){
		jQuery("div.process_status").html(mrkts.processing);
		var plugin_slug = jQuery(elem).attr("data-slug");
		var security = jQuery(elem).attr("data-nonce");
		var data = {
			action: 'markets_sync_'+plugin_slug,
			security: security
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("div.process_status").html(response.message);
		});
	}
}