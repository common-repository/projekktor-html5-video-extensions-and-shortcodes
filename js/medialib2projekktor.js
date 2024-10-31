jQuery(document).ready(function() {
	jQuery('a.thickbox').click(function() {
        var destId = jQuery(this).attr('id').replace(/\_ml/g, '');
        window.send_to_editor = function (html) {
            try {        
                var mediaUrl = html.match(/src=[\'|\"](.+?[\.jpg|\.gif|\.png])[\'|\"]/)[1];
                jQuery('#'+destId).val(mediaUrl);
                tb_remove();
            } catch(e){tb_remove();};
        }
    });
});