(function ($) {
	"use strict";

	function run_upload_assets(data) {
		var count = (data.count > 0) ? data.count : 0;
		var processed = (data.processed) ? data.processed : 0;
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			success: function (result) {
				if (result.data.status == 'success') {
					$('.progress-bar .progress').css({
						width: '100%'
					});
					$('#percent').text('100%');
					$('.progress_count').text(data.count + '/' + data.count);
					$('.iziToastloading').removeClass('spin_loading').addClass('dashicons-yes');
					setTimeout(function () {
						location.reload();
					}, 2000);
				} else {
					if(result.data.status == 'fail'){
						setTimeout(function () {
							location.reload();
						}, 2000);
					}else{
						data.processed = result.data.processed;
						$('.progress-bar .progress').css({
							width: result.data.percent + '%'
						});
						$('#percent').text(result.data.percent + '%');
						data.count = result.data.count;
						run_upload_assets(data);
						$('.progress_count').text(processed + '/' + data.count);
					}
				}
			}
		});
	}

	function upload_assets() {
		iziToast.destroy();
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
			message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar"><span id="percent">0%</span><span class="bar"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
			position: 'topCenter',
			onOpened: function () {
				var data = {
					count: 0,
					action: 'incoom_carrot_bunnycdn_incoom_plugin_upload_assets',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				run_upload_assets(data);
			},
			onClosing: function (instance, toast, closedBy) {
				setTimeout(function () {
					location.reload();
				}, 1000);
			}
		});
	}

	function create_bucket() {
		var data = {
			bucket: $('#buckets-form input[name="incoom_carrot_bunnycdn_incoom_plugin_bucket"]').val(),
			regional: $('#buckets-form select[name="incoom_carrot_bunnycdn_incoom_plugin_bucket_regional"]').val(),
			action: 'incoom_carrot_bunnycdn_incoom_plugin_create_bucket',
			_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
		};
		$('.iziToast-body .spin_loading').css({
			opacity: '1'
		});
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			success: function (result) {
				if (result.data.status == 'success') {
					setTimeout(function () {
						location.reload();
					}, 1000);
				} else {
					alert(result.data.message);
				}
				$('.iziToast-body .spin_loading').css({
					opacity: '0'
				});
			}
		});
	}

	function run_sync_data(data) {
		var count = (data.count > 0) ? data.count : 0;
		var processed = (data.processed) ? data.processed : 0;
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			success: function (result) {
				if (result.data.status == 'success') {
					$('.progress-bar .progress').css({
						width: '100%'
					});
					$('#percent').text('100%');
					$('.progress_count').text(data.count + '/' + data.count);
					$('.iziToastloading').removeClass('spin_loading').addClass('dashicons-yes');
					setTimeout(function () {
						location.reload();
					}, 2000);
				} else {
					data.processed = result.data.processed;
					$('.progress-bar .progress').css({
						width: result.data.percent + '%'
					});
					$('#percent').text(result.data.percent + '%');
					data.count = result.data.count;
					run_sync_data(data);
					$('.progress_count').text(processed + '/' + data.count);
				}
			}
		});
	}

	function sync_data(count) {
		iziToast.destroy();
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
			message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar"><span id="percent">0%</span><span class="bar"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
			position: 'topCenter',
			onOpened: function () {
				var data = {
					count: count,
					action: 'incoom_carrot_bunnycdn_incoom_plugin_sync_data',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				run_sync_data(data);
			},
			onClosing: function (instance, toast, closedBy) {
				setTimeout(function () {
					location.reload();
				}, 1000);
			}
		});
	}

	function run_download_all_files(data) {
		var count = (data.count > 0) ? data.count : 0;
		var processed = (data.processed) ? data.processed : 0;
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			success: function (result) {
				if (result.data.status == 'success') {
					$('.progress-bar .progress').css({
						width: '100%'
					});
					$('#percent').text('100%');
					$('.progress_count').text(data.count + '/' + data.count);
					$('.iziToastloading').removeClass('spin_loading').addClass('dashicons-yes');
					setTimeout(function () {
						location.reload();
					}, 2000);
				} else {
					data.processed = result.data.processed;
					$('.progress-bar .progress').css({
						width: result.data.percent + '%'
					});
					$('#percent').text(result.data.percent + '%');
					data.count = result.data.count;
					run_download_all_files(data);
					$('.progress_count').text(processed + '/' + data.count);
				}
			}
		});
	}

	function run_remove_all_files_from_server(data) {
		var count = (data.count > 0) ? data.count : 0;
		var processed = (data.processed) ? data.processed : 0;
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			success: function (result) {
				if (result.data.status == 'success') {
					$('.progress-bar .progress').css({
						width: '100%'
					});
					$('#percent').text('100%');
					$('.progress_count').text(data.count + '/' + data.count);
					$('.iziToastloading').removeClass('spin_loading').addClass('dashicons-yes');
					setTimeout(function () {
						location.reload();
					}, 2000);
				} else {
					data.processed = result.data.processed;
					$('.progress-bar .progress').css({
						width: result.data.percent + '%'
					});
					$('#percent').text(result.data.percent + '%');
					data.count = result.data.count;
					run_remove_all_files_from_server(data);
					$('.progress_count').text(processed + '/' + data.count);
				}
			}
		});
	}

	function download_all_files() {
		iziToast.destroy();
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
			title: carrot_bunnycdn_incoom_plugin_params.download_title,
			message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar"><span id="percent">0%</span><span class="bar"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
			position: 'topCenter',
			onOpened: function () {
				var data = {
					count: 0,
					action: 'incoom_carrot_bunnycdn_incoom_plugin_download_all_files',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				run_download_all_files(data);
			},
			onClosing: function (instance, toast, closedBy) {
				setTimeout(function () {
					location.reload();
				}, 1000);
			}
		});
	}

	function run_remove_all_files_from_bucket(data) {
		var count = (data.count > 0) ? data.count : 0;
		var processed = (data.processed) ? data.processed : 0;
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			success: function (result) {
				if (result.data.status == 'success') {
					$('.progress-bar .progress').css({
						width: '100%'
					});
					$('#percent').text('100%');
					$('.progress_count').text(data.count + '/' + data.count);
					$('.iziToastloading').removeClass('spin_loading').addClass('dashicons-yes');
					setTimeout(function () {
						location.reload();
					}, 2000);
				} else {
					data.processed = result.data.processed;
					$('.progress-bar .progress').css({
						width: result.data.percent + '%'
					});
					$('#percent').text(result.data.percent + '%');
					data.count = result.data.count;
					run_remove_all_files_from_bucket(data);
					$('.progress_count').text(processed + '/' + data.count);
				}
			}
		});
	}

	function remove_all_files_from_bucket() {
		iziToast.destroy();
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
			title: carrot_bunnycdn_incoom_plugin_params.remove_all_files_from_bucket_title,
			message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar"><span id="percent">0%</span><span class="bar"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
			position: 'topCenter',
			onOpened: function () {
				var data = {
					count: 0,
					action: 'incoom_carrot_bunnycdn_incoom_plugin_remove_all_files_from_bucket',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				run_remove_all_files_from_bucket(data);
			},
			onClosing: function (instance, toast, closedBy) {
				setTimeout(function () {
					location.reload();
				}, 1000);
			}
		});
	}

	function remove_all_files_from_server() {
		iziToast.destroy();
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
			title: carrot_bunnycdn_incoom_plugin_params.remove_all_files_from_server_title,
			message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar"><span id="percent">0%</span><span class="bar"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
			position: 'topCenter',
			onOpened: function () {
				var data = {
					count: 0,
					action: 'incoom_carrot_bunnycdn_incoom_plugin_remove_all_files_from_server',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				run_remove_all_files_from_server(data);
			},
			onClosing: function (instance, toast, closedBy) {
				setTimeout(function () {
					location.reload();
				}, 1000);
			}
		});
	}

	function copy_all_files_to_bucket() {
		iziToast.destroy();
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
			title: carrot_bunnycdn_incoom_plugin_params.copy_all_files_to_bucket_title,
			//message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar"><span id="percent">100%</span><span class="bar"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
			position: 'topCenter',
			onOpened: function () {
				var data = {
					action: 'incoom_carrot_bunnycdn_incoom_plugin_copy_all_files_to_bucket',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				$.ajax({
					url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
					type: "POST",
					data: data,
					success: function (result) {
						setTimeout(function () {
							location.reload();
						}, 2000);
					}
				});
			},
			onClosing: function (instance, toast, closedBy) {
				setTimeout(function () {
					location.reload();
				}, 1000);
			}
		});
	}

	$(document).ready(function () {

		$('body').on('change', 'select[name="incoom_carrot_bunnycdn_connection_provider"]', function () {
			var provider = $(this).val();
			$('#carrot-bunnycdn-incoom-plugin-wrap .conditional').hide();
			$('#carrot-bunnycdn-incoom-plugin-wrap .show_if_' + provider).removeClass('hidden').show();
		});

		$("body").on('change', 'input[name="incoom_carrot_bunnycdn_incoom_plugin_assets_rewrite_urls_checkbox"]', function () {
			if (this.checked) {
				$('#carrot-bunnycdn-incoom-plugin-wrap .show_if_assets_rewrite_urls').show();
			} else {
				$('#carrot-bunnycdn-incoom-plugin-wrap .show_if_assets_rewrite_urls').hide();
			}
		});

		$("body").on('click', '#incoom_carrot_bunnycdn_scan_assets', function () {
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
				message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar"><span id="percent" style="line-height: 11px;height: 15px;right: -5%;width: 100%;">' + carrot_bunnycdn_incoom_plugin_params.scan_title + '</span><span class="bar" style="opacity: 0;"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
				position: 'topCenter',
				onOpened: function () {
					var data = {
						action: 'incoom_carrot_bunnycdn_incoom_plugin_scan_assets',
						_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
					};
					$.ajax({
						url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
						type: "POST",
						data: data,
						success: function (result) {
							if (result.data.status == 'success') {
								$('#percent').text(result.data.total);
								$('.iziToast-body .iziToast-buttons').show();
								$('.iziToastloading').removeClass('spin_loading').addClass('dashicons-yes');
							}
						}
					});

				},
				buttons: [
					['<button>' + carrot_bunnycdn_incoom_plugin_params.upload_title + '</button>', function (instance, toast) {
						upload_assets();
					}],
					['<button>' + carrot_bunnycdn_incoom_plugin_params.close_title + '</button>', function (instance, toast) {
						instance.hide({
							transitionOut: 'fadeOutUp',
						}, toast, 'buttonName');
					}]
				],
				onClosing: function (instance, toast, closedBy) {
					setTimeout(function () {
						location.reload();
					}, 1000);
				}
			});
		});

		$("body").on('click', '#incoom_carrot_bunnycdn_create_bucket', function () {
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
				message: '<div id="buckets-form"></div>',
				position: 'topCenter',
				onOpening: function () {
					var data = {
						action: 'incoom_carrot_bunnycdn_incoom_plugin_form_create_bucket',
						_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
					};
					$.ajax({
						url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
						type: "POST",
						data: data,
						success: function (result) {
							if (result.data.status == 'success') {
								$('#buckets-form').html(result.data.form);
								$('.iziToast-body .iziToast-buttons').show();
								$('.iziToast-body .spin_loading').removeClass('revealIn');
							}
						}
					});

				},
				buttons: [
					['<div class="iziToastloading spin_loading"></div>'],
					['<button>' + carrot_bunnycdn_incoom_plugin_params.create_title + '</button>', function (instance, toast) {
						var name = $('#buckets-form input[name="incoom_carrot_bunnycdn_incoom_plugin_bucket"]').val();
						if (name != '') {
							create_bucket();
						} else {
							$('#buckets-form input[name="incoom_carrot_bunnycdn_incoom_plugin_bucket"]').addClass('error');
						}
					}],
					['<button>' + carrot_bunnycdn_incoom_plugin_params.close_title + '</button>', function (instance, toast) {
						instance.hide({
							transitionOut: 'fadeOutUp',
						}, toast, 'buttonName');
					}]
				],
				onClosing: function (instance, toast, closedBy) {
					setTimeout(function () {
						location.reload();
					}, 1000);
				}
			});
		});

		$("body").on('click', '#incoom_carrot_bunnycdn_sync_data, #incoom_carrot_bunnycdn_resync_data', function () {
			$(this).addClass('disabled');
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
				message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar" style="overflow: visible;"><span id="percent" style="line-height: 15px;height: 15px;right: -5%;width: 100%;">' + carrot_bunnycdn_incoom_plugin_params.sync_title + '</span><span class="bar" style="opacity: 0;height: 15px;"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
				position: 'topCenter',
				onOpening: function () {
					var data = {
						action: 'incoom_carrot_bunnycdn_incoom_plugin_scaned_sync_data',
						_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
					};
					$.ajax({
						url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
						type: "POST",
						data: data,
						success: function (result) {
							if (result.data.status == 'success') {
								if (result.data.count > 0) {
									setTimeout(function () {
										location.reload();
									}, 1000);
								} else {
									$('#percent').text(result.data.message);
									setTimeout(function () {
										location.reload();
									}, 3000);
								}
							} else {
								$('#percent').text(result.data.message);
							}
						}
					});

				},
				onClosing: function (instance, toast, closedBy) {
					setTimeout(function () {
						location.reload();
					}, 1000);
				}
			});
		});

		$("body").on('click', '#incoom_carrot_bunnycdn_settings_download_files_from_bucket', function () {
			iziToast.show({
				progressBar: true,
				theme: 'dark',
				progressBarColor: 'rgb(0, 255, 184)',
				maxWidth: '500px',
				drag: false,
				overlay: true,
				displayMode: 1,
				pauseOnHover: false,
				timeout: false,
				title: carrot_bunnycdn_incoom_plugin_params.download_title,
				message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar" style="overflow: visible;"><span id="percent" style="line-height: 15px;height: 15px;right: -5%;width: 100%;">' + carrot_bunnycdn_incoom_plugin_params.scan_title + '</span><span class="bar" style="opacity: 0;height: 15px;"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
				position: 'topCenter',
				onOpening: function () {
					$('#percent').text(carrot_bunnycdn_incoom_plugin_params.scanning_step);
					var data = {
						action: 'incoom_carrot_bunnycdn_incoom_plugin_scan_attachments',
						_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
						do_action: 'download_files_from_bucket'
					};
					$.ajax({
						url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
						type: "POST",
						data: data,
						success: function (result) {
							$('#percent').text(result.data.message);
							setTimeout(function () {
								location.reload();
							}, 5000);
						}
					});

				},
				onClosing: function (instance, toast, closedBy) {
					setTimeout(function () {
						location.reload();
					}, 1000);
				}
			});
		});

		$("body").on('click', '#incoom_carrot_bunnycdn_settings_remove_files_from_bucket', function () {
			iziToast.show({
				progressBar: true,
				theme: 'dark',
				progressBarColor: 'rgb(0, 255, 184)',
				maxWidth: '500px',
				drag: false,
				overlay: true,
				displayMode: 1,
				pauseOnHover: false,
				timeout: false,
				title: carrot_bunnycdn_incoom_plugin_params.remove_all_files_from_bucket_title,
				message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar" style="overflow: visible;"><span id="percent" style="line-height: 15px;height: 15px;right: -5%;width: 100%;">' + carrot_bunnycdn_incoom_plugin_params.scan_title + '</span><span class="bar" style="opacity: 0;height: 15px;"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
				position: 'topCenter',
				onOpening: function () {
					$('#percent').text(carrot_bunnycdn_incoom_plugin_params.scanning_step);
					var data = {
						action: 'incoom_carrot_bunnycdn_incoom_plugin_scan_attachments',
						_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
						do_action: 'remove_files_from_bucket'
					};
					$.ajax({
						url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
						type: "POST",
						data: data,
						success: function (result) {
							$('#percent').text(result.data.message);
							setTimeout(function () {
								location.reload();
							}, 5000);
						}
					});

				},
				onClosing: function (instance, toast, closedBy) {
					setTimeout(function () {
						location.reload();
					}, 1000);
				}
			});
		});

		$("body").on('click', '#incoom_carrot_bunnycdn_settings_remove_files_from_server', function () {
			iziToast.show({
				progressBar: true,
				theme: 'dark',
				progressBarColor: 'rgb(0, 255, 184)',
				maxWidth: '500px',
				drag: false,
				overlay: true,
				displayMode: 1,
				pauseOnHover: false,
				timeout: false,
				title: carrot_bunnycdn_incoom_plugin_params.remove_all_files_from_server_title,
				message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar" style="overflow: visible;"><span id="percent" style="line-height: 15px;height: 15px;right: -5%;width: 100%;">' + carrot_bunnycdn_incoom_plugin_params.scan_title + '</span><span class="bar" style="opacity: 0;height: 15px;"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
				position: 'topCenter',
				onOpening: function () {
					$('#percent').text(carrot_bunnycdn_incoom_plugin_params.scanning_step);
					var data = {
						action: 'incoom_carrot_bunnycdn_incoom_plugin_scan_attachments',
						_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
						do_action: 'remove_files_from_server'
					};
					$.ajax({
						url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
						type: "POST",
						data: data,
						success: function (result) {
							$('#percent').text(result.data.message);
							setTimeout(function () {
								location.reload();
							}, 5000);
						}
					});

				},
				onClosing: function (instance, toast, closedBy) {
					setTimeout(function () {
						location.reload();
					}, 1000);
				}
			});
		});

		$("body").on('click', '#incoom_carrot_bunnycdn_settings_copy_files_to_bucket_kill', function () {
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
				title: carrot_bunnycdn_incoom_plugin_params.copy_all_files_to_bucket_title,
				message: carrot_bunnycdn_incoom_plugin_params.confirm_kill_process,
				position: 'topCenter',
				buttons: [
					['<button id="confirm_kill_process_btn">' + carrot_bunnycdn_incoom_plugin_params.confirm_kill_process_btn + '</button>', function (instance, toast) {
						
						if(!$('#confirm_kill_process_btn').hasClass("disabled")){
							var data = {
								action: 'incoom_carrot_bunnycdn_incoom_plugin_copy_all_files_to_bucket_kill_process',
								_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
							};
							$.ajax({
								url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
								type: "POST",
								data: data,
								beforeSend: function(){
									$('#confirm_kill_process_btn').addClass("disabled").text(carrot_bunnycdn_incoom_plugin_params.waiting);
									$('#cancel_kill_process_btn').addClass("disabled");
								},
								success: function (result) {
									setTimeout(function () {
										location.reload();
									}, 1000);
								}
							});
						}

					}, true], // true to focus
					['<button id="cancel_kill_process_btn">' + carrot_bunnycdn_incoom_plugin_params.close_title + '</button>', function (instance, toast) {
						instance.hide({
							transitionOut: 'fadeOutUp',
							onClosing: function (instance, toast, closedBy) {location.reload();}
						}, toast, 'buttonName');
					}]
				],
				onOpened: function () {
					$('.iziToast-body .iziToast-buttons').show();

				},
				onClosing: function (instance, toast, closedBy) {
					location.reload();
				}
			});
		});

		$("body").on('click', '#incoom_carrot_bunnycdn_settings_copy_files_to_bucket', function () {
			iziToast.show({
				progressBar: true,
				theme: 'dark',
				progressBarColor: 'rgb(0, 255, 184)',
				maxWidth: '500px',
				drag: false,
				overlay: true,
				displayMode: 1,
				pauseOnHover: false,
				timeout: false,
				title: carrot_bunnycdn_incoom_plugin_params.copy_all_files_to_bucket_title,
				message: '<div class="iziToastloading spin_loading"></div> ' + '<div class="progress-bar" style="overflow: visible;"><span id="percent" style="line-height: 15px;height: 15px;right: -5%;width: 100%;">' + carrot_bunnycdn_incoom_plugin_params.scan_title + '</span><span class="bar" style="opacity: 0;height: 15px;"><span class="progress"></span></span></div>' + '<div class="progress_count"></div>',
				position: 'topCenter',
				onOpening: function () {
					$('#percent').text(carrot_bunnycdn_incoom_plugin_params.scanning_step);
					var data = {
						action: 'incoom_carrot_bunnycdn_incoom_plugin_scan_attachments',
						_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
						do_action: 'copy_files_to_bucket',
					};
					$.ajax({
						url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
						type: "POST",
						data: data,
						success: function (result) {
							$('#percent').text(result.data.message);
							setTimeout(function () {
								location.reload();
							}, 5000);
						}
					});
				},
				onClosing: function (instance, toast, closedBy) {
					setTimeout(function () {
						location.reload();
					}, 1000);
				}
			});
		});

		$("body").on('click', 'input[name="incoom_carrot_bunnycdn_incoom_plugin_sync_target"]', function () {
			var type = $(this).val();
			var data = {
				type: type,
				action: 'incoom_carrot_bunnycdn_incoom_plugin_sync_render_form',
				_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
			};
			$.ajax({
				url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
				type: "POST",
				data: data,
				beforeSend: function () {
					$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').addClass('active');
				},
				success: function (result) {
					if (result.data.status == 'success') {
						$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-content').html(result.data.html);
						if (type == 'bucket') {
							$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-content .sync-content-bucket').removeClass('hidden');
							$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-content .sync-content-provider-to').addClass('hidden');
						} else {
							$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-content .sync-content-bucket').removeClass('hidden');
							$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-content .sync-content-provider').removeClass('hidden');
						}
					}
					$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-action').addClass('hidden');
					$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').removeClass('active');
				}
			});
		});

		$("body").on('change', '#carrot-bunnycdn-incoom-plugin-wrap .sync-provider', function () {
			var type = $(this).data('target');
			var provider = $(this).val();
			var class_condictions = $('body #carrot-bunnycdn-incoom-plugin-wrap .conditional_' + type);
			var class_target = $('body #carrot-bunnycdn-incoom-plugin-wrap .conditional_' + type + '.show_if_' + provider);
			class_condictions.addClass('hidden');
			class_target.removeClass('hidden');

			class_condictions.find('.conditional_change').val('');

			$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-content-bucket').addClass('hidden');
			$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-action').addClass('hidden');
		});

		$("body").on('change', '#carrot-bunnycdn-incoom-plugin-wrap .conditional_change', function () {
			var parent = $(this).closest('.sync-content-provider-col');
			var type = 'from';

			if (parent.hasClass('sync-content-provider-to')) {
				type = 'to';
			}
			var access_key = parent.find('input[name="incoom_carrot_bunnycdn_connection_access_key_text_' + type + '"]').val();
			var secret_access_key = parent.find('input[name="incoom_carrot_bunnycdn_connection_secret_access_key_text_' + type + '"]').val();
			var credentials_key = parent.find('textarea[name="incoom_carrot_bunnycdn_connection_credentials_' + type + '"]').val();
			var provider = $('.sync-content-provider-' + type + ' .sync-provider').val();
			var region = $('.sync-content-provider-' + type + ' .sync-region').val();

			if (provider == '0') {
				alert(carrot_bunnycdn_incoom_plugin_params.sync_provider_required);
				return false;
			}

			if ((provider == 'google' && credentials_key != '') || (access_key != '' && secret_access_key != '')) {
				var data = {
					type: type,
					provider: parent.find('select[name="incoom_carrot_bunnycdn_connection_provider_' + type + '"]').val(),
					region: region,
					access_key: parent.find('input[name="incoom_carrot_bunnycdn_connection_access_key_text_' + type + '"]').val(),
					secret_access_key: parent.find('input[name="incoom_carrot_bunnycdn_connection_secret_access_key_text_' + type + '"]').val(),
					credentials_key: parent.find('textarea[name="incoom_carrot_bunnycdn_connection_credentials_' + type + '"]').val(),
					action: 'incoom_carrot_bunnycdn_incoom_plugin_sync_render_bucket_form',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				$.ajax({
					url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
					type: "POST",
					data: data,
					beforeSend: function () {
						$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').addClass('active');
					},
					success: function (result) {
						if (result.data.status == 'success') {
							$('body .sync-content-provider-' + type + ' .sync-content-bucket').html(result.data.html).removeClass('hidden');
						}
						$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').removeClass('active');
					}
				});
			}

			$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-action').addClass('hidden');
		});

		$("body").on('change', '#carrot-bunnycdn-incoom-plugin-wrap .sync-content-bucket select', function () {
			var parent = $(this).closest('.sync-content-provider-col');
			var type = $(this).data('target');

			var data = {
				type: type,
				bucket: $(this).val(),
				action: 'incoom_carrot_bunnycdn_incoom_plugin_sync_update_bucket_selected',
				_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
			};
			$.ajax({
				url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
				type: "POST",
				data: data,
				beforeSend: function () {
					$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').addClass('active');
				},
				success: function (result) {
					if (result.data.status == 'done') {
						$('body #carrot-bunnycdn-incoom-plugin-wrap .sync-action').removeClass('hidden');
					}
					$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').removeClass('active');
				}
			});
		});

		if ($('.carrot-sync-notice .current-sync-process').length) {
			function report_sync_data() {
				var data = {
					action: 'incoom_carrot_bunnycdn_incoom_plugin_report_sync_data',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				$.ajax({
					url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
					type: "POST",
					data: data,
					success: function (result) {
						if (result.data.status == 'success') {
							if (result.data.message <= 100) {
								$('.carrot-sync-notice .progress-bar .progress').css({
									width: result.data.message + '%'
								});
								$('.carrot-sync-notice .current-sync-process').text(result.data.message + '%');
							} else {
								setTimeout(function () {
									location.reload();
								}, 1000);
							}
							$('.carrot-sync-notice').removeClass('hidden');
						}
						if (parseInt(result.data.sync) == 1) {
							setTimeout(function () {
								location.reload();
							}, 1000);
						}
					}
				});
			}
			setInterval(report_sync_data, 3000);
			report_sync_data();
		}

		//copy_all_files_to_bucket_check_process
		if ($('#incoom_carrot_bunnycdn_settings_copy_files_to_bucket_kill').length) {
			function copy_all_files_to_bucket_check_process() {
				var data = {
					action: 'incoom_carrot_bunnycdn_incoom_plugin_copy_all_files_to_bucket_check_process',
					_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
				};
				$.ajax({
					url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
					type: "POST",
					data: data,
					success: function (result) {
						let action = result.data.action;
						if(action == ''){
							location.reload();
						}else{
							if (result.data.status == 'success') {
								if (result.data.message < 100) {
									$('.action_'+ action +' .progress-bar .progress').css({
										width: result.data.message + '%'
									});
									$('.action_'+ action +' #percent').text(result.data.message + '%');
									$('.action_'+ action +' .current-sync-process').text(result.data.count);

									if(result.data.output && result.data.output.length > 0){
										if($('.action_'+ action +' .output').length > 0){
											let html = '<ul>';
											$.each(result.data.output, function(index, item) {
												html += '<li>'+ item +'</li>';
											});
											html += '</ul>';
											$('.action_'+ action +' .output').html(html).removeClass('hidden');
										}
									}

								} else {
									if(result.data.message == 100 && result.data.step == 1){
										var data = {
											sync_action: result.data.action,
											action: 'incoom_carrot_bunnycdn_incoom_plugin_set_step_sync',
											_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
										};
										$.ajax({
											url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
											type: "POST",
											data: data,
											success: function (result) {
												location.reload();
											}
										});

									}
								}
							}

							if(result.data.step == 2 && $('.action_'+ action +' .step-1').length > 0){
								location.reload();
							}

							if(result.data.step == 3){
								$('.action_'+ action +' .progress-bar .progress').css({
									width: '100%'
								});
								$('.action_'+ action +' #percent').text('100%');
								location.reload();
							}
						}
					}
				});
			}
			setInterval(copy_all_files_to_bucket_check_process, 50000);
			copy_all_files_to_bucket_check_process();
		}

	});

	$('#incoom_carrot_bunnycdn_update_cache_control').on('click', function () {
		$('input[name="incoom_carrot_bunnycdn_update_cache_control"]').val('1');
		$('#carrot-bunnycdn-incoom-plugin-wrap form').submit();
	});

	// Export
	$("body").on('click', '#incoom_carrot_bunnycdn_export', function () {
		var data = {
			action: 'incoom_carrot_bunnycdn_incoom_plugin_export',
			_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
		};
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			beforeSend: function () {
				$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').addClass('active');
			},
			success: function (result) {
				if (result.data.status == 'success') {
					var data = JSON.stringify(result.data.data);
					var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(data);
					var dlAnchorElem = document.getElementById('downloadAnchorElem');
					dlAnchorElem.setAttribute("href",     dataStr     );
					dlAnchorElem.setAttribute("download", "carrot_bunnycdn_incoom_plugin_settings.json");
					dlAnchorElem.click();
				}
				$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').removeClass('active');
			}
		});
	});

	// Import
	$("body").on('change', '#incoom_carrot_bunnycdn_import_content', function () {
		if($(this).val() != ''){
			$('#incoom_carrot_bunnycdn_import').removeAttr('disabled');
		}
	});
	$("body").on('click', '#incoom_carrot_bunnycdn_import', function () {
		var data = {
			content: JSON.parse($('#incoom_carrot_bunnycdn_import_content').val()),
			action: 'incoom_carrot_bunnycdn_incoom_plugin_import',
			_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
		};
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			beforeSend: function () {
				$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').addClass('active');
			},
			success: function (result) {
				if (result.data.status == 'success') {
					iziToast.success({
						message: carrot_bunnycdn_incoom_plugin_params.imported_ok
					});
				}else{
					iziToast.error({
						message: carrot_bunnycdn_incoom_plugin_params.imported_error
					});
				}
				$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').removeClass('active');
			}
		});
	});

	$("body").on('change', 'input[name="incoom_carrot_bunnycdn_incoom_plugin_cdn"]', function () {
		let val = $(this).val(),
		cname = $('input[name="incoom_carrot_bunnycdn_incoom_plugin_cname"]').val();
		if(val != 'default' && cname === ''){
			iziToast.error({
				message: carrot_bunnycdn_incoom_plugin_params.add_cdn_error
			});
			$('input[name="incoom_carrot_bunnycdn_incoom_plugin_cname"]').prop('required', true);
			$('input[name="incoom_carrot_bunnycdn_incoom_plugin_cname"]').addClass('error');
		}else{
			$('input[name="incoom_carrot_bunnycdn_incoom_plugin_cname"]').removeClass('error');
			$('input[name="incoom_carrot_bunnycdn_incoom_plugin_cname"]').prop('required', false);
		}
	});


	$("body").on('click', '#incoom_carrot_bunnycdn_change_links_download', function () {
		var data = {
			action: 'incoom_carrot_bunnycdn_incoom_plugin_scan_links_download',
			_wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
		};
		$.ajax({
			url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
			type: "POST",
			data: data,
			beforeSend: function () {
				$('body #change_links_download_header').addClass('hidden');
				$('body #change_links_download_content').removeClass('hidden');
			},
			success: function (result) {
				if (result.data.status == 'success') {
					setTimeout(function () {
						$('body #change_links_download_header').addClass('hidden');
						$('body #change_links_download_content').addClass('hidden');
						$('body #change_links_download_footer').removeClass('hidden');
					}, 5000);
				}
				$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').removeClass('active');
			}
		});
	});
})(jQuery);