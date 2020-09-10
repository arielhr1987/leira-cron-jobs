<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Leira_Cron_Jobs_List_Table
 */
class Leira_Cron_Jobs_List_Table extends WP_List_Table{

	/**
	 * The cron jobs manager
	 *
	 * @var Leira_Cron_Jobs_Manager
	 */
	protected $manager;

	/**
	 * Leira_Cron_Jobs_List_Table constructor.
	 *
	 * @param array $args
	 */
	function __construct( $args = array() ) {
		global $status, $page;


		//Set parent defaults
		parent::__construct( array(
			'singular' => 'cron-job',     //singular name of the listed records
			'plural'   => 'cron-jobs',    //plural name of the listed records
			'ajax'     => true            //does this table support ajax?
		) );

		$this->manager = leira_cron_jobs()->manager;

	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag.
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', /*'striped',*/ $this->_args['plural'] );
	}

	/**
	 * @param array $item .
	 */
	public function single_row( $item ) {
		$class = array( 'cron-job-tr' );

		if ( ( $item['time'] - time() ) <= 0 ) {
			$class[] = 'active'; //running
		}

		if ( $item['action'] === false ) {
			$class[] = 'orphan';
		}
		$job_key = sprintf( 'cron-job-%s', md5( implode( '_', array(
			$item['time'],
			$item['event'],
			$item['md5']
		) ) ) );
		echo sprintf( '<tr id="%s" class="%s">', $job_key, implode( ' ', $class ) );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox"/>', //Render a checkbox instead of text
			'title'    => __( 'Job', 'leira-cron-jobs' ),
			'action'   => __( 'Action', 'leira-cron-jobs' ),
			'args'     => __( 'Arguments', 'leira-cron-jobs' ),
			'schedule' => __( 'Schedule', 'leira-cron-jobs' ),
			'time'     => __( 'Next execution', 'leira-cron-jobs' )
		);

