<?php

require_once __DIR__.'/phgrd/jqgrid_dist.php';
class UIGrid
{
	public static function getGrid($caption, $tbname, $sql, $ms=false, $ro=true) {
		$g = new jqgrid();
		$g->select_command = $sql;
		$g->table = $tbname;
		$grid["multiselect"] = $ms;	
		$grid["autowidth"] = true;
		$grid["autoresize"] = true;
		$grid["rowList"] = array(10, 20, 50, 'All');
		$grid["toolbar"] = "both";
		$grid["caption"] = $caption;
		//$grid["url"] = 'admin-ajax.php?action=load_geyser_contacts';
		//$grid["actionicon"] = false;
		$grid["export"]["range"] = "filtered";
		$g->set_options($grid);
		
		$acts = array();

		$acts["rowactions"] = false; // show/hide row wise edit/del/save option
		//"search" => "advance", // show single/multi field search condition (e.g. simple or advance)
		$acts["showhidecolumns"] = true;
		$acts["export_pdf"] = true;
		$acts["export_excel"] = true;
		$acts["export_csv"] = true;
		
		
		if ($ro) {
			$acts['add'] = false;
			$acts['edit'] = false;
			$acts['delete'] = false;
		}
		
		
		$g->set_actions($acts);
		
		return $g;
	}
	
  	//public static function add_cols(&$cols, $name, $title, $hidden = false, $formatter = '', $formatoptions = '', $editable = '', $edittype = '', $editoptions = '', $default = '', $stype = 'text', $searchoptions = '', $dbname = '', $conds = '', $width = 0) {
		public static function add_cols(&$cols, $name, $title = '', $opts = array()) {
		$defcol = array(
			'name' => $name,
			'title' => $title,
			'hidden' => false,
			'editable'=> true,
			'stype' => 'text',
			'export'=> true, 
		);
		
		$col = array_merge($defcol, $opts);
		
		if ($opts['align'] == null) {
			$align = 'center'; //$col['formatter'] == 'currency' ? 'right' : 'center';
			$col['align'] = $align;
		}
		
		/*if ($col['formatter'] == 'date') {
			$col["fixed"]=true;
			$col["width"] = "120";
			$col["formatoptions"] = array("srcformat" => 'Y-m-d', "newformat" => 'd/m/Y');
		}
		if ($col['formatter'] == 'datetime') {
			$col["fixed"]=true;
			$col["width"] = "120";
			$col["formatoptions"] = array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i');
		}*/
		$cols[] = $col;
	}
	
}
	
?>