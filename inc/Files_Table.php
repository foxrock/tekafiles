<?php

// http://www.smashingmagazine.com/2011/11/03/native-admin-tables-wordpress/

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Files_Table extends WP_List_Table {

	function __construct() {
		parent::__construct( array(
			'singular' => 'Archivo',
			'plural' => 'Archivos',
			'ajax' => false));
	}
/*
	function extra_tablenav ($wich) {
		if ($wich == 'top') {
			echo "Hello, i'm in the top";
		}
		if ($wich == 'bottom') {
			echo "Hello, i'm in the bottom";
		}
	}
*/
	function get_columns () {
		return array(
			'cb' => "<input type='checkbox' />",
			'title' => 'Título',
			'description' => 'Descripción',
			'category' => 'Categoría',
			'enabled' => 'Habilitado');
	}

	function get_sortable_columns () {
		return array(
			'title' => 'title',
			'category' => 'category');
	}

	function column_default ($item, $column_name) {
		return $item->$column_name;
	}

	function column_cb($item) {
		return "<input type='checkbox' name='file[$item->file]' value='$item->ID' />";
	}

	function column_title($item) {
		$edit = admin_url("admin.php?page=tekafiles_new.php&e=$item->ID");
		$report = admin_url("admin.php?page=tekafiles_report.php&t=$item->ID");
		$actions = array(
			'edit' => "<a href='$edit'>Editar</a>",
			'report' => "<a href='$report'>Reporte</a>");
		$rowactions = $this->row_actions($actions);
		return "$item->title $rowactions";
	}

	function column_enabled($item) {
		if ($item->enabled) return 'Si';
		else return 'No';
	}

	function get_bulk_actions() {
		return array(
			'delete' => 'Eliminar',
			'enable' => 'Habilitar',
			'disable' => 'Deshabilitar'
		);
	}

	function process_bulk_action() {
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
            $action = 'bulk-' . $this->_args['plural'];
            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );
        }
        if (isset($_POST['file'])) {
	        global $wpdb;
	        $action = $this->current_action();
	        $items = $_POST['file'];
	        $files = array_keys($items);
	        $items = join(',', $items);
	        switch ( $action ) {
	            case 'delete':
	            	foreach ($files as $file) {
	            		if (is_file($file)) unlink($file);
	            	}
	            	$query = "DELETE FROM {$wpdb->prefix}tekafile_user
	            		WHERE tekafile IN ($items)";
	            	$wpdb->query($query);
	            	$query = "DELETE FROM {$wpdb->prefix}tekadownload
	            		WHERE tekafile IN ($items)";
	            	$wpdb->query($query);
	            	$query = "DELETE FROM {$wpdb->prefix}tekafile
	            		WHERE ID IN ($items)";
	            	$wpdb->query($query);
	                break;
	            case 'enable':
	            	$query = "UPDATE {$wpdb->prefix}tekafile
	            	SET enabled=1
	            	WHERE ID IN ($items)";
	            	$wpdb->query($query);
	            	break;
	            case 'disable':
	            	$query = "UPDATE {$wpdb->prefix}tekafile
	            	SET enabled=0
	            	WHERE ID IN ($items)";
	            	$wpdb->query($query);
	            	break;
	        }
	    }
        return;
	}

	function prepare_items () {
		if ($this->current_action()) $this->process_bulk_action();
		global $wpdb;
		$table = $wpdb->prefix . 'tekafile';
		$query = "SELECT * FROM $table";
		$per_page = 3;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$data = $wpdb->get_results($query);
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$this->items = $data;

		$this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ));
	}


}