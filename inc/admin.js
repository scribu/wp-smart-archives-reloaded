jQuery(document).ready(function($) {
	var $type = $('#general td:last');
	var cur_val = $type.find(':checked').val();

	var $list_format = $('#specific tr:first');
	var $numeric = $list_format.find('+ tr');
	var $anchor = $numeric.find('+ tr');

	if ( cur_val == 'block' )
		$list_format.hide();

	if ( cur_val == 'list' )
		$numeric.hide();

	if ( cur_val != 'both' )
		$anchor.hide();

	$type.find(':radio').click(function() {
		( $(this).val() == 'list' ) ? $numeric.fadeOut() : $numeric.fadeIn();

		( $(this).val() == 'block' ) ? $list_format.fadeOut() : $list_format.fadeIn();

		( $(this).val() != 'both' ) ? $anchor.fadeOut() : $anchor.fadeIn();
	});
});
