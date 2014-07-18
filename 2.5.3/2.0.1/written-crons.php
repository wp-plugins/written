<?php
	add_filter( 'cron_schedules', 'wtt_cron_add_ten_minutes' );
 
	function wtt_cron_add_ten_minutes( $schedules ) {
		// Adds once weekly to the existing schedules.
		$schedules['tenminutes'] = array(
			'interval' => 600,
			'display' => __( 'Ten Minutes' )
		);
		return $schedules;
	}

	add_filter( 'cron_schedules', 'wtt_cron_add_two_minutes' );
	 
	function wtt_cron_add_two_minutes( $schedules ) {
		// Adds once weekly to the existing schedules.
		$schedules['twominutes'] = array(
			'interval' => 120,
			'display' => __( 'Two Minutes' )
		);
		return $schedules;
	}

	add_filter( 'cron_schedules', 'wtt_cron_add_one_minute' );
	 
	function wtt_cron_add_one_minute( $schedules ) {
		// Adds once weekly to the existing schedules.
		$schedules['oneminute'] = array(
			'interval' => 60,
			'display' => __( 'One Minutes' )
		);
		return $schedules;
	}
?>