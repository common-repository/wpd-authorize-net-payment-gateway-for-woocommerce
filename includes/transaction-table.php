<?php
/**
 * class WPD_Authorizenet_Transactions_Table extends WP_List_Table
 *
 * @package WPD Authorize.net
 * @author wpplugindesign.com
 * @since 0.5
*/

if (!defined('ABSPATH')) {
	exit;
}

do_action('wpd_authnet_before_single_order_transactions_table_class');

if (!class_exists('WPD_Authorizenet_Transactions_Table')) {

class WPD_Authorizenet_Transactions_Table extends WP_List_Table {

	public $order_id;

	function __construct() {
		parent::__construct( array(
			'singular' => 'transaction',
			'plural' => 'transactions',
			'ajax' => false ) 
		);
	}

	public function get_columns() {
		$columns = apply_filters('wpd_authnet_single_order_transactions_table_columns', array(
			'trans_id' => 'Transaction ID',
			'cur_stat' => 'Current Status',
			'capt' => 'Capture'
		));
		return $columns;
	}

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->get_transactions($this->order_id);
	}

	public function get_transactions($order_id) {

		add_thickbox();

		$results = array();

		// get order wpd_authnet_pmet meta from database
		$authnet = get_post_meta($order_id, 'wpd_authnet_pmet', true);

		if (is_array($authnet) && !empty($authnet)) :

			foreach ($authnet as $trans_id => $actions) {

				if ($trans_id > 0 && is_array($actions) && !empty($actions)) :

					$raw_column_data = array();
					$row = array();

					// authCaptureTransaction
					$c = array();
					if (is_array($actions['authCaptureTransaction']) && !empty($actions['authCaptureTransaction'])) {
						foreach ($actions['authCaptureTransaction'] as $k => $v) {
							$c[$k] = $v;
						}
					}

					$raw_column_data['capt'] = apply_filters('wpd_authnet_capture_column_transactions', $c, $actions);

					$raw_column_data = apply_filters('wpd_authnet_single_order_table_column_raw_data', $raw_column_data, $actions);

					foreach ($raw_column_data as $k => $v) {
						$s = $this->table_column_content($v);
						$row[$k] = strlen($s) ? $s : ' ';
					}

					// Transaction ID
					$row['trans_id'] = $trans_id;

					// Current Status (added after page load via ajax)
					$row['cur_stat'] = '<div class="processing_spinner"></div>';

					// add row to results array
					$results[] = $row;

				endif;
			}
		endif;
		return $results;
	}

	public function table_column_content($transactions) {
		$y = '';
		if (is_array($transactions) && !empty($transactions)) :
			// sort array by key (timestamp) in reverse order (newest first)
			krsort($transactions);
			// set array pointer to first element
			reset($transactions);

			foreach ($transactions as $time => $trans) {

				if (is_array($trans) && !empty($trans)) {
					if ($trans['respcode'] == 1 || $trans['respcode'] == 4) :
						$y .= '<div class="item">';
						$y .= '<span class="trans_type">'.$trans['trans_type'].'</span>';

						if (!empty($trans['void_type'])) {
							$y .= ' (' . $trans['void_type'] . ')';
						}

						$y .= ' - ';
						$y .= '<span class="status">'.WPD_WC_Authorizenet_Payment_Gateway::get_instance()->api_resp_codes[ $trans['respcode'] ].'</span>';
						$y .= ':';

						if (!empty($trans['amount'])) {
							$y .= ' &#36;<span class="auth_amount">' . $trans['amount'] . '</span>';
						}

						$y .= ' <span class="date">' . date('F j, Y', $time) . '</span>';
						$y .= ' <span class="time">' . date('h:i A', $time) . '</span>';
						$y .= '</div>';
					endif;
				}
			}
		endif;
		return $y;
	}

	public function column_default($item, $column_name) {
		return $item[$column_name];
	}

	public function display() {
		global $post;
		$singular = $this->_args['singular']; ?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" data-oid="<?php echo $post->ID; ?>">
	<thead>
	<tr>
		<?php $this->print_column_headers(); ?>
	</tr>
	</thead>
	<tbody id="the-list"<?php
		if ($singular) {
			echo " data-wp-lists='list:$singular'";
		} ?>>
		<?php $this->display_rows_or_placeholder(); ?>
	</tbody>
</table><?php 
	}

	public function single_row($item) {
		$id = !empty($item['trans_id']) ? ' id="trans-'.$item['trans_id'].'"' : '';
		echo '<tr'.$id.'>';
		$this->single_row_columns($item);
		echo '</tr>';
	}

}
}