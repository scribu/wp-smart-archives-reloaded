jQuery(document).ready(function($) {
	var $type = $('td:first');
	var $numeric = $('tr:eq(1)');
	var $anchor = $('tr:eq(2)');
	var cur_val = $type.find(':checked').val();

	if ( cur_val == 'list' )
		$numeric.hide();

	if ( cur_val != 'both' )
		$anchor.hide();

	$type.find(':radio').click(function() {
		if ( $(this).val() == 'list' )
			$numeric.fadeOut();
		else
			$numeric.fadeIn();

		if ( $(this).val() != 'both' )
			$anchor.fadeOut();
		else
			$anchor.fadeIn();
	});
});
