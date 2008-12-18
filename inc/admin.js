jQuery(function($) {
	$(document).ready(function() {
		box = $('tr:eq(1)');

		if ( $('td:first :checked').val() != 'both' )
			box.css("display","none");

		$('td:first :radio').bind('click', function() {
			if ( $(this).val() != 'both' )
				box.css("display","none");
			else
				box.css("display","table-row");
		});
	});
});
