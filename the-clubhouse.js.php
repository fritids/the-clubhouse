<?php
header("Content-type: text/javascript");
?>
/* <script> */
jQuery(document).ready(function($) {

	// Player Score Character Limiting
	$('input.player-scores').bind('keyup', function() {
		$(this).val($(this).val().replace(/[^0-9,]/g, ""));
	});

	// Initialize Sortables
	$( "#sortable" ).sortable();

});

/* </script> */