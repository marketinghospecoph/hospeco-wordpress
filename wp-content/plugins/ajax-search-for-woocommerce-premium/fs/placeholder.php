<?php

/**
 * @dgwt_wcas_premium_only
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FiboSearchFreemiusPlaceholder {
	function is__premium_only() {
		return true;
	}
}

function dgoraAsfwFs() {
	global $dgoraAsfwFsPlaceholder;

	if ( ! isset( $dgoraAsfwFsPlaceholder ) ) {
		$dgoraAsfwFsPlaceholder = new FiboSearchFreemiusPlaceholder();
	}

	return $dgoraAsfwFsPlaceholder;
}

dgoraAsfwFs();
