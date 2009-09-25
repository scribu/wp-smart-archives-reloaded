jQuery(document).ready(function($) {
	var $type = $('#general td:last');
	var cur_val = $type.find(':checked').val();

	var $list_format = $('#specific tr:first');
	var $date_format = $list_format.find('+ tr');
	var $numeric = $date_format.find('+ tr');
	var $anchor = $numeric.find('+ tr');

	if ( cur_val == 'block' )
		$list_format.hide();

	if ( cur_val == 'list' )
		$numeric.hide();

	if ( cur_val != 'both' )
		$anchor.hide();

	$type.find(':radio').click(function() {
		var radio_val = $(this).val();

		( radio_val == 'list' ) ? $numeric.fadeOut() : $numeric.fadeIn();

		( radio_val == 'block' ) ? $list_format.fadeOut() : $list_format.fadeIn();
		( radio_val == 'block' ) ? $date_format.fadeOut() : $date_format.fadeIn();

		( radio_val != 'both' ) ? $anchor.fadeOut() : $anchor.fadeIn();
	});
});