		return $columns;
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 * @since 3.1.0
	 *
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'title'    => array( 'event', true ),
			'action'   => array( 'action', true ),
			'args'     => array( 'args', true ),
			'schedule' => array( 'schedule', true ),
			'time'     => array( 'time', true )
		);

		return $sortable_columns;
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @param object $item        The item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @return string The row actions HTML, or an empty string if the current column is the primary column.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		//Build row actions
		$job_key = 'job[' . $item['time'] . '][' . $item['event'] . ']';
		$actions = array(
			'delete' => sprintf( '<a href="%s" class="" onclick="return confirm(\'%s\')">%s</a>',
				add_query_arg( array(
					'page'     => 'leira-cron-jobs',
					'action'   => 'delete',
					$job_key   => $item['md5'],
					'_wpnonce' => wp_create_nonce( 'bulk-cron-jobs' )
				), admin_url( 'tools.php' ) ),
				__( 'Are you sure you want to delete this cron job?', 'leira-cron-jobs' ),
				__( 'Delete', 'leira-cron-jobs' )
			),
		);

		/**
		 * We can't run or edit a cron job with missing action
		 */
		if ( ! empty( $item['action'] ) ) {

			$actions = array_merge( array(
				'run'                  => sprintf( '<a href="%s" class="">%s</a>',
					add_query_arg( array(
						'page'     => 'leira-cron-jobs',
						'action'   => 'run',
						$job_key   => $item['md5'],
						'_wpnonce' => wp_create_nonce( 'bulk-cron-jobs' )
					), admin_url( 'tools.php' ) ),
					__( 'Run now', 'leira-cron-jobs' )
				),
				'inline hide-if-no-js' => sprintf(
					'<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
					esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline', 'leira-cron-jobs' ), $item['event'] ) ),
					__( 'Quick&nbsp;Edit', 'leira-cron-jobs' )
				)
			), $actions );
		}

		return $column_name === $primary ? $this->row_actions( $actions, false ) : '';
	}

	/**
	 * Add default
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return mixed|string|void
	 */
	protected function column_default( $item, $column_name ) {
		return ! empty( $item[ $column_name ] ) ? $item[ $column_name ] : '&mdash;';
	}

	/**
	 * The checkbox column
	 *
	 * @param object $item
	 *
	 * @return string|void
	 */
	protected function column_cb( $item ) {
		$job_key = 'job[' . $item['time'] . '][' . $item['event'] . ']';

		return sprintf( '<input type="checkbox" name="%1$s" value="%2$s" />', $job_key, $item['md5'] );
	}

	/**
	 * The event column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	protected function column_title( $item ) {
		$job_key = sprintf( '%s', implode( '_', array( $item['time'], $item['event'], $item['md5'] ) ) );
		$job_key = md5( $job_key );

		$out = sprintf( '<strong>%1$s</strong>', $item['event'] );

		$out .= '<div class="hidden" id="inline_' . $job_key . '">';
		foreach ( $item as $key => $value ) {
			$out .= sprintf( '<div class="%s">%s</div>', $key, $value );
		}
		$out .= '</div>';

		return $out;
	}

	/**
	 * The action column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	protected function column_action( $item ) {
		//return ! empty( $item['action'] ) ? sprintf( '<pre>%1$s</pre>', $item['action'] ) : '&mdash;';
		return ! empty( $item['action'] ) ? $item['action'] : '&mdash;';
	}

	/**
	 * The args column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	protected function column_args( $item ) {
		$args = $item['args'];
		$args = ! empty( $args ) ? $args : '&mdash;';

		return $args;
	}

	/**
	 * The schedule column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	protected function column_schedule( $item ) {
		$schedules = wp_get_schedules();
		$schedule  = $item['schedule'];
		if ( $schedule === '__single_run' ) {
			$schedule = __( 'Single Run', 'leira-cron-jobs' );
		} elseif ( isset( $schedules[ $schedule ]['display'] ) ) {
			$schedule = $schedules[ $schedule ]['display'];
		}

		return sprintf( '%1$s', $schedule );
	}

	/**
	 * The time column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	protected function column_time( $item ) {
		$time      = $item['time'];
		$time_diff = $time - time();

		if ( $time_diff > 0 ) {
			//it will run in the future
			$h_diff = sprintf( __( 'In %s', 'leira-cron-jobs' ), human_time_diff( $time ) );
		} else {
			//run ASAP
			$h_diff = __( 'Now', 'leira-cron-jobs' );
		}
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$tzstring    = get_option( 'timezone_string' );
		$offset      = get_option( 'gmt_offset' );

		//User timezone defined in settings
		$h_date      = date_i18n( $date_format, $time + ( $offset * HOUR_IN_SECONDS ) );
		$h_time      = date_i18n( $time_format, $time + ( $offset * HOUR_IN_SECONDS ) );
		$h_date_time = $h_date . ' ' . $h_time;

		//GMT timezone
		$h_date_gmt      = date_i18n( $date_format, $time );
		$h_time_gmt      = date_i18n( $time_format, $time );
		$h_date_time_gmt = "UTC " . $h_date_gmt . ' ' . $h_time_gmt;

		return sprintf( '<span>%1$s</span><br><abbr title="%2$s" class="date-time-field" data-utc-time="%4$s" data-date-format="%5$s" data-time-format="%6$s">%3$s</abbr>', $h_diff, $h_date_time_gmt, $h_date_time_gmt, $time, $date_format, $time_format );
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'run'    => __('Run now', 'leira-cron-jobs'),
			'delete' => __('Delete', 'leira-cron-jobs')
		);

		return $actions;
	}

	/**
	 * Process bulk actions
	 */
	protected function process_bulk_action() {

		$query_arg = '_wpnonce';
		$action    = 'bulk-' . $this->_args['plural'];
		$checked   = $result = isset( $_REQUEST[ $query_arg ] ) ? wp_verify_nonce( $_REQUEST[ $query_arg ], $action ) : false;

		if ( ! $checked ) {
			return;
		}

		$current_action = $this->current_action();
		//Detect when a bulk action is being triggered...
		switch ( $current_action ) {
			case 'run':
				//wp_die( 'Items deleted (or they would be if we had items to delete)!' );
				break;
			case 'delete':
				//wp_die( 'Items deleted (or they would be if we had items to delete)!' );
				break;
			default:

		}
	}

	/**
	 * Determine if a given string contains a given substring.
	 *
	 * @param string       $haystack
	 * @param string|array $needles
	 * @param bool         $sensitive Use case sensitive search
	 *
	 * @return bool
	 */
	public function str_contains( $haystack, $needles, $sensitive = true ) {
		foreach ( (array) $needles as $needle ) {
			$function = $sensitive ? 'mb_strpos' : 'mb_stripos';
			if ( $needle !== '' && $function( $haystack, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Display the schedule filters views
	 *
	 * @return array
	 */
	public function get_views() {
		$views     = array();
		$tasks     = $this->manager->get_crons();
		$schedules = wp_get_schedules();
		$schedules = array_merge( array(
			'__all'        => array(
				'display' => __( 'All', 'leira-cron-jobs' ),
				'count'   => 0
			),
			'__single_run' => array(
				'display' => __( 'Single Run', 'leira-cron-jobs' ),
				'count'   => 0
			)
		), $schedules );
		$status    = isset( $_REQUEST['filter'] ) && isset( $schedules[ $_REQUEST['filter'] ] ) ? $_REQUEST['filter'] : '__all';

		//determine task count per schedule
		foreach ( $tasks as $task ) {

			$schedules['__all']['count'] ++;
			if ( isset( $schedules[ $task['schedule'] ] ) ) {
				if ( ! isset( $schedules[ $task['schedule'] ]['count'] ) ) {
					$schedules[ $task['schedule'] ]['count'] = 0;
				}
				$schedules[ $task['schedule'] ]['count'] ++;
			}
		}

		//build view links
		foreach ( $schedules as $schedule => $details ) {
			$text  = isset( $details['display'] ) ? $details['display'] : __( $schedule, 'leira-cron-jobs' );
			$count = isset( $details['count'] ) ? $details['count'] : null;
			if ( ! $count ) {
				continue;
			}

			$views[ $schedule ] = sprintf( '<a href="%s"%s>%s</a>',
				add_query_arg( 'filter', $schedule ),
				( $schedule === $status ) ? sprintf( ' class="current" aria-current="%s"', 'page' ) : '',
				sprintf( '%s <span class="count">(%s)</span>', $text, number_format_i18n( $count ) )
			);
		}

		return $views;
	}

	/**
	 * Displays the search box.
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 *
	 * @since 3.1.0
	 *
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['filter'] ) ) {
			echo '<input type="hidden" name="filter" value="' . esc_attr( $_REQUEST['filter'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['page'] ) ) {
			echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
		}
		?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s"
                   value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
        </p>
		<?php
	}

	/**
	 *
	 */
	public function prepare_items() {

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = $this->get_items_per_page( str_replace( '-', '_', $this->screen->id . '_per_page' ), 999 );

		/**
		 * handle bulk actions.
		 */
		$this->process_bulk_action();

		/**
		 * Fetch the data
		 */
		$data = $this->manager->get_crons();

		/**
		 * Handle search
		 */
		if ( ( ! empty( $_REQUEST['s'] ) ) && $search = $_REQUEST['s'] ) {
			//$_SERVER['REQUEST_URI'] = add_query_arg( 's', $search );
			$data_filtered = array();
			foreach ( $data as $item ) {
				if ( $this->str_contains( $item['event'], $search, false ) ) {
					$data_filtered[] = $item;
				}
			}
			$data = $data_filtered;
		}

		/**
		 * Handle filter
		 */
		if ( ( ! empty( $_REQUEST['filter'] ) ) && $filter = $_REQUEST['filter'] ) {
			$filters = array_merge( array( '__single_run' ), array_keys( wp_get_schedules() ) );
			if ( in_array( $filter, $filters ) ) {
				$data_filtered = array();
				foreach ( $data as $item ) {
					if ( $item['schedule'] === $filter ) {
						$data_filtered[] = $item;
					}
				}
				$data = $data_filtered;
			}
		}

		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 */
		function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'event'; //If no sort, default to title
			$order   = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'asc'; //If no order, default to asc

			//$result = strcmp( $a[ $orderby ], $b[ $orderby ] ); //Determine sort order, case sensitive
			//$result = strcasecmp( $a[ $orderby ], $b[ $orderby ] ); //Determine sort order, case insensitive
			$result = strnatcasecmp( $a[ $orderby ], $b[ $orderby ] ); //Determine sort order, case insensitive, natural order

			return ( $order === 'asc' ) ? $result : - $result; //Send final sort direction to usort
		}

		usort( $data, 'usort_reorder' );

		/**
		 * Pagination.
		 */
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );


		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );


		/**
		 * Now we can add the data to the items property, where it can be used by the rest of the class.
		 */
		$this->items = $data;

		/**
		 * We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items,                      //calculate the total number of items
			'per_page'    => $per_page,                         //determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page )   //calculate the total number of pages
		) );
	}

	/**
	 * Outputs the hidden row displayed when inline editing
	 *
	 * @since 3.1.0
	 */
	public function inline_edit() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>

        <form method="get">
            <table style="display: none">
                <tbody id="inlineedit">
                <tr id="inline-edit" class="inline-edit-row quick-edit-row-page inline-edit-row-page"
                    style="display: none">
                    <td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">

                        <fieldset class="inline-edit-col-left">
                            <legend class="inline-edit-legend"><?php _e( 'Quick Edit' ); ?></legend>
                            <div class="inline-edit-col">
                                <label>
                                    <span class="title"><?php _e( 'Args', 'leira-cron-jobs' ); ?></span>
                                    <span class="input-text-wrap">
                                        <input class="ptitle" type="text" name="args" value=""/>
                                        <p class="description">
                                            <small>
									        <?php _e( 'Use a JSON encoded array, e.g. [10] , ["value"] or [10,"mixed","values"]', 'leira-cron-jobs' ) ?>
                                        </small></p>
                                    </span>
                                </label>
                            </div>
                        </fieldset>
                        <fieldset class="inline-edit-col-right">
                            <div class="inline-edit-col">
                                <input type="hidden" name="event" value=""/>
                                <input type="hidden" name="_action" value=""/>
                                <input type="hidden" name="md5" value=""/>
                                <input type="hidden" name="time" value=""/>
                                <label>
                                    <span class="title"><?php _e( 'Schedule', 'leira-cron-jobs' ); ?></span>
                                    <span class="input-text-wrap">
                                        <?php echo $this->get_dropdown_schedules(); ?>
                                    </span>
                                </label>
                                <fieldset class="inline-edit-date">
                                    <legend>
                                        <span class="title"><?php _e( 'Execution', 'leira-cron-jobs' ); ?></span>
                                    </legend>
									<?php echo $this->get_datetime_editor() ?>
                                </fieldset>
                                <input type="hidden" name="offset" value=""/>
                                <br class="clear"/>
                            </div>
                        </fieldset>
						<?php

						$core_columns = array(
							'cb'          => true,
							'description' => true,
							'name'        => true,
							'slug'        => true,
							'posts'       => true,
						);

						list( $columns ) = $this->get_column_info();

						foreach ( $columns as $column_name => $column_display_name ) {
							if ( isset( $core_columns[ $column_name ] ) ) {
								continue;
							}

							/** This action is documented in wp-admin/includes/class-wp-posts-list-table.php */
							do_action( 'quick_edit_custom_box', $column_name, 'edit-cron-job', 0 );
						}

						?>

                        <div class="inline-edit-save submit">
                            <button type="button" class="cancel button alignleft"><?php _e( 'Cancel' ); ?></button>
                            <button type="button"
                                    class="save button button-primary alignright"><?php _e( 'Save' ); ?></button>
                            <span class="spinner"></span>
							<?php wp_nonce_field( 'cronjobinlineeditnonce', '_inline_edit', false ); ?>
                            <br class="clear"/>
                            <div class="notice notice-error notice-alt inline hidden">
                                <p class="error"></p>
                            </div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
		<?php
	}

	/**
	 * Generates a dropdown for the available schedules
	 *
	 * @return string
	 */
	protected function get_dropdown_schedules() {

		$options = array(
			//''             => __( 'Select a Schedule', 'leira-cron-jobs' ),
			'__single_run' => __( 'Single Run', 'leira-cron-jobs' )
		);

		$schedules = wp_get_schedules();
		if ( ! is_array( $schedules ) ) {
			$schedules = array();
		}
		foreach ( $schedules as $name => $schedule ) {
			$options[ $name ] = $schedule['display'];
		}

		$out = sprintf( '<select name="%s" class="%s">', 'schedule', '' );
		foreach ( $options as $name => $text ) {
			$out .= sprintf( '<option value="%s">%s</option>', $name, $text );
		}
		$out .= '</select>';

		return $out;
	}


	/**
	 * Print out HTML form date elements for editing post or comment publish date.
	 *
	 * @param int        $tab_index The tabindex attribute to add. Default 0.
	 * @param int|bool   $multi     Optional. Whether the additional fields and buttons should be added. Default
	 *                              0|false.
	 *
	 * @return string
	 * @global WP_Locale $wp_locale
	 */
	function get_datetime_editor( $tab_index = 0, $multi = 0 ) {
		global $wp_locale;
		$edit = false;

		$tab_index_attribute = '';
		if ( (int) $tab_index > 0 ) {
			$tab_index_attribute = " tabindex=\"$tab_index\"";
		}

		$post_date = time();
		$jj        = ( $edit ) ? mysql2date( 'd', $post_date, false ) : '';
		$mm        = ( $edit ) ? mysql2date( 'm', $post_date, false ) : '';
		$aa        = ( $edit ) ? mysql2date( 'Y', $post_date, false ) : '';
		$hh        = ( $edit ) ? mysql2date( 'H', $post_date, false ) : '';
		$mn        = ( $edit ) ? mysql2date( 'i', $post_date, false ) : '';
		$ss        = ( $edit ) ? mysql2date( 's', $post_date, false ) : '';

		$month = '<label><span class="screen-reader-text">' . __( 'Month' ) . '</span><select ' . ( $multi ? '' : 'id="mm" ' ) . 'name="mm"' . $tab_index_attribute . ">\n";
		for ( $i = 1; $i < 13; $i = $i + 1 ) {
			//$monthnum  = zeroise( $i, 2 );
			$monthnum  = $i;
			$monthtext = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
			$month     .= "\t\t\t" . '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected( $monthnum, $mm, false ) . '>';
			/* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
			$month .= sprintf( __( '%1$s-%2$s' ), $monthnum, $monthtext ) . "</option>\n";
		}
		$month .= '</select></label>';

		$day    = '<label><span class="screen-reader-text">' . __( 'Day' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="jj" ' ) . 'name="jj" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
		$year   = '<label><span class="screen-reader-text">' . __( 'Year' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="aa" ' ) . 'name="aa" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" /></label>';
		$hour   = '<label><span class="screen-reader-text">' . __( 'Hour' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="hh" ' ) . 'name="hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
		$minute = '<label><span class="screen-reader-text">' . __( 'Minute' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="mn" ' ) . 'name="mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';

		$out = '<div class="timestamp-wrap">';
		/* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
		$out .= sprintf( __( '%1$s %2$s, %3$s @ %4$s:%5$s' ), $month, $day, $year, $hour, $minute );

		$out .= '</div><input type="hidden" id="ss" name="ss" value="' . $ss . '" />';

		return $out;
	}

}
