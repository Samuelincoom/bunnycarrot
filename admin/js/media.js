var test = {};

(function ($, _) {

	// Local reference to the WordPress media namespace.
	var media = wp.media;

	// Local instance of the Attachment Details TwoColumn used in the edit attachment modal view
	var wpAttachmentDetailsTwoColumn = media.view.Attachment.Details.TwoColumn;
	var l10n = media.view.l10n;

	if (wp.media && carrot_bunnycdn_incoom_plugin_params.is_plugin_setup == '1') {
		if ($("#woocommerce-product-data").length > 0 || $(".edd_upload_file_button").length > 0) {
			media.view.MediaFrame.Select.prototype.browseRouter = function (routerView) {
				routerView.set({
					upload: {
						text: l10n.uploadFilesTitle,
						priority: 20
					},
					browse: {
						text: l10n.mediaLibraryTitle,
						priority: 40
					},
					my_tab: {
						text: carrot_bunnycdn_incoom_plugin_params.current_provider,
						priority: 60
					}
				});
			};

			wp.media.view.Modal.prototype.on("open", function () {
				if ($('body').find('.media-frame-router .media-menu-item.active')[0].innerText == carrot_bunnycdn_incoom_plugin_params.current_provider) {
					do_cloud_tab_contents();
				}
			});

			$(wp.media).on('click', '.media-router .media-menu-item', function (e) {
				if (e.target.innerText == carrot_bunnycdn_incoom_plugin_params.current_provider) {
					do_cloud_tab_contents();
				}
			});

			function do_cloud_tab_contents() {
				var data = {
					action: 'incoom_carrot_bunnycdn_incoom_plugin_render_cloud_files',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				$.ajax({
					url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
					type: "POST",
					data: data,
					beforeSend: function () {
						var html = '<div class="attachments-browser"><div class="attachments"><div class="incoom_carrot_bunnycdn_loading active"></div></div></div>';
						$('body .media-modal-content .media-frame-content')[0].innerHTML = html;
					},
					success: function (result) {
						if (result.data.status == 'success') {
							$('body .media-modal-content .media-frame-content')[0].innerHTML = result.data.html;
						}
					},
					error: function (jqXHR, textStatus, errorThrown) {
						$('body .media-modal-content .media-frame-content')[0].innerHTML = errorThrown;
					}
				});
			}

			function set_permission_object(data) {
				$.ajax({
					url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
					type: "POST",
					data: data,
					beforeSend: function () {
						var html = '<div class="incoom_carrot_bunnycdn_loading active"></div>';
						$('body .media-modal-content .media-frame-content #carrot_bunnycdn_incoom_plugin_Show_Keys_of_a_Folder_Bucket_Result_ID').append(html);
					},
					success: function (result) {
						if (result.data.status == 'success') {
							$('body .media-modal-content .media-frame-content #carrot_bunnycdn_incoom_plugin_Show_Keys_of_a_Folder_Bucket_Result_ID .incoom_carrot_bunnycdn_loading').removeClass('active');
						} else {
							$('body .media-modal-content .media-frame-content')[0].innerHTML = result.data.message;
						}
					}
				});
			}

			$("body").on("click", "#carrot_bunnycdn_incoom_plugin_Show_Keys_of_a_Folder_Bucket_Result_ID a.select-folder", function (event) {

				event.preventDefault();

				var Region = $(this).data('region');
				var Current_folder = $(this).data('current_folder');

				var data = {
					action: 'incoom_carrot_bunnycdn_incoom_plugin_render_cloud_files',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
					current_region: Region,
					current_folder: Current_folder,
				}

				$.ajax({
					url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
					type: "POST",
					data: data,
					beforeSend: function () {
						var html = '<div class="attachments-browser"><div class="attachments"><div class="incoom_carrot_bunnycdn_loading active"></div></div></div>';
						$('body .media-modal-content .media-frame-content')[0].innerHTML = html;
					},
					success: function (result) {
						if (result.data.status == 'success') {
							$('body .media-modal-content .media-frame-content')[0].innerHTML = result.data.html;
						}
					}
				});

			});
			var input_file_url;
			var input_file_name;

			$("body").on("click", ".upload_file_button", function () {

				input_file_url = $(this).parent().prev().find("input");
				input_file_name = $(this).parent().prev().prev().find("input");

				$(input_file_url).val('');
				$(input_file_name).val('');

				$("body").on("click", '#carrot_bunnycdn_incoom_plugin_Show_Keys_of_a_Folder_Bucket_Result_ID .incoom_carrot_bunnycdn_ul_File_Manager_li_File a', function (event) {
					var target = $(event.target);
					var Name = $(this).data('value');
					var Key = $(this).data('key');
					var Original = $(this).data('original');
					var Path = $(this).data('path');
					var type = localStorage.getItem('carrot_bunnycdn_incoom_plugin_type') || 'url';
					if (target.hasClass('onoffswitch-checkbox')) {
						var data = {
							key: Key,
							path: Path,
							action: 'incoom_carrot_bunnycdn_incoom_plugin_set_permission_object',
							_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
						};
						set_permission_object(data);
					} else {
						$(".media-modal-close").trigger("click");
						if (type == 'url') {
							$(input_file_url).val(Key);
						} else {
							var shortcode = '[carrot_bunnycdn_incoom_plugin_storage key="' + Original + '" name="' + Name + '"]';
							$(input_file_url).val(shortcode);
						}
						$(input_file_name).val(Name);
					}
				});

				input_file_url.on('change', function(){
					let url = input_file_url.val();
					if(url !== '' && !url.includes("carrot_bunnycdn_incoom_plugin_storage")){
						let key = url.replace(carrot_bunnycdn_incoom_plugin_params.base_url, '');
						
						try {
							key = url.replace(carrot_bunnycdn_incoom_plugin_params.cname_url, '');
						} catch (error) {}

						var shortcode = '[carrot_bunnycdn_incoom_plugin_storage key="' + key + '" name="' + input_file_name.val() + '"]';
						$(input_file_url).val(shortcode);
					}
				});
			});

			$("body").on("click", ".edd_upload_file_button", function () {

				input_file_url = $(this).closest('.edd-repeatable-row-standard-fields').find(".edd_repeatable_upload_field");
				input_file_name = $(this).closest('.edd-repeatable-row-standard-fields').find(".edd_repeatable_name_field");

				$(input_file_url).val('');
				$(input_file_name).val('');

				$("body").on("click", '#carrot_bunnycdn_incoom_plugin_Show_Keys_of_a_Folder_Bucket_Result_ID .incoom_carrot_bunnycdn_ul_File_Manager_li_File a', function (event) {
					var target = $(event.target);
					var Name = $(this).data('value');
					var Key = $(this).data('key');
					var Path = $(this).data('path');
					var Original = $(this).data('original');
					var type = localStorage.getItem('carrot_bunnycdn_incoom_plugin_type') || 'url';
					if (target.hasClass('onoffswitch-checkbox')) {
						var data = {
							key: Key,
							path: Path,
							action: 'incoom_carrot_bunnycdn_incoom_plugin_set_permission_object',
							_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
						};
						set_permission_object(data);
					} else {
						$(".media-modal-close").trigger("click");
						if (type == 'url') {
							$(input_file_url).val(Key);
						} else {
							var shortcode = '[carrot_bunnycdn_incoom_plugin_storage key="' + Original + '" name="' + Name + '"]';
							$(input_file_url).val(shortcode);
						}
						$(input_file_name).val(Name);
					}
				});

				input_file_url.on('change', function(){
					let url = input_file_url.val();
					if(url !== '' && !url.includes("carrot_bunnycdn_incoom_plugin_storage")){
						let key = url.replace(carrot_bunnycdn_incoom_plugin_params.base_url, '');
						
						try {
							key = url.replace(carrot_bunnycdn_incoom_plugin_params.cname_url, '');
						} catch (error) {}

						var shortcode = '[carrot_bunnycdn_incoom_plugin_storage key="' + key + '" name="' + input_file_name.val() + '"]';
						$(input_file_url).val(shortcode);
					}
				});
			});

			$("body").on("click", "#carrot_bunnycdn_incoom_plugin_Show_Keys_of_a_Folder_Bucket_Result_ID .filemanager-display a.view", function () {
				var type = 'grid';
				if ($(this).hasClass('view-list')) {
					type = 'list';
				}
				var Region = $(this).data('region');
				var Current_folder = $(this).data('current_folder');

				var data = {
					type: type,
					action: 'incoom_carrot_bunnycdn_incoom_plugin_render_cloud_files',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
					current_region: Region,
					current_folder: Current_folder,
				}
				$.ajax({
					url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
					type: "POST",
					data: data,
					beforeSend: function () {
						var html = '<div class="attachments-browser"><div class="attachments"><div class="incoom_carrot_bunnycdn_loading active"></div></div></div>';
						$('body .media-modal-content .media-frame-content')[0].innerHTML = html;
					},
					success: function (result) {
						if (result.data.status == 'success') {
							$('body .media-modal-content .media-frame-content')[0].innerHTML = result.data.html;
						}
					}
				});
				return false;
			});

			$("body").on("click", "#carrot_bunnycdn_incoom_plugin_Show_Keys_of_a_Folder_Bucket_Result_ID .filemanager-display a.use", function () {
				$('#carrot_bunnycdn_incoom_plugin_Show_Keys_of_a_Folder_Bucket_Result_ID .filemanager-display a.use').removeClass('current');
				var type = $(this).data('type');
				localStorage.setItem('carrot_bunnycdn_incoom_plugin_type', type);
				$(this).addClass('current');
				return false;
			});
		}
	}
	/**
	 * Add S3 details to attachment.
	 */
	media.view.Attachment.Details.TwoColumn = wpAttachmentDetailsTwoColumn.extend({
		events: function () {
			return _.extend({}, wpAttachmentDetailsTwoColumn.prototype.events, {
				'click .local-warning': 'confirmS3Removal',
			});
		},

		render: function () {
			this.fetchS3Details(this.model.get('id'));
		},

		fetchS3Details: function (id) {
			wp.ajax.send('incoom_carrot_bunnycdn_incoom_plugin_get_attachment_provider_details', {
				data: {
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
					id: id
				}
			}).done(_.bind(this.renderView, this));
		},

		renderView: function (response) {
			// Render parent media.view.Attachment.Details
			wpAttachmentDetailsTwoColumn.prototype.render.apply(this);
			
			if (response && response.links && response.links.length > 0) {
				this.renderActionLinks(response);
				this.renderS3Details(response);
			}
		},

		renderActionLinks: function (response) {
			var links = (response && response.links) || [];
			var $actionsHtml = this.$el.find('.actions');
			var $s3Actions = $('<div />', {
				'class': 'nou-actions'
			});

			var s3Links = [];
			_(links).each(function (link) {
				s3Links.push(link);
			});

			$s3Actions.append(s3Links.join(' | '));
			$actionsHtml.append($s3Actions);
		},

		renderS3Details: function (response) {
			if (!response || !response.provider_object) {
				return;
			}
			var $detailsHtml = this.$el.find('.attachment-info .details');
			var html = this.generateDetails(response, ['provider_name', 'region', 'bucket', 'key']);
			$detailsHtml.append(html);
		},

		generateDetails: function (response, keys) {
			var html = '';
			var template = _.template('<div class="<%= key %>"><strong><%= label %>:</strong> <%= value %></div>');

			_(keys).each(function (key) {
				if (response.provider_object[key]) {
					var value = response.provider_object[key];

					html += template({
						key: key,
						label: carrot_bunnycdn_incoom_plugin_params.strings[key],
						value: value
					});
				}
			});

			return html;
		},

		confirmS3Removal: function (event) {
		},
	});

	media.view.Attachment.Library = media.view.Attachment.Library.extend({
		className: function () { return 'attachment ' + this.model.get('carrot-cloud-class'); }
	});

})(jQuery, _);
