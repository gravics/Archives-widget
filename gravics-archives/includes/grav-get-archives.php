<?php
function grav_get_archives($args = '') {
	global $wpdb, $wp_locale;

	$defaults = array(
		'type' => 'monthly', 'limit' => '',
		'format' => 'html', 'before' => '',
		'after' => '', 'show_post_count' => false,
		'drop' => false,
		'echo' => 1, 'order' => 'DESC',
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( '' == $type )
		$type = 'monthly';

	if ( '' != $limit ) {
		$limit = absint($limit);
		$limit = ' LIMIT '.$limit;
	}

	$order = strtoupper( $order );
	if ( $order !== 'ASC' )
		$order = 'DESC';

	/**
	 * Filter the SQL WHERE clause for retrieving archives.
	 *
	 * @since 2.2.0
	 *
	 * @param string $sql_where Portion of SQL query containing the WHERE clause.
	 * @param array  $r         An array of default arguments.
	 */
	$where = apply_filters( 'getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish'", $r );

	/**
	 * Filter the SQL JOIN clause for retrieving archives.
	 *
	 * @since 2.2.0
	 *
	 * @param string $sql_join Portion of SQL query containing JOIN clause.
	 * @param array  $r        An array of default arguments.
	 */
	$join = apply_filters( 'getarchives_join', '', $r );

	$output = '';

	$last_changed = wp_cache_get( 'last_changed', 'posts' );
	if ( ! $last_changed ) {
		$last_changed = microtime();
		wp_cache_set( 'last_changed', $last_changed, 'posts' );
	}

	if ( 'monthly' == $type ) {
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date $order $limit";
		$key = md5( $query );
		$key = "wp_get_archives:$key:$last_changed";
		if ( ! $results = wp_cache_get( $key, 'posts' ) ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'posts' );
		}
		if ( $results && $drop == true ) {
			$afterafter = $after;
			foreach ( (array) $results as $result ) {
				$url = get_month_link( $result->year, $result->month );
				/* translators: 1: month name, 2: 4-digit year */
				$text = sprintf(__('%1$s %2$d'), $wp_locale->get_month($result->month), $result->year);
				if ( $show_post_count )
					$after = '&nbsp;('.$result->posts.')' . $afterafter;
				$output .= get_archives_link($url, $text, $format, $before, $after);
			}
		}
		elseif ( $results && $drop == false ) {
			$afterafter = $after;
			foreach ( (array) $results as $result ) {
				$url = get_month_link( $result->year, $result->month );
				/* translators: 1: month name, 2: 4-digit year */
				$text = sprintf(__('%1$s %2$d'), $wp_locale->get_month($result->month), $result->year);
				if ( $show_post_count )
					$text .= '&nbsp;<span>('.$result->posts.')</span>' . $afterafter;
				$output .= get_archives_link($url, $text, $format, $before, $after, $drop);
			}
		}
	}
	if ( $echo )
		echo $output;
	else
		return $output;
}