(function ($) {
	"use strict";

	let ajax_url = carrot_bunnycdn_incoom_plugin_params.ajax_url

    let actionText = ''

    // Leave step event is used for validating the forms
    $("#smartwizard").on("leaveStep", function(e, anchorObject, currentStepIdx, nextStepIdx, stepDirection) {
        let actionValue = $('#media_action').val()
        if(actionValue != ''){
            if(actionValue === 'copy_files_to_bucket'){
                actionText = carrot_bunnycdn_incoom_plugin_params.copy_to_s3_text
            } else if(actionValue === 'remove_files_from_server'){
                actionText = carrot_bunnycdn_incoom_plugin_params.remove_from_server_text
            } else if(actionValue === 'remove_files_from_bucket'){
                actionText = carrot_bunnycdn_incoom_plugin_params.remove_from_s3_text
            } else if(actionValue === 'download_files_from_bucket'){
                actionText = carrot_bunnycdn_incoom_plugin_params.copy_to_server_from_s3_text
            }
            $("#btnFinish").text(actionText)
        }
        // Validate only on forward movement  
        if (stepDirection == 'forward') {
            let form = document.getElementById('form-' + (currentStepIdx + 1));
            if (form) {
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    $('#smartwizard').smartWizard("setState", [currentStepIdx], 'error');
                    $("#smartwizard").smartWizard('fixHeight');
                    return false;
                }
                $('#smartwizard').smartWizard("unsetState", [currentStepIdx], 'error');
            }
        }
    });

    // Step show event
    $("#smartwizard").on("showStep", function(e, anchorObject, stepIndex, stepDirection, stepPosition) {
        
        $("#prev-btn").removeClass('disabled').prop('disabled', false);
        $("#next-btn").removeClass('disabled').prop('disabled', false);

        if(stepPosition === 'first') {
            $("#prev-btn").addClass('hidden disabled').prop('disabled', true);
        } else if(stepPosition === 'last') {
            $("#next-btn").addClass('hidden disabled').prop('disabled', true);
        } else {
            $("#prev-btn").removeClass('hidden disabled').prop('disabled', false);
            $("#next-btn").removeClass('hidden disabled').prop('disabled', false);
        }

        // Get step info from Smart Wizard
        let stepInfo = $('#smartwizard').smartWizard("getStepInfo");
        $("#sw-current-step").text(stepInfo.currentStep + 1);
        $("#sw-total-step").text(stepInfo.totalSteps);

        if (stepPosition == 'last') {
            $("#btnFinish").removeClass('hidden');
            $("#btnFinish").prop('disabled', false);
        } else {
            $("#btnFinish").addClass('hidden');
            $("#btnFinish").prop('disabled', true);
        }

        // Focus first name
        if (stepIndex == 1) {
            setTimeout(() => {
                $('#media_action').focus();
            }, 0);
        }
    });

    $('#smartwizard').smartWizard({
        theme: 'round', // basic, arrows, square, round, dots
        enableUrlHash: false,
        toolbar: {
            showNextButton: true, // show/hide a Next button
            showPreviousButton: true, // show/hide a Previous button
            position: 'bottom', // none/ top/ both bottom
            extraHtml: `<button class="btn btn-success sw-btn hidden" id="btnFinish" disabled></button>`
        },
        anchor: {
            enableNavigation: true, // Enable/Disable anchor navigation 
            enableNavigationAlways: false, // Activates all anchors clickable always
            enableDoneState: true, // Add done state on visited steps
            markPreviousStepsAsDone: true, // When a step selected by url hash, all previous steps are marked done
            unDoneOnBackNavigation: true, // While navigate back, done state will be cleared
            enableDoneStateNavigation: true // Enable/Disable the done state navigation
        },
        justified: false,
        lang: { // Language variables for button
            next: carrot_bunnycdn_incoom_plugin_params.btn_next,
            previous: carrot_bunnycdn_incoom_plugin_params.btn_previous
        },
    });

    $("body").on('click', '#btnFinish', function () {
        let actionValue = $('#media_action').val()
        let sendEmailTask = $('#incoom_carrot_bunnycdn_incoom_plugin_send_email_task').is(':checked') ? 'on' : 'off'
        let data = {
            action: 'incoom_carrot_bunnycdn_incoom_plugin_one_click_init',
            _wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce,
            do_action: actionValue,
            send_email_task: sendEmailTask
        };
        $.ajax({
            url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
            type: "POST",
            data: data,
            beforeSend: function () {
                $('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').addClass('active');
            },
            success: function (result) {
                if(result.data){
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                }
				$('body #carrot-bunnycdn-incoom-plugin-wrap .incoom_carrot_bunnycdn_loading').removeClass('active');
            }
        });
    });

    if ($('.oneclick-wrap .current-sync-process').length) {
        function report_sync_data() {
            var data = {
                action: 'incoom_carrot_bunnycdn_incoom_plugin_one_click_check_process',
                _wpnonce: carrot_bunnycdn_incoom_plugin_params.ajax_nonce
            };
            $.ajax({
                url: carrot_bunnycdn_incoom_plugin_params.ajax_url,
                type: "POST",
                data: data,
                success: function (result) {
                    if (result.data.status == 'success') {
                        if(result.data.count['total'] !== undefined){
                            if(result.data.message == 100 || result.data.count['total'] == 0){
                                setTimeout(function () {
                                    location.reload();
                                }, 1000);
                            }else{
                                $('.oneclick-wrap #percent').text(result.data.message + '%')
                                $('.oneclick-wrap .progress_count').text(result.data.count.count)
                                $('.oneclick-wrap .progress-bar .progress').css({
                                    width: result.data.message + '%'
                                });
                            }
                        }else{
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        }
                    }
                }
            });
        }
        setInterval(report_sync_data, 30000);
        report_sync_data();
    }

})(jQuery);