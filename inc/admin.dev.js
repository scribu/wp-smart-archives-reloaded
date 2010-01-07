jQuery(document).ready(function($) {
	var $type = $('#general td:last');
	var cur_val = $type.find(':checked').val();

	var $list_format = $('#specific tr:first');
	var $date_format = $list_format.find('+ tr');
	var $month_format = $date_format.find('+ tr');
	var $anchor = $month_format.find('+ tr');

//console.log($list_format, $date_format, $month_format, $anchor);

	if ( cur_val == 'block' )
		$list_format.hide();

	if ( cur_val == 'list' )
		$month_format.hide();

	if ( cur_val != 'both' )
		$anchor.hide();

	var hide_if = function(cond, $el) {
		cond ? $el.fadeOut() : $el.fadeIn();
	}

	$type.find(':radio').click(function() {
		var radio_val = $(this).val();

		hide_if(radio_val == 'list', $month_format);

		hide_if(radio_val == 'block', $list_format);
		hide_if(radio_val == 'block', $date_format);

		hide_if(radio_val != 'both', $anchor);
	});
});
