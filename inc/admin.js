jQuery(function($) {
	$(document).ready(function() {
		numeric = $('tr:eq(1)');
		anchor = $('tr:eq(2)');
		cur_val = $('td:first :checked').val();

		if ( cur_val == 'list' )
			numeric.hide();

		if ( cur_val != 'both' )
			anchor.hide();

		$('td:first :radio').bind('click', function() {
			if ( $(this).val() == 'list' )
				numeric.fadeOut();
			else
				numeric.fadeIn();

			if ( $(this).val() != 'both' )
				anchor.fadeOut();
			else
				anchor.fadeIn();
		});
	});
});
