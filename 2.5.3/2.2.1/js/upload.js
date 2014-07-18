jQuery(document).ready(function($){

	$('#wtt_logo_url_button').click(function(e) {
	    e.preventDefault();

	    var custom_uploader = wp.media({
	        title: 'Takeover Logo',
	        button: {
	            text: 'Select Image'
	        },
	        multiple: false  // Set this to true to allow multiple files to be selected
	    })
	    .on('select', function() {
	        var attachment = custom_uploader.state().get('selection').first().toJSON();
	        $('.wtt_logo_image').attr('src', attachment.url);
		    $('#wtt_logo_url').val(attachment.url);
	    })
	    .open();
	});

});