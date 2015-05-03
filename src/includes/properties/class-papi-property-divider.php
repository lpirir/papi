<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Papi Property Divider.
 *
 * @package Papi
 * @since 1.0.0
 */

class Papi_Property_Divider extends Papi_Property {

	/**
	 * Display property html.
	 *
	 * @since 1.0.0
	 */

	public function html() {
		$options = $this->get_options();
		?>
		<h3 class="hndle papi-property-divider">
			<span><?php echo $options->title; ?></span>
		</h3>

		<?php if ( ! papi_is_empty( $options->description ) ): ?>
			<p><?php echo $options->description; ?></p>
		<?php endif; ?>
	<?php
	}

	/**
	 * Render the final html that is displayed in a table.
	 *
	 * @since 1.0.0
	 */

	public function render() {
		?>
		<tr class="papi-fullwidth">
			<td colspan="2">
				<?php $this->html(); ?>
			</td>
		</tr>
	<?php
	}

}
