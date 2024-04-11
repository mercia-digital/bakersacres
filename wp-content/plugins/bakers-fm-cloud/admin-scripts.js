jQuery(document).ready(function($) {
    $('#BakersFM_GetProductionPlan').click(function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bakers_fm_manual_import',
            },
            success: function(response) {
                alert('Script executed!'); // Or handle the response as needed
            }
        });
    });
});