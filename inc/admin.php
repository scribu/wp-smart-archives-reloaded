<?php
class smartArchivesAdmin extends smartArchives {
	var $options = array(
		'format' => 'both',
		'catID' => ''
	);

	function __construct() {
		$this->cachefile = dirname(dirname(__FILE__)) . '/cache.txt';

		add_action('admin_menu', array(&$this, 'page_init'));
	}

	function install() {
		add_option('smart-archives', $this->options);
		wp_schedule_event(time(), 'daily', 'smart_archives_update');
	}

	// Options Page
	function page_init() {
		if ( current_user_can('manage_options') )
			add_options_page('Smart Archives', 'Smart Archives', 8, 'smart-archives', array(&$this, 'page'));
	}

	function page() {
		$this->options = get_option('smart-archives'); // load options

		// Update options
		if ( $_POST['submit-options'] ) {
			foreach ($this->options as $name => $value)
				$newoptions[$name] = $_POST[$name];

			if($newoptions != $this->options) {
				$this->options = $newoptions;
				update_option('smart-archives', $this->options);

				$this->generate(); // rebuild the archive with changed settings
			}

			echo '<div class="updated"><p>Options <strong>saved</strong>.</p></div>';
		}
	?>
<div class="wrap">
<h2>Smart Archives Options</h2>
<form name="sar-options" method="post" action="<?= str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<table class="form-table">
		<tr>
			<th scope="row" valign="top">Format</th>
			<td>
			<?php foreach (array('block', 'list', 'both') as $value) { ?>
				<input type="radio"<?php if ($value === $this->options['format']) echo ' checked="checked"'; ?> name="format" value="<?php echo $value; ?>" />
				<label><?php echo $value; ?></label>
				<br class="clear" />
			<?php } ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Exclude Categories by ID</th>
			<td>
				<input type="text" name="catID" value="<?php echo $this->options['catID']; ?>" style="width: 250px" />
				<label>A list of category IDs (space separated) that you want to exclude from the list archives</label>
			</td>
		</tr>
	</table>

	<p class="submit">
	<input type="submit" name="submit-options" value="Save Options" />
	</p>
</form>

</div>
	<?php
	}
}

$smartArchivesAdmin = new smartArchivesAdmin();
?>
