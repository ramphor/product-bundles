<?php
function ramphor_product_bundles_get_allowed_html( $case ) {
	$allowed_html = array();

	switch ( $case ) {
		case 'inline':
			$allowed_html = array(

				// Formatting.
				'strong' => array(),
				'em'     => array(),
				'b'      => array(),
				'i'      => array(),
				'span'   => array(
					'class' => array(),
				),

				// Links.
				'a'      => array(
					'href'   => array(),
					'target' => array(),
				),
			);
			break;

		default:
			break;
	}

		return $allowed_html;
}
