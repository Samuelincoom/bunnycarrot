(function( $ ) {
	'use strict';
	$( ".post-type-attachment #wpwrap" ).append("<div class='incoom_carrot_bunnycdn_loading'></div>");
	$('body').on('click', '.select-mode-toggle-button', function(){
		if(carrot_bunnycdn_incoom_plugin_params.is_plugin_setup == '1'){
			if($(this).hasClass('button-large') && $('.incoom_carrot_bunnycdn_storage_button').length == 0){
				$(".media-toolbar-secondary").append("<button type='button' data-doaction='incoom_carrot_bunnycdn_copy_to_s3' class='button media-button button-primary button-large incoom_carrot_bunnycdn_storage_button' disabled='disabled'>"+ carrot_bunnycdn_incoom_plugin_params.copy_to_s3_text +"</button>");
	    		$(".media-toolbar-secondary").append("<button type='button' data-doaction='incoom_carrot_bunnycdn_remove_from_s3' class='button media-button button-primary button-large incoom_carrot_bunnycdn_storage_button' disabled='disabled'>"+ carrot_bunnycdn_incoom_plugin_params.remove_from_s3_text +"</button>");
	    		$(".media-toolbar-secondary").append("<button type='button' data-doaction='incoom_carrot_bunnycdn_copy_to_server_from_s3' class='button media-button button-primary button-large incoom_carrot_bunnycdn_storage_button' disabled='disabled'>"+ carrot_bunnycdn_incoom_plugin_params.copy_to_server_from_s3_text +"</button>");
	    		$(".media-toolbar-secondary").append("<button type='button' data-doaction='incoom_carrot_bunnycdn_remove_from_server' class='button media-button button-primary button-large incoom_carrot_bunnycdn_storage_button' disabled='disabled'>"+ carrot_bunnycdn_incoom_plugin_params.remove_from_server_text +"</button>");
	    		$(".media-toolbar-secondary").append("<button type='button' data-doaction='incoom_carrot_bunnycdn_build_webp' class='button media-button button-primary button-large incoom_carrot_bunnycdn_storage_button' disabled='disabled'>"+ carrot_bunnycdn_incoom_plugin_params.build_webp_text +"</button>");
			}else{
				$(".media-toolbar-secondary .incoom_carrot_bunnycdn_storage_button").toggle();
	            $(".media-toolbar-secondary .incoom_carrot_bunnycdn_storage_button").attr("disabled", "disabled");
			}
		}	
	});

	$('body').on('click', '.attachments-browser li.attachment', function(){
		if($('body .attachments-browser li.attachment.selected').length > 0){
			$('.incoom_carrot_bunnycdn_storage_button').prop("disabled", false);
		}else{
			$('.incoom_carrot_bunnycdn_storage_button').prop("disabled", true);
		}
	});

	$('body').on("click", ".delete-selected-button", function () {
        if ($(this).hasClass('hidden')) {
            $(".media-toolbar-secondary .incoom_carrot_bunnycdn_storage_button").toggle();
            $(".media-toolbar-secondary .incoom_carrot_bunnycdn_storage_button").attr("disabled", "disabled");
        }
    });

	$('body').on("click", ".incoom_carrot_bunnycdn_storage_button", function () {
		if(carrot_bunnycdn_incoom_plugin_params.is_plugin_setup == '1'){
			var self = $(this);
			var ids = [];
			if($('body .attachments-browser li.attachment').length > 0){
				$('body .attachments-browser li.attachment').each(function(i, obj) {
				    if($(this).hasClass('selected')){
				    	ids.push($(this).data('id'));
				    }
				});
			}

	        iziToast.show({
		        progressBar: false,
		        theme: 'dark',
		        progressBarColor: 'rgb(0, 255, 184)',
		        maxWidth: '500px',
		        drag: false,
		        overlay: true,
		        displayMode: 1,
		        pauseOnHover: false,
		        timeout: false,
		        title: carrot_bunnycdn_incoom_plugin_params.popup_title,
		        message: '<div id="spin_loading"></div> ' + '<div class="progress-bar"><span id="percent">0%</span><span class="bar"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
		        position: 'topCenter',
		        onOpened: function () {
		        	var data = {
				  		action: 'incoom_carrot_bunnycdn_incoom_plugin_library_button_action_mode_grid',
				  		_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
				  		doaction: self.data('doaction'),
				  		post_ids: ids
				  	};	
				  	run_action_mode_grid(data);

		        }
		    });
		}    
    });

    function run_action_mode_grid(data){
    	var count = (data.post_ids.length > 0) ? data.post_ids.length : 0;
    	var processed = (data.processed) ? data.processed : 0;
    	$('.progress_count').text( processed + '/' + count );
    	$.ajax({
		  	url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
		  	type: "POST",
		  	data: data,
			success: function(result) {
				if(result.data.status == 'success'){
					$('.progress-bar .progress').css({
                        width: '100%'
                    });
                    $('#percent').text('100%');

					setTimeout(function(){ 
						location.reload();
					}, 2000);
					
				}else{
					data.processed = result.data.processed;
					$('.progress-bar .progress').css({
                        width: result.data.percent + '%'
                    });
                    $('#percent').text(result.data.percent + '%');
					run_action_mode_grid(data);
				}
			}
		});
    }

	setTimeout(function(){ 
		if($('#incoom_carrot_bunnycdn_connection_status').length > 0){
			$('#incoom_carrot_bunnycdn_connection_status').hide();
		} 
	}, 5000);

})( jQuery );
