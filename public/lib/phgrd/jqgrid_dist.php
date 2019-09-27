<?php
/**
 * PHP Grid Component
 *
 * @author Abu Ghufran <gridphp@gmail.com> - http://Easyiso.com
 * @version 1.5.2 build 20150521-1043
 * @license: see license.txt included in package
 */
 
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set("display_errors","off");

function mysqli_field_name($result, $field_offset)
{
    $properties = mysqli_fetch_field_direct($result, $field_offset);
    return is_object($properties) ? $properties->name : null;
}

class jqgrid
{	
	
	// id of grid
	var $id;

	// grid parameters
	var $options = array();

	// nav grid parameters
	var $navgrid = array();

	// internal use params
	var $internal = array();
	
	// select query to show data
	var $select_command;
	
	// db table name used in add,update,delete
	var $table;
	
	// allowed operation on grid
	var $actions;

	### P ###
	// var for conditional css data
	var $conditional_css;

	### P ###
	// var for conditional css data
	var $group_header;

	// show server error
	var $debug;
	
	// db connection identifier - not used now, @todo: need to integrate adodb lib
	var $con;
	var $db_driver;
		
	// callback events
	var $events;
	
	/**
	 * Contructor to set default params
	 */
	function __construct($db_conf = null)
	{
		global $db;
		/*if (PHPGRID_AUTOCONNECT == 1)
		{
			$db_conf = array();
			$db_conf["type"] = PHPGRID_DBTYPE; // mysql,oci8(for oracle),mssql,postgres,sybase
			$db_conf["server"] = PHPGRID_DBHOST;
			$db_conf["user"] = PHPGRID_DBUSER;
			$db_conf["password"] = PHPGRID_DBPASS;
			$db_conf["database"] = PHPGRID_DBNAME;
		}*/
		
		// resume older session or create new
		if(session_id() == '') session_start();
		
		$this->db_driver = "mysql";
		$this->debug = 1;
		// shown in case of debug = 0
		$this->error_msg = "Some issues occured in this operation, Contact technical support for help";

		### P ###
		// use adodb layer to support non-mysql dbs
		if ($db_conf)
		{
			// set up DB
			include_once("adodb/adodb.inc.php");
			$driver = $db_conf["type"];
			$this->con = ADONewConnection($driver); # eg. 'mysql,oci8(for oracle),mssql,postgres,sybase' 
			$this->con->SetFetchMode(ADODB_FETCH_ASSOC);
			$this->con->debug = 0;

			$this->con->Connect($db_conf["server"], $db_conf["user"], $db_conf["password"], $db_conf["database"]);
	
			// set your db encoding -- for ascent chars (if required)	
			if ($db_conf["type"] == "mysql" || $db_conf["type"] == "mysqli" 
				|| ($db_conf["type"]=="pdo" && strstr($db_conf["server"],"mysql")!==false) )
				$this->con->Execute("SET NAMES 'utf8'");
				
			$this->db_driver = $db_conf["type"];
			
			// set server for strstr match in case of pdo
			if ($this->db_driver == "pdo")
				$this->db_driver = $db_conf["server"];
		}
		
		// set utf8 encoding
		if ($this->db_driver == "mysql")
			@mysqli_query($db, "SET NAMES 'utf8'");
		
		$grid["datatype"] = "json";
		$grid["rowNum"] = 20;
		$grid["width"] = 900;
		$grid["height"] = 350;
		$grid["rowList"] = array(10,20,30,'All');
		$grid["viewrecords"] = true;
		$grid["multiSort"] = false;
		$grid["scrollrows"] = true;
		// $grid["loadui"] = "block";
		$grid["toppager"] = false;
		// renamed qstr variable due to wordpress conflict
		$grid["prmNames"] = array("page"=>"jqgrid_page");

		// default sort options (first field and asc)
		$grid["sortname"] = "1";
		$grid["sortorder"] = "asc";
		$grid["form"]["nav"] = false;
		
		$protocol = ( ($_SERVER['HTTPS'] == "on" || $_SERVER["SERVER_PORT"] == "443" ) ? "https" : "http");
		$grid["url"] = "$protocol://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		
		### P ###
		// pass subgrid params if exist
		$s = (strstr($grid["url"], "?")) ? "&":"?";
		if (isset($_REQUEST["rowid"]) && isset($_REQUEST["subgrid"]))
			$grid["url"] .= $s."rowid=".$_REQUEST["rowid"]."&subgrid=".$_REQUEST["subgrid"];
		### P-END ###

		$grid["editurl"] = $grid["url"];
		$grid["cellurl"] = $grid["url"];
		
		// virtual scrolling, for big datasets
		$grid["scroll"] = 0;
		
		// drag drop columns to sort
		$grid["sortable"] = false;
		
		// excel like editing
		$grid["cellEdit"] = false;

		### P ###
		// if specific export is requested
		if (isset($_GET["export_type"]) && ($_GET["export_type"] == "xls" || $_GET["export_type"] == "xlsx" || $_GET["export_type"] == "excel"))
			$grid["export"]["format"] = "excel";
		else if (isset($_GET["export_type"]) && $_GET["export_type"] == "pdf")
			$grid["export"]["format"] = "pdf";
		else if (isset($_GET["export_type"]) && $_GET["export_type"] == "csv")
			$grid["export"]["format"] = "csv";

		// default pdf export options
		$grid["export"]["paper"] = "a4";
		$grid["export"]["orientation"] = "landscape";
		### P-END ###		

		$grid["add_options"] = array("recreateForm" => true, "closeAfterAdd"=>true, "closeOnEscape"=>true,
										"errorTextFormat"=> "function(r){ return r.responseText;}", "jqModal" => true, "modal" => true
										);
		$grid["edit_options"] = array("recreateForm" => true, "closeAfterEdit"=>true, "closeOnEscape"=>true,
										"errorTextFormat" => "function(r){ return r.responseText;}", "jqModal" => true, "modal" => true
										);
		$grid["delete_options"] = array("closeOnEscape"=>true, "errorTextFormat"=> "function(r){ return r.responseText;}", "modal" => true
										);
										
		$grid["view_options"]["closeOnEscape"] = true;
		$grid["view_options"]["modal"] = true;
		$grid["view_options"]["recreateForm"] = true;

		$grid["form"]["position"] = "center";

		### F - for free version only ###
		/*
		$x = "st"."r"."_"."r"."ot".strval((15%8)+6);
		$y = $x("obggbzvasb");
		// $grid["add_options"][$y] = str_rot13("<div align='center' style='color:gray;margin-top:3px;'>PHPGrid - Non Commercial / Evaluation Copy. <a style='color:gray' target='_blank' href='http://Easyiso.com/compare'>Get License</a></div>");
		$grid["add_options"][$y] = $x('<qvi fglyr="pbybe:tenl;znetva-gbc:3ck;" nyvta="pragre">CUCTevq - Aba Pbzzrepvny / Rinyhngvba Pbcl. <n uers="uggc://jjj.cuctevq.bet/pbzcner" gnetrg="_oynax" fglyr="pbybe:tenl">Trg Yvprafr</n></qvi>');
		$grid["edit_options"][$y] = $grid["add_options"][$y];
		*/
		### F-END ###

		$this->options = $grid;	
		
		$this->actions["showhidecolumns"] = false;
		$this->actions["inlineadd"] = false;
		$this->actions["search"] = "";
		$this->actions["export"] = false;
	}

	
	public function array_is_associative($array) 
	{
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}

	/**
	 * Helping function to parse array
	 */
	public function strip($value)
	{
		// gpc line removed for wp plugin search fix
		// if(get_magic_quotes_gpc() != 0)
		{
			if(is_array($value))  
				if ( $this->array_is_associative($value) )
				{
					foreach( $value as $k=>$v)
						$tmp_val[$k] = stripslashes($v);
					$value = $tmp_val; 
				}				
				else  
					for($j = 0; $j < sizeof($value); $j++)
						$value[$j] = stripslashes($value[$j]);
			else
				$value = stripslashes($value);
		}
		return $value;
	}	
	
	/**
	 * Advance search where clause maker
	 */
	private function construct_where($s)
	{
		$qwery = "";

		if ($s) {
			$jsona = (array)json_decode($s,true);
			if(is_array($jsona))
			{
				$qwery = $this->make_where($jsona);
			}
		}
		
		if (!empty($qwery))
			$qwery = " AND ( $qwery )";
		 
		// die($qwery);
		return $qwery;
	}	
	
	private function make_where($jsona)
	{
		$qwery = "";
	
		//['eq','ne','lt','le','gt','ge','bw','bn','in','ni','ew','en','cn','nc']
		$qopers = array(
					  'eq'=>" = ",
					  'ne'=>" <> ",
					  'lt'=>" < ",
					  'le'=>" <= ",
					  'gt'=>" > ",
					  'ge'=>" >= ",
					  'bw'=>" LIKE ",
					  'bn'=>" NOT LIKE ",
					  'in'=>" IN ",
					  'ni'=>" NOT IN ",
					  'ew'=>" LIKE ",
					  'en'=>" NOT LIKE ",
					  'se'=>" = ",
					  'cn'=>" LIKE " ,
					  'nu'=>" IS NULL " ,
					  'nn'=>" IS NOT NULL " ,
					  'nc'=>" NOT LIKE " );
					  
		$gopr = $jsona['groupOp'];
		$rules = $jsona['rules'];
		$groups = $jsona['groups'];
		
		$i =0;
		
		$qwery = "";
		
		if (is_array($rules))
		foreach($rules as $key=>$val) 
		{
			$val = (array)$val;
			$op = $val['op'];

			# fix for conflicting table name fields (used alias from page, in property dbname)
			foreach($this->options["colModel"] as $link_c)
			{
				// only used exact date match, when operator is not 'cn' (contains) - default is cn
				if ($val['field'] == $link_c["name"] && !empty($link_c["formatoptions"]) && in_array($op, array("ne","eq","gt","ge","lt","le")))
				{
					// fix for d/m/Y or d/m/y date format. strtotime expects m/d/Y
					if (stristr($link_c["formatoptions"]["newformat"],"d/m/Y"))
					{
						$val['data'] = preg_replace('/(\d+)\/(\d+)\/(\d+)/i','$2/$1/$3',$val['data']);
					}
					// fix for d-m-y (2 digit year) for strtotime
					else if (strstr($link_c["formatoptions"]["newformat"],"d-m-y"))
					{
						$val['data'] = preg_replace('/(\d+)-(\d+)-(\d+)/i','$3-$2-$1',$val['data']);
					}
					else if (strstr($link_c["formatoptions"]["newformat"],"d/M/Y") || strstr($link_c["formatoptions"]["newformat"],"d-M-Y"))
					{
						$val['data'] = preg_replace('/\/\-/i',' ',$val['data']);
					}

					if ($link_c["formatter"] == "date")
						$val['data'] = $this->custom_date_format("Y-m-d",$val['data']);
					else if ($link_c["formatter"] == "datetime")
						$val['data'] = $this->custom_date_format("Y-m-d H:i:s",$val['data']);
				}

				if ($val['field'] == $link_c["name"] && !empty($link_c["dbname"]))
				{
					$val['field'] = $link_c["dbname"];
				}
			}

			$field = $val['field'];
			
			// skip if some mysql function is used
			$is_fx = false;
			$is_fx = (strstr($field,"(") !== false && strstr($field,")") !== false);
			
			// add tilde sign for mysql
			if (!$is_fx)
			{
				$field = $this->wrap_field($field);
			}
				
			$v = $val['data'];

			// enable >,>=,<,<= in search textbox
			$d_len = 0;
			if (strpos($v,"!=") === 0 || strpos($v,">=") === 0 || strpos($v,"<=") === 0)
				$d_len = 2;
			else if (strpos($v,"=") === 0 || strpos($v,">") === 0 || strpos($v,"<") === 0)
				$d_len = 1;
				
			if ($d_len > 0)
			{
				$d_op = substr($v,0,$d_len);
				$d_val = substr($v,$d_len);
			
				if (is_numeric($d_val))
				{
					$v = " $d_op $d_val";
					$op = 'inline';
					$qopers['inline']='';
				}
			}

			if(isset($v) && isset($op))
			{
				$i++;
				// ToSql in this case is absolutley needed
				$v = $this->to_sql($field,$op,$v);

				if ($i > 1)
					$qwery .= " " .$gopr." ";
				
				switch ($op) {
					// in need other thing
					case 'in' :
					case 'ni' :
						$qwery .= $field.$qopers[$op]." (".$v.")";
						break;
					case 'cn' :
						// make case insensitive for oracle and db2
						if (strpos($this->db_driver,"oci8") !== false || strpos($this->db_driver,"db2") !== false || strpos($this->db_driver,"postgres") !== false)
							$qwery .= "LOWER($field)".$qopers[$op]." LOWER(".$v.")";
						else
							$qwery .= $field.$qopers[$op].$v;
						break;
					case 'bw' :
						$qwery .= "LOWER($field)".$qopers[$op]." LOWER(".$v.")";
						break;
					case 'nn' :
					case 'nu' :
						$qwery .= $field.$qopers[$op];
						break;	
					case 'se' :
						$qwery .= "soundex($field) $qopers[$op] soundex($v)";
						break;								
					default:
						$qwery .= $field.$qopers[$op].$v;
				}
			}
		}	
	
		if (!empty($groups))
		{
			if (!empty($rules))
				$qwery .= " $gopr ";
				
			foreach($groups as $g)
			{
				$tmp = $this->make_where($g);
				$group_qwery[] = "($tmp)";
			}
			
			$qwery .= implode(" $gopr ",$group_qwery);
		}
		
		return $qwery;
	}

	/**
	 * Advance search, make search operator sql compatible
	 */
	private function to_sql($field, $oper, $val) 
	{
		//mysql_real_escape_string is better
		if($oper=='bw' || $oper=='bn') return "'" . $this->escape_string($val) . "%'";
		else if ($oper=='ew' || $oper=='en') return "'%" . $this->escape_string($val) . "'";
		else if ($oper=='cn' || $oper=='nc') return "'%" . $this->escape_string($val) . "%'";
		else if ($oper=='inline') return $this->escape_string($val);
		else if ($oper=='in' || $oper=='ni') 
		{		
			$val = "'".implode("','",explode(",",$this->escape_string($val)))."'";
			return $val;
		}
		else return "'" . $this->escape_string($val) . "'";
	}
	
	### P ###
	/**
	 * Setter for event handler
	 */
	function set_events($arr)
	{
		$this->events = $arr;
	}

	### P ###
	/**
	 * Get dropdown values using ajax, onchange of dropdowns
	 */	
	function get_dependent_dropdown($sql,$return_format)
	{
		$select = array();
		$result = $this->execute_query($sql);

		if ($this->con)
		{
			$arr = $result->GetRows();

			foreach($arr as $rs)
			{
				$rs["k"] = (!empty($rs["K"])) ? $rs["K"] : $rs["k"];
				$rs["v"] = (!empty($rs["V"])) ? $rs["V"] : $rs["v"];

				$select[$rs["k"]] = $rs["v"];
			}
		}
		else
		{
			$arr = array();
			while($rs = mysqli_fetch_array ($result,MYSQLI_ASSOC))
			{
				$arr[] = $rs;
				$select[$rs["k"]] = $rs["v"];
			}
		}
				
		$str = "";
		if ($return_format == "option")
		{
			// return html for dependent dropdown ajax
			foreach($select as $k => $v)
			{
				$str .= "<option value='$k'>$v</option>";
			}
		}
		elseif ($return_format == "select")
		{
			$str .= "<select>";
			// return html for dependent dropdown ajax
			foreach($select as $k => $v)
			{
				$str .= "<option value='$k'>$v</option>";
			}
			$str .= "</select>";
		}
		elseif ($return_format == "json")
		{
			$str = json_encode($arr);
		}
		
		echo $str;
		die;
	}

	### P ###
	/**
	 * Get dropdown values for select dropdowns
	 */	
	function get_dropdown_values($sql)
	{
		$str = array();
		$result = $this->execute_query($sql);

		if ($this->con)
		{
			$arr = $result->GetRows();

			foreach($arr as $rs)
			{
				$rs["k"] = (!empty($rs["K"])) ? $rs["K"] : $rs["k"];
				$rs["v"] = (!empty($rs["V"])) ? $rs["V"] : $rs["v"];

				$str[] = $rs["k"].":".$rs["v"];
			}
		}
		else
		{
			while($rs = mysqli_fetch_array ($result,MYSQLI_ASSOC))
			{
				$str[] = $rs["k"].":".$rs["v"];
			}
		}
				
		$str = implode($str,";");
		return $str;
	}

	/**
	 * Setter for allowed actions (add/edit/del/autofilter etc)
	 */
	function set_actions($arr)
	{
		if (empty($arr))
			$arr = array();		
			
		if (empty($this->actions))
			$this->actions = array();
			
		// for add_option array
		foreach($arr as $k=>$v)
			if (is_array($v))
			{
				if (!isset($this->actions[$k]))
					$this->actions[$k] = array();
					
				$arr[$k] = array_merge($arr[$k],$this->actions[$k]);
			}		
			
		$this->actions = array_merge($this->actions,$arr);
	}
	
	/**
	 * Setter for grid customization options
	 */
	function set_options($options)
	{
		if (empty($arr))
			$arr = array();

		if (empty($this->options))
			$this->options = array();

		if (isset($options["rowList"]))
			unset($this->options["rowList"]);

		// for export like array merge
		foreach($options as $k=>$v)
			if (is_array($v))
			{
				if (!isset($this->options[$k]))
					$this->options[$k] = array();
					
				$options[$k] = array_merge($this->options[$k],$options[$k]);
			}
			
		$this->options = array_merge($this->options,$options);

		$this->options["editurl"] = $this->options["url"];
		$this->options["cellurl"] = $this->options["url"];

		// enable form prev/next buttons. disabled by default now
		$show_form_nav = '';
		if ($this->options["form"]["nav"] === true)
		{
			$show_form_nav = 'setTimeout(function(){jQuery("#pData").show();jQuery("#nData").show();},100);';
		}
		else
		{
			$show_form_nav = 'setTimeout(function(){jQuery("#pData").hide();jQuery("#nData").hide();},100);';
		}

		$this->internal["add_options"]["beforeShowForm"] = $show_form_nav;
		$this->internal["edit_options"]["beforeShowForm"] = $show_form_nav;
		$this->internal["delete_options"]["beforeShowForm"] = $show_form_nav;
		// left out on view, for ease of navigation on view
		//$this->internal["view_options"]["beforeShowForm"] = $show_form_nav;
		
		// toolbar position
		if (isset($this->options["toolbar"]) && $this->options["toolbar"] != "bottom")
		{
			$this->options["toppager"] = true;

			// fix for initially hidden grid
			if ($this->options["hiddengrid"] == true && $this->options["toolbar"] == "top")
				$this->options["toolbar"] = "both";
		}

		// align dialog to center
		if ($this->options["form"]["position"] == "center")
		{
			$fx_center = ($this->options["add_options"]["jqModal"] == false) ? "fixed" : "abs";
			$this->internal["add_options"]["beforeShowForm"] .= '	
																	var gid = formid.attr("id").replace("FrmGrid_","");
																	jQuery("#editmod" + gid).'.$fx_center.'center();
																';	

			$fx_center = ($this->options["edit_options"]["jqModal"] == false) ? "fixed" : "abs";
			$this->internal["edit_options"]["beforeShowForm"] .= '
																	var gid = formid.attr("id").replace("FrmGrid_","");
																	jQuery("#editmod" + gid).'.$fx_center.'center();
																';

			$fx_center = ($this->options["delete_options"]["jqModal"] == false) ? "fixed" : "abs";
			$this->internal["delete_options"]["beforeShowForm"] .= '
																	var gid = formid.attr("id").replace("DelTbl_","");
																	jQuery("#delmod" + gid).'.$fx_center.'center();
																';

			$fx_center = ($this->options["view_options"]["jqModal"] == false) ? "fixed" : "abs";
			$this->internal["view_options"]["beforeShowForm"] .= '
																	var gid = formid.attr("id").replace("ViewGrid_","");
																	jQuery("#viewmod" + gid).'.$fx_center.'center();
																';

			$fx_center = ($this->options["search_options"]["jqModal"] == false) ? "fixed" : "abs";
			$this->options["search_options"]["beforeShowSearch"] .= 'function(formid) { 
																		if (!formid.attr("id")) return true;

																		var gid = formid.attr("id").replace("fbox_","");
																		jQuery("#searchmodfbox_" + gid).'.$fx_center.'center();
																		return true;
																	}
																';

																	
			unset($this->options["form"]["position"]);
		}
		
		// show action icons by default, unless disabled
		if ($this->options["actionicon"] !== false)
		{
			$this->internal["actionicon"] = true;
			unset($this->options["actionicon"]);
		}
		
		// shift based selection for multiselect 
		if ($this->options["multiselect"] == true)
		{
			$this->options["beforeSelectRow"] = "function(rowid, e) 
			{
				var grid = jQuery(this), rows = this.rows,

				// get id of the previous selected row
				startId = grid.jqGrid('getGridParam', 'selrow'),
				startRow, endRow, iStart, iEnd, i, rowidIndex;

				if (!e.ctrlKey && !e.shiftKey) 
				{
					//intentionally left here to show differences with
					//Oleg's solution. Just have normal behavior instead.
					//grid.jqGrid('resetSelection');
				} 
				else if (startId && e.shiftKey) 
				{
					//Do not clear existing selections
					//grid.jqGrid('resetSelection');

					// get DOM elements of the previous selected and
					// the currect selected rows
					startRow = rows.namedItem(startId);
					endRow = rows.namedItem(rowid);

					if (startRow && endRow) 
					{
						// get min and max from the indexes of the previous selected
						// and the currect selected rows
						iStart = Math.min(startRow.rowIndex, endRow.rowIndex);
						rowidIndex = endRow.rowIndex;
						iEnd = Math.max(startRow.rowIndex, rowidIndex);

						// get the rowids of selected rows
						var selected = grid.jqGrid('getGridParam','selarrrow');

						for (i = iStart; i <= iEnd; i++) 
						{
							// if this row isn't selected, then toggle it.
							// jqgrid will select the clicked on row, so just ingore it.
							// note that we still go <= iEnd because we don't know which is start or end.
							if(selected.indexOf(rows[i].id) < 0 && i != rowidIndex) 
							{
								// true is to trigger onSelectRow event, which you may not need
								grid.jqGrid('setSelection', rows[i].id, true);
							}
						}
					}

					// clear text selection (needed in IE)
					if(document.selection && document.selection.empty) 
					{
						document.selection.empty();
					} 
					else if(window.getSelection) 
					{
						window.getSelection().removeAllRanges();
					}
				}
				// commented as unabled to copy text after selection
				// grid.disableSelection(); 
				return true;
			}";		
		}
	}

	### P ###
	function set_conditional_css($params)
	{
		$this->conditional_css = $params;
	}

	### P ###
	function set_group_header($params)
	{
		$this->group_header = $params;
	}

	/**
	 * Auto generate columns for grid based on SQL / table
	 */
	function set_columns($cols = null, $change_field = false)
	{
		if (!is_array($this->table) && !$this->table && !$this->select_command) die("Please specify tablename or select command");
		
		// if loading from array
		if (is_array($this->table))
		{
			### P ###
			$arr = $this->table;
			$f = array_keys($arr[0]);
		}
		else
		{
			// if only table is defined, make select sql for it
			if (!$this->select_command && $this->table)
				$this->select_command = "SELECT * FROM ".$this->table;

			// make sql on single line, with no extra spaces
			$this->select_command = trim($this->select_command);
			$this->select_command = preg_replace("/(\r|\n)/"," ",$this->select_command);
			$this->select_command = preg_replace("/[ ]+/"," ",$this->select_command);
			$this->select_command = trim($this->select_command);
			
			if ($this->select_command[0] == "(" && $this->select_command[count($this->select_command)-1] == ")")
				$this->select_command = trim($this->select_command,"()");

			// add where clause if not present -- fix for search feature
			if (stristr($this->select_command,"WHERE") === false)
			{
				// preserve subqueries
				$matches_subsql = $this->remove_subsql();
					
				// place group by at proper position in sql
				if (($p = stripos($this->select_command,"GROUP BY")) !== false)
				{
					//$start = substr($this->select_command,0,$p);
					//$end = substr($this->select_command,$p);
					//$this->select_command = $start." WHERE 1=1 ".$end;
					
					$tmpsql = explode('GROUP BY', $this->select_command);
					$this->select_command = implode(' WHERE 1=1 GROUP BY ', $tmpsql);
				}
				else
					$this->select_command .= " WHERE 1=1";
					
				// re-adjust subqueries in sql
				$this->select_command = $this->add_subsql($this->select_command,$matches_subsql);
			}
			// get sql column names by running nulled sql
			if (!empty($this->internal["sql"]))
				$this->select_command = $this->internal["sql"];
				
			$sql = $this->select_command . " LIMIT 1 OFFSET 0";
			
			$sql = $this->prepare_sql($sql,$this->db_driver);
			
			$result = $this->execute_query($sql);

			if ($this->con)
			{
				$cnt = $result->FieldCount();
				for ($x=0; $x<$cnt; $x++)
				{
					$fld = $result->FetchField($x);
					$fld->type = $result->MetaType($fld->type);

					$f[] = $fld->name;
					$meta[$fld->name] = $fld;
				}
			}	
			else
			{		
				$meta = array();
				$numfields = mysqli_num_fields($result);
				for ($i=0; $i < $numfields; $i++) // Header
				{
					$f[] = $n = mysqli_field_name($result, $i);
					$meta[$n] = mysqli_fetch_field($result);
				}
			}			
		}

		if ($change_field)
		{
			$tmp = $cols;
			unset($cols);
		}

		// if grid columns not defined, make from sql
		if (!$cols)
		{
			foreach($f as $c)
			{
				// skip rnum for oci drivers
				if (strtolower($c) == 'rnum') continue;
				
				$col = array();
				$col["title"] = ucwords(str_replace("_"," ",$c));
				$col["name"] = $c;
				$col["index"] = $c;
				$col["editable"] = true;
				
				// auto suggest edit control on field type
				if (!empty($meta))
				{
					if (strpos(strtolower($meta[$c]->type),"time") !== false || $meta[$c]->type == 'T')
						$col["formatter"] = "datetime";
					else if (strpos(strtolower($meta[$c]->type),"date") !== false || $meta[$c]->type == 'D')
						$col["formatter"] = "date";
					else if (strpos(strtolower($meta[$c]->type),"blob") !== false || $meta[$c]->type == 'X' || $meta[$c]->type == 'B')
						$col["edittype"] = "textarea";
				}
				
				$col["editoptions"] = array("size"=>20);
				$col["searchoptions"]["clearSearch"] = false; # to disable clear search (x) 
				$g_cols[] = $col;
			}
		}
		
		$act_col = array();
		
		// if $change_field is passed, only update the defined column and rest from table
		if ($change_field)
		{
			foreach ($g_cols as &$gc)
			{
				foreach ($tmp as $tc)
				{
					if ($gc["name"] == $tc["name"])
					{
						$gc = array_merge($gc,$tc);
					}
					else if ("act" == $tc["name"])
					{
						$act_col = $tc;
					}
				}
			}
			
			if (!empty($act_col))
				$g_cols[] = $act_col;
		}
		
		if (!$cols)
			$cols = $g_cols;
			
		// index attr is must for jqgrid, so add it in array
		for($i=0;$i<count($cols);$i++)
		{
			$cols[$i]["name"] = $cols[$i]["name"];
			$cols[$i]["index"] = $cols[$i]["name"];
			$cols[$i]["searchoptions"]["clearSearch"] = false; # to disable clear search (x) 

			// field is editable by default, on custom column definition
			#if (!isset($cols[$i]["editable"]))
			#	$cols[$i]["editable"] = true;

			if ($cols[$i]["editrules"]["required"] == true)
				$cols[$i]["formoptions"]["elmsuffix"] = '<font color=red> *</font>';

			if (isset($cols[$i]["formatter"]) && $cols[$i]["formatter"] == "date" && empty($cols[$i]["formatoptions"]))
				$cols[$i]["formatoptions"] = array("srcformat"=>'Y-m-d',"newformat"=>'Y-m-d');

			if (isset($cols[$i]["formatter"]) && $cols[$i]["formatter"] == "datetime" && empty($cols[$i]["formatoptions"]))
				$cols[$i]["formatoptions"] = array("srcformat"=>'Y-m-d H:i:s',"newformat"=>'Y-m-d H:i:s');

			$js_dt_fmt = $cols[$i]["formatoptions"]["newformat"];

			if (isset($cols[$i]["formatter"]) && $cols[$i]["formatter"] == "date")
			{
				$js_dt_fmt = str_replace("Y", "yy", $js_dt_fmt);
				$js_dt_fmt = str_replace("m", "mm", $js_dt_fmt);
				$js_dt_fmt = str_replace("d", "dd", $js_dt_fmt);
				$js_dt_fmt = str_replace("h", "", $js_dt_fmt);
				$js_dt_fmt = str_replace("H", "", $js_dt_fmt);
				$js_dt_fmt = str_replace("i", "", $js_dt_fmt);
				$js_dt_fmt = str_replace("s", "", $js_dt_fmt);
				$js_dt_fmt = str_replace(":", "", $js_dt_fmt);
				$js_dt_fmt = str_replace("A", "", $js_dt_fmt);
				$js_dt_fmt = str_replace("a", "", $js_dt_fmt);
				$js_dt_fmt = trim($js_dt_fmt);

				$opts = $cols[$i]["formatoptions"]["opts"];
				if (empty($opts)) $opts = array();
				$opts = json_encode($opts);	
				
				$cols[$i]["editoptions"]["dataInit"] = "function(o){link_date_picker(o,'{$js_dt_fmt}',0,$opts);}";
				
				// only used exact date match, when operator is not 'cn' (contains)
				if ( empty($cols[$i]["searchoptions"]["sopt"]) || !in_array("cn",$cols[$i]["searchoptions"]["sopt"]) ) 
				{
					$cols[$i]["searchoptions"]["sopt"] = array("eq","ne","gt","ge","lt","le");
					$cols[$i]["searchoptions"]["dataInit"] = "function(o){link_date_picker(o,'{$js_dt_fmt}',1,$opts);}";
				}
			}
			
			// prepend empty option if not there
			if (isset($cols[$i]["stype"]) && $cols[$i]["stype"] == "select" && $cols[$i]["searchoptions"]["skipEmpty"]!==true && substr($cols[$i]["searchoptions"]["value"],0,2) !== ":;")
			{
				$cols[$i]["searchoptions"]["value"] = ":-;".$cols[$i]["searchoptions"]["value"];
			}
			
			if (isset($cols[$i]["formatter"]) && $cols[$i]["formatter"] == "datetime")
			{
				// http://docs.jquery.com/UI/Datepicker/formatDate
				$dt_fmt = $js_dt_fmt;
				$dt_fmt = str_replace("Y", "yy", $dt_fmt);
				$dt_fmt = str_replace("m", "mm", $dt_fmt);
				$dt_fmt = str_replace("d", "dd", $dt_fmt);
				$dt_fmt = str_replace("h", "", $dt_fmt);
				$dt_fmt = str_replace("H", "", $dt_fmt);
				$dt_fmt = str_replace("i", "", $dt_fmt);
				$dt_fmt = str_replace("s", "", $dt_fmt);
				$dt_fmt = str_replace(":", "", $dt_fmt);
				$dt_fmt = str_replace("A", "", $dt_fmt);
				$dt_fmt = str_replace("a", "", $dt_fmt);
				$dt_fmt = trim($dt_fmt);

				// http://trentrichardson.com/examples/timepicker/
				$tm_fmt = $js_dt_fmt;
				$tm_fmt = str_replace("Y", "", $tm_fmt);
				$tm_fmt = str_replace("y", "", $tm_fmt);
				$tm_fmt = str_replace("M", "", $tm_fmt);
				$tm_fmt = str_replace("m", "", $tm_fmt);
				$tm_fmt = str_replace("d", "", $tm_fmt);
				$tm_fmt = str_replace("/", "", $tm_fmt);
				$tm_fmt = str_replace("-", "", $tm_fmt);
				$tm_fmt = str_replace("H", "HH", $tm_fmt);
				$tm_fmt = str_replace("h", "hh", $tm_fmt);
				$tm_fmt = str_replace("i", "mm", $tm_fmt);
				$tm_fmt = str_replace("s", "ss", $tm_fmt);
				$tm_fmt = str_replace("A", "TT", $tm_fmt);
				$tm_fmt = str_replace("a", "tt", $tm_fmt);
				$tm_fmt = trim($tm_fmt);

				$opts = $cols[$i]["formatoptions"]["opts"];
				$opts["timeFormat"] = $tm_fmt;
				if (empty($opts)) $opts = array();
				$opts = json_encode($opts);				

				$cols[$i]["editoptions"]["dataInit"] = "function(o){link_datetime_picker(o,'{$dt_fmt}',0,$opts);}";
				
				// only used exact date match, when operator is not 'cn' (contains)
				if ( empty($cols[$i]["searchoptions"]["sopt"]) || !in_array("cn",$cols[$i]["searchoptions"]["sopt"]) ) 
				{
					$cols[$i]["searchoptions"]["sopt"] = array("eq","ne","gt","ge","lt","le");
					$cols[$i]["searchoptions"]["dataInit"] = "function(o){link_datetime_picker(o,'{$dt_fmt}',1,$opts);}";
				}
			}
			
			### P ###
			if (isset($cols[$i]["stype"]) && $cols[$i]["stype"] == "select-multiple")
			{
				// multi-select in search filter
				$cols[$i]["stype"] = "select";
				$cols[$i]["searchoptions"]["dataInit"] = "function(el){ setTimeout(function(){ link_multiselect(el); },200); }";
				$cols[$i]["searchoptions"]["sopt"] = array("in");
				$cols[$i]["searchoptions"]["attr"] = array("multiple"=>"multiple", "size"=>4);
			}			
			
			### P ###
			if (isset($cols[$i]["formatter"]) && $cols[$i]["formatter"] == "wysiwyg")
			{
				$cols[$i]["formatter"] = "function(cellval,options,rowdata){ jQuery(document).data('wysiwyg_{$cols[$i]["name"]}_'+options.rowId,jQuery.jgrid.htmlEncode(cellval)); return '<div style=\'white-space:inherit;\'>'+jQuery.jgrid.htmlDecode(cellval)+'</div>'; }";
				$cols[$i]["unformat"] = "function(cellval,options,rowdata){ return jQuery.jgrid.htmlDecode(jQuery(document).data('wysiwyg_{$cols[$i]["name"]}_'+options.rowId)); }";
				$cols[$i]["editoptions"]["dataInit"] = "function(el){ setTimeout(function(){ link_editor(el); },200); }";
			}			

			### P ###
			if (isset($cols[$i]["formatter"]) && $cols[$i]["formatter"] == "autocomplete")
			{
				if ($cols[$i]["formatoptions"]["callback"]) 
					$param = "function(d){ d=eval(d); {$cols[$i]["formatoptions"]["callback"]}(d); }";
				else
					$param = "'".$cols[$i]["formatoptions"]["update_field"]."'";
				
				$force = 0;
				if ($cols[$i]["formatoptions"]["force_select"] == true)
				{
					$cols[$i]["editrules"] = array("required"=>true);
					$force = 1;
				}
					
				$cols[$i]["editoptions"]["dataInit"] = "function(o){link_autocomplete(o,$param,$force);}";
			}
			
			### P ###
			if ($cols[$i]["edittype"] == "file")
			{
				$this->require_upload_ajax = 1;
				$cols[$i]["editoptions"]["dataInit"] = "function(o){link_upload(o,'".$cols[$i]["name"]."');}";
				$cols[$i]["edittype"] = "text";
				$cols[$i]["show"]["list"] = false;
			}

			### P ###
			if (is_array($cols[$i]["editoptions"]["onchange"]) || is_array($cols[$i]["editoptions"]["onload"]))
			{
				$col_tmp = $cols[$i];
				
				if (is_array($cols[$i]["editoptions"]["onchange"]))
				{
					$field = $cols[$i]["editoptions"]["onchange"]["update_field"];
					$callback = $cols[$i]["editoptions"]["onchange"]["callback"];

					if (!empty($field))
						$cols[$i]["editoptions"]["onchange"] = "this.event='onchange'; fx_get_dropdown(this,'$field');";
					else if (!empty($callback))
					{
						$cols[$i]["editoptions"]["onchange"] = "this.event='onchange'; fx_get_dropdown(this,function(d){ d=eval(d); $callback(d); })";
						
						// removed to avoid double ajax call - one this, one dependent dropdown onload
						// $cols[$i]["editoptions"]["dataInit"] = "function(o) { setTimeout(function(){ o.event='onchange'; fx_get_dropdown(o,function(d){ d=eval(d); $callback(d); }); },200); }"; 
					}
					
					// to enable dependent dropdown on search toolbar
					if (!empty($field))
						$this->internal["js_dependent_dropdown"] .= "jQuery('.ui-search-toolbar select[name={$cols[$i][name]}]').change(function(){ fx_get_dropdown(this,'$field',1); });";
					
					if (!empty($callback))
						$this->internal["js_dependent_dropdown"] .= "jQuery('.ui-search-toolbar select[name={$cols[$i][name]}]').change(function(){ fx_get_dropdown(this,function(d){ d=eval(d); $callback(d); }); });";
				}

				if (is_array($cols[$i]["editoptions"]["onload"]))
				{
					// blank value to make dropdown
					if (empty($cols[$i]["editoptions"]["value"]))
						$cols[$i]["editoptions"]["value"] = ":"; 
					
					$old_init = "";
					if (!empty($cols[$i]["editoptions"]["dataInit"]))
						$old_init = "(".$cols[$i]["editoptions"]["dataInit"].")();";
					
					$cols[$i]["editoptions"]["dataInit"] = "function(o) { o.event = 'onload'; setTimeout(function(){ fx_get_dropdown(o,'".$cols[$i]["name"]."'); },200); $old_init }"; 
				}

				// on postback check
				if ($_POST["src"] == $cols[$i]["name"] && isset($_POST["return"]))
				{
					$row = $_POST;

					// execute correct sql based on load event
					if ($row['event'] == 'onload')
					{
						$sql = $col_tmp["editoptions"]["onload"]["sql"];
					}
					else
					{
						$sql = $col_tmp["editoptions"]["onchange"]["sql"];
						$search_on = $col_tmp["editoptions"]["onchange"]["search_on"];
					}

					// remove non-db posted data, $row has all current selections
					unset($row["return"]); 
					unset($row["src"]); 
					unset($row["value"]);
					 
					$term = $_POST["value"];
					if (isset($term))
					{
						// if search_on field is passed (old logic)
						if (!empty($search_on))
						{
							// if subqurey
							if (preg_match('/SELECT (.*) \\((.*)\) (.*)/', $sql, $match))
							{
								if (preg_match('/SELECT .* \\((.*)\) (.*) WHERE (.*)/', $sql, $match))
									$cond = "AND";
								else
									$cond = "WHERE";
							}
							// if normal query
							else if (stristr($sql, " WHERE "))
								$cond = "AND";
							else
								$cond = "WHERE";
							
							$sql = $sql. " $cond {$search_on} = '$term'";
						}

						// new logic, use any field in sql using placeholder
						$sql = str_replace("{".$_POST["src"]."}", $term, $sql);
					}

					foreach($row as $k=>$v)
					{
						$sql = str_replace("{".$k."}", $v, $sql);
					}

					$this->get_dependent_dropdown($sql,$_POST["return"]);
				}

				unset($cols[$i]["editoptions"]["onload"]);
			}
		}

		// make first column as key for postbacks
		$cols[0]["key"] = true;

		$this->options["colModel"] = $cols;
		foreach($cols as $c)
		{
			$this->options["colNames"][] = $c["title"];		
		}
	}

	/**
	 * Common function for db operations
	 */
	function execute_query($sql,$return="")
	{
		global $db;
		if ($this->con)
		{
			$ret = $this->con->Execute($sql);
			if (!$ret)
			{
				if ($this->debug)
					phpgrid_error("Couldn't execute query. ".$this->con->ErrorMsg()." - $sql");
				else
					phpgrid_error($this->error_msg);
			}

			if ($return == "insert_id")
				return $this->con->Insert_ID();
		}
		else
		{
			$ret = mysqli_query($db, $sql);
			if (!$ret)
			{
				if ($this->debug)
					phpgrid_error("Couldn't execute query. ".mysqli_error($db)." - $sql");		
				else
					phpgrid_error($this->error_msg);
			}

			if ($return == "insert_id")
				return mysqli_insert_id($db);
		}
		//echo $sql;
		return $ret;
	}

	/**
	 * Generate JSON array for grid rendering
	 * @param $grid_id Unique ID for grid
	 */
	function render($grid_id)
	{
		// render grid for first time (non ajax), but specific grid on ajax calls
		$is_ajax = isset($_REQUEST["nd"]) || isset($_REQUEST["oper"]) || isset($_REQUEST["export"]);
		if ($is_ajax && $_GET["grid_id"] != $grid_id)
			return;

		$append_by = (strpos($this->options["url"],"?") === false) ? "?" : "&";

		$this->options["url"] .= $append_by."grid_id=$grid_id";
		$this->options["editurl"] .= $append_by."grid_id=$grid_id";
		$this->options["cellurl"] .= $append_by."grid_id=$grid_id";
		
		if (isset($_REQUEST["subgrid"]))
		{
			$grid_id = "_".$_REQUEST["subgrid"]."_".$grid_id;
		}
			
		$this->id = $grid_id;

		### P ###
		// custom on select event execution
		if (!empty($this->events["on_select"]))
		{
			$func = $this->events["on_select"][0];
			$obj = $this->events["on_select"][1];
			$continue = $this->events["on_select"][2];
				
			$event_sql = "";
			$event_sql_count = "";
				
			if ($obj)
				call_user_func(array($obj,$func),array("param"=> $_REQUEST, "grid"=>$this, "sql" => &$event_sql, "sql_count" => &$event_sql_count));
			else
				call_user_func($func,array("param"=> $_REQUEST, "grid"=>$this, "sql" => &$event_sql, "sql_count" => &$event_sql_count));
				
			$this->internal["sql_count"] = $event_sql_count;
			$this->internal["sql"] = $event_sql;
		}
				
		// generate column names, if not defined
		if (!$this->options["colNames"])
			$this->set_columns();
			
		### P ###
		// persist search if asked
		if ($this->options["persistsearch"] === true)
		{
			$this->options["search"] = true;
			$this->options["postData"] = array("filters" => $_SESSION["jqgrid_{$grid_id}_searchstr"] ); // this performs the search
				
			$array_of_search_values = json_decode($_SESSION["jqgrid_{$grid_id}_searchstr"], true);
			foreach ($array_of_search_values["rules"] as &$rules) 
			{
				foreach($this->options["colModel"] as &$col)
				{
					if( $rules['field'] == $col["name"] )
					{
						$search_word=$rules['data'];
						$col["searchoptions"]["defaultValue"] = $search_word;
					}
				}
			}
		}
			
		### P ###
		// manage uploaded files (grid_id check for master-detail fix || subgrid check)
		if (count($_FILES) && ($_REQUEST["grid_id"] == $grid_id || "_".$_REQUEST["subgrid"]."_".$_REQUEST["grid_id"] == $grid_id))
		{
			$files = array_keys($_FILES);
			$fileElementName = $files[0];

			if(!empty($_FILES[$fileElementName]['error']))
			{
				switch($_FILES[$fileElementName]['error'])
				{
					case '1':
						$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
						break;
					case '2':
						$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
						break;
					case '3':
						$error = 'The uploaded file was only partially uploaded';
						break;
					case '4':
						$error = 'No file was uploaded.';
						break;

					case '6':
						$error = 'Missing a temporary folder';
						break;
					case '7':
						$error = 'Failed to write file to disk';
						break;
					case '8':
						$error = 'File upload stopped by extension';
						break;
					case '999':
					default:
						$error = 'No error code avaiable';
				}
			}
			elseif(empty($_FILES[$fileElementName]['tmp_name']) || $_FILES[$fileElementName]['tmp_name'] == 'none')
			{
				$error = 'No file was uploaded';
			}
			else 
			{
				foreach($this->options["colModel"] as $c)
				{
					if ($c["upload_dir"] != "" && $c["name"]."_file" == $fileElementName)
					{
						$tmp_name = $_FILES[$fileElementName]["tmp_name"];
						$name = $_FILES[$fileElementName]["name"];
						
						$uploads_dir = $c["upload_dir"];
						
						if(!file_exists($uploads_dir))
							@mkdir($uploads_dir,0755,true);
						 
						// set to rename file by default
						if (empty($c["editrules"]["ifexist"]))
							$c["editrules"]["ifexist"] = "rename";
							
						// check if required
						if ($c["editrules"]["ifexist"] == "error")
						{
							if (file_exists("$uploads_dir/$name"))
							{
								$error = "File already exist: $uploads_dir/$name";
								break;
							}	
						}
						else if ($c["editrules"]["ifexist"] == "rename")
						{
							// rename file if exist
							$ext = strrchr($name, '.');
							$prefix = substr($name, 0, -strlen($ext));
							$i = 0;
							while(file_exists("$uploads_dir/$name")) // If file exists, add a number to it.
							{ 
								$name = $prefix . "_" . ++$i . $ext;
							}
						}

						if ( @move_uploaded_file($tmp_name, "$uploads_dir/$name") )
						{
							//! JOHN - $msg = "$uploads_dir/$name";
							$msg = "$name";
						}
						else
							$error = "Unable to move to desired folder $uploads_dir/$name";
						
						break;
					}
				}
			}
			
			echo "{";
			echo	"error: '" . $error . "',";
			echo	"msg: '" . $msg . "'";
			echo "}//"; # fix for upload lib, it get response from doc body that include <canvas>
			die;
		}
		### P-END ###
		
		if (isset($_POST['oper']))
		{
			// removed in case of treeGrid
			if ($this->options["treeGrid"] == true)
			{
				unset($_POST["expanded"]);
				unset($_POST["icon"]);
				unset($_POST["isLeaf"]);
				unset($_POST["level"]);
				unset($_POST["loaded"]);
			}
			
			$op = $_POST['oper'];
			$data = $_POST;
			$pk_field = $this->options["colModel"][0]["index"];
								
			// fix for dialog edit v/s inline edit
			$id = (isset($data[$pk_field])?$data[$pk_field]:$data["id"]);

			// formatters array for k->v
			$is_numeric = array();

			// reformat date w.r.t mysql
			foreach( $this->options["colModel"] as $c )
			{
				// don't fix vars that are not posted (celledit mode)
				if (!isset($data[$c["index"]]))
					continue;

				// fix for short weekday name 
				if (strstr($c["formatoptions"]["newformat"],"D"))
				{
					$data[$c["index"]] = str_ireplace(array("sun","mon","tue","wed","thu","fri","sat"), "", $data[$c["index"]]);
					$data[$c["index"]] = trim($data[$c["index"]]);
				}

				// fix for d/m/Y or d/m/y date format. strtotime expects m/d/Y
				if (stristr($c["formatoptions"]["newformat"],"d/m/Y"))
				{
					$data[$c["index"]] = preg_replace('/(\d+)\/(\d+)\/(\d+)/i','$2/$1/$3',$data[$c["index"]]);
				}
				// fix for d-m-y (2 digit year) for strtotime
				else if (strstr($c["formatoptions"]["newformat"],"d-m-y"))
				{
					$data[$c["index"]] = preg_replace('/(\d+)-(\d+)-(\d+)/i','$3-$2-$1',$data[$c["index"]]);
				}		
				else if (strstr($c["formatoptions"]["newformat"],"d/M/Y") || strstr($c["formatoptions"]["newformat"],"d-M-Y"))
				{
					$data[$c["index"]] = preg_replace('/\/\-/i',' ',$data[$c["index"]]);
				}
				
				// put zeros for blank date field
				if (($c["formatter"] == "date" || $c["formatter"] == "datetime") && (empty($data[$c["index"]]) || $data[$c["index"]] == "//"))
				{
					$data[$c["index"]] = "NULL";
				}
				// if db field allows null, then set NULL
				else if ($c["isnull"] && empty($data[$c["index"]]))
				{
					$data[$c["index"]] = "NULL";
				}
				else if ($c["formatter"] == "date")
				{
					$data[$c["index"]] = $this->custom_date_format("Y-m-d",$data[$c["index"]]);
				}
				else if ($c["formatter"] == "datetime")
				{
					$data[$c["index"]] = $this->custom_date_format("Y-m-d H:i:s",$data[$c["index"]]);
				}
				// remove for lookup FK data, and dont when searching in same field
				else if ($c["formatter"] == "autocomplete" && (isset($c["formatoptions"]["update_field"]) && $c["index"] != $c["formatoptions"]["update_field"]) )
				{
					unset($data[$c["index"]]);
				}				
				else if ($c["formatter"] == "password" && $data[$c["index"]] == "*****")
				{
					unset($data[$c["index"]]);
				}

				// isnumeric check for sql '' issue
				if ($c["isnum"] === true)
					$is_numeric[$c["index"]] = true;
			}

			// handle grid operations of CRUD
			switch($op)
			{
				### P ###
				case "autocomplete":
					$field = $data['element'];
					$term = $data['term'];
					foreach( $this->options["colModel"] as $c )
					{
						if ($c["index"] == $field)
						{
							// if subqurey
							if (preg_match('/SELECT (.*) \\((.*)\) (.*)/', $c["formatoptions"]["sql"], $match))
							{
								if (preg_match('/SELECT .* \\((.*)\) (.*) WHERE (.*)/', $c["formatoptions"]["sql"], $match))
									$cond = "AND";
								else
									$cond = "WHERE";
							}
							// if normal query
							else if (stristr($c["formatoptions"]["sql"], " WHERE "))
								$cond = "AND";
							else
								$cond = "WHERE";

							$search_on = (!empty($c["dbname"])) ? $c["dbname"] : $c["index"];
							if (!empty($c["formatoptions"]["search_on"]))
								$search_on = $c["formatoptions"]["search_on"];
							
							$sql = $c["formatoptions"]["sql"];
							
							// default contains, if set then bw (begins with)
							if ($c["formatoptions"]["op"] == "bw")
								$where_part = " $cond {$search_on} like '$term%'";
							else
							{
								// case in-sensitive search for oracle etc
								if (strpos($this->db_driver,"oci8") !== false || strpos($this->db_driver,"db2") !== false || strpos($this->db_driver,"postgres") !== false)
									$where_part .= " $cond LOWER({$search_on}) like LOWER('%$term%')";
								else
									$where_part = " $cond {$search_on} like '%$term%'";
							}
							// insert where condition before orderby
							if (($p = stripos($sql,"ORDER BY")) !== false)
							{
								$start = substr($sql,0,$p);
								$end = substr($sql,$p);
								$sql = $start." $where_part ".$end;
							}
							else
							{
								$sql .= $where_part;
							}
				
							$result = $this->execute_query($sql);
							if ($this->con)
							{
								$rows = $result->GetArray();
								foreach ($rows as $key => $row) 
								{
									$arr = array();
									$arr['id'] = (isset($row["K"]) ? $row["K"] : $row["k"]);
								    $arr['label'] = (isset($row["V"]) ? $row["V"] : $row["v"]);
								    $arr['value'] = (isset($row["V"]) ? $row["V"] : $row["v"]);
									
									if ($c["formatoptions"]["callback"]) $arr['data'] = $row;
									
								    $data_arr[] = $arr;									
								}
							}
							else
							{
								while($row = mysqli_fetch_assoc($result)) 
								{
									$arr = array();
								    $arr['id'] = $row['k'];
								    $arr['label'] = $row['v'];
								    $arr['value'] = $row['v'];
									
									if ($c["formatoptions"]["callback"]) $arr['data'] = $row;
								    
									$data_arr[] = $arr;
								}
							}

							header('Content-type: application/json');
							echo json_encode($data_arr);
							die;
						}
					}
					break;

				### P ###
				case "clone":
						// only clone if grid id is matched (fix for master-detail)
						if ($data["grid_id"] != $grid_id)
							break;

						$src_id = $data['id'];

						// get columns to build INSERT - SELECT query
						$sql = "SELECT * FROM ".$this->table . " LIMIT 1 OFFSET 0";
						$sql = $this->prepare_sql($sql,$this->db_driver);
						
						$result = $this->execute_query($sql);

						// and exclude PK
						if ($this->con)
						{
							$arr = $result->FetchRow();
							foreach($arr as $k=>$rs)
								if ($k != $pk_field)
									$f[] = $k;
						}
						else
						{		
							$numfields = mysqli_num_fields($result);
							for ($i=0; $i < $numfields; $i++) // Header
							{
								$k = mysqli_field_name($result, $i);
								if ($k != $pk_field)
									$f[] = $k;
							}
						}

						// custom onclone event execution
						if (!empty($this->events["on_clone"]))
						{
							$func = $this->events["on_clone"][0];
							$obj = $this->events["on_clone"][1];
							$continue = $this->events["on_clone"][2];
								
							if ($obj)
								call_user_func(array($obj,$func),array($pk_field => $src_id, "params" => &$f));
							else
								call_user_func($func,array($pk_field => $src_id, "params" => &$f));
						
							if (!$continue)
								break;
						}						

						// wrap all fields in clone query
						$pk_field = $this->wrap_field($pk_field);
						for($i=0;$i<count($f);$i++)
							$f[$i] = $this->wrap_field($f[$i]);
						
						$fields_str = implode(",",$f);
						$sql = "INSERT INTO {$this->table} ($fields_str) SELECT $fields_str FROM {$this->table} WHERE $pk_field = $src_id";
						$insert_id = $this->execute_query($sql,"insert_id");

						if (intval($insert_id)>0)
							$res = array("id" => $insert_id, "success" => true);
						else
							$res = array("id" => 0, "success" => false);

						echo json_encode($res);		
					break;

				case "add":
					if ($pk_field != "id")
						unset($data['id']);
						
					unset($data['oper']);
					
					$update_str = array();

					### P ###
					// custom oninsert event execution
					if (!empty($this->events["on_insert"]))
					{
						$func = $this->events["on_insert"][0];
						$obj = $this->events["on_insert"][1];
						$continue = $this->events["on_insert"][2];
						
						if ($obj)
							call_user_func(array($obj,$func),array($pk_field => $id, "params" => &$data));
						else
							call_user_func($func,array($pk_field => $id, "params" => &$data));
						
						if (!$continue)
							break;
					}
					
					foreach($data as $k=>$v)
					{
						// skip first column while insert, unless autoid = false
						if ($k == $pk_field && $this->options["colModel"][0]["autoid"] !== false)
							continue;
							
						$k = addslashes($k);
						$v = $this->escape_string($v);

						$v = ($v == "NULL" || $is_numeric[$k] === true) ? $v : "'$v'";
						$values_str[] = "$v";
						
						// e.g. wrap tilde sign for mysql
						$k = $this->wrap_field($k);

						$fields_str[] = "$k";
					}
					
					$insert_str = "(".implode(",",$fields_str).") VALUES (".implode(",",$values_str).")";

					$sql = "INSERT INTO {$this->table} $insert_str";

					$insert_id = $this->execute_query($sql,"insert_id");

					### P ###
					// custom onupdate event execution
					if (!empty($this->events["on_after_insert"]))
					{
						$func = $this->events["on_after_insert"][0];
						$obj = $this->events["on_after_insert"][1];
						$continue = $this->events["on_after_insert"][2];
						
						if ($obj)
							call_user_func(array($obj,$func),array($pk_field => $insert_id, "params" => &$data));
						else
							call_user_func($func,array($pk_field => $insert_id, "params" => &$data));
						
						if (!$continue)
							break;
					}
					
					// for inline row addition, return insert id to update PK of grid (e.g. order_id#33)
					if ($id == "new_row")
						die($pk_field."#".$insert_id);

					// return JSON response for insert id
					if (intval($insert_id)>0)
						$res = array("id" => $insert_id, "success" => true);
					else
						$res = array("id" => 0, "success" => false);

					echo json_encode($res);

					break;
					
				case "edit":
					//pr($_POST);
					if ($pk_field != "id")
						unset($data['id']);
						
					unset($data['oper']);
					
					$update_str = array();

					### P ###
					// custom onupdate event execution
					if (!empty($this->events["on_update"]))
					{
						$func = $this->events["on_update"][0];
						$obj = $this->events["on_update"][1];
						$continue = $this->events["on_update"][2];
						
						if ($obj)
							call_user_func(array($obj,$func),array($pk_field => $id, "params" => &$data));
						else
							call_user_func($func,array($pk_field => $id, "params" => &$data));
						
						if (!$continue)
							break;
					}
					
					foreach($data as $k=>$v)
					{
						$k = addslashes($k);

						// skip PK in update sql
						if ($k == $pk_field)
							continue;

						// e.g. wrap tilde sign for mysql
						$k = $this->wrap_field($k);
						
						$v = $this->escape_string($v);
						
						// dont update blank fields in case of bulk edit
						if (strstr($id,",") !== false && ($v === "" || $v == "NULL"))
							continue;
						
						// if blank option is select in bulk edit
						if ($v=="-") $v = "";
							
						$v = ($v == "NULL" || $is_numeric[$k] === true) ? $v : "'$v'";
						$update_str[] = "$k=$v";
					}
					
					// don't run update if no field is changed (in bulk edit)
					if (count($update_str)==0)
						break;
						
					$update_str = "SET ".implode(",",$update_str);
					
					$id_sql = "'".implode("','",explode(",", $id))."'";
					$pk_field_sql = $this->wrap_field($pk_field);
					
					$sql = "UPDATE {$this->table} $update_str WHERE $pk_field_sql IN ($id_sql)";
					$ret = $this->execute_query($sql);

					### P ###
					// custom on after update event execution
					if (!empty($this->events["on_after_update"]))
					{
						$func = $this->events["on_after_update"][0];
						$obj = $this->events["on_after_update"][1];
						$continue = $this->events["on_after_update"][2];
						
						if ($obj)
							call_user_func(array($obj,$func),array($pk_field => $id, "params" => &$data));
						else
							call_user_func($func,array($pk_field => $id, "params" => &$data));
						
						if (!$continue)
							break;
					}

					// return JSON response for update (passing id that was updated)
					if ($ret)
						$res = array("id" => $id, "success" => true);
					else
						$res = array("id" => 0, "success" => false);

					echo json_encode($res);

				break;			
				
				case "del":
					// row to delete is passed as id
					$id = $data["id"];
					
					### P ###
					// custom on delete event execution
					if (!empty($this->events["on_delete"]))
					{
						$func = $this->events["on_delete"][0];
						$obj = $this->events["on_delete"][1];
						$continue = $this->events["on_delete"][2];
						if ($obj)
							call_user_func(array($obj,$func),array($pk_field => $id));
						else
							call_user_func($func,array($pk_field => $id));
						
						if (!$continue)
							break;
					}
					
					$pk_field_sql = $this->wrap_field($pk_field);
					$id_sql = "'".implode("','",explode(",",$id))."'";
					
					$sql = "DELETE FROM {$this->table} WHERE $pk_field_sql IN ($id_sql)";
					$this->execute_query($sql);
					
					### P ###
					// custom on after delete event execution
					if (!empty($this->events["on_after_delete"]))
					{
						$func = $this->events["on_after_delete"][0];
						$obj = $this->events["on_after_delete"][1];
						$continue = $this->events["on_after_delete"][2];
						if ($obj)
							call_user_func(array($obj,$func),array($pk_field => $id));
						else
							call_user_func($func,array($pk_field => $id));
					
						if (!$continue)
							break;
					}

				break;
			}
			
			die;
		}
		
		// apply search conditions (where clause)
		$wh = "";
		
		if (!isset($_REQUEST['_search']))
			$_REQUEST['_search'] = "";
		
		$searchOn = $this->strip($_REQUEST['_search']);
		if($searchOn=='true') 
		{
			$fld = $this->strip($_REQUEST['searchField']);
			
			$cols = array();
			foreach($this->options["colModel"] as $col)
				$cols[] = $col["index"];

			// quick search bar
			if (!$fld)
			{
				$searchstr = $this->strip($_REQUEST['filters']);

				// persist search string 
				$_SESSION["jqgrid_{$this->id}_searchstr"] = $searchstr;				
				$wh = $this->construct_where($searchstr);
			}
			// search popup form, simple one
			else
			{
				if(in_array($fld,$cols)) 
				{	
					$fldata = $this->strip($_REQUEST['searchString']);
					$foper = $this->strip($_REQUEST['searchOper']);
					
					# fix for conflicting table name fields (used alias from page, in property dbname)
					foreach($this->options["colModel"] as $link_c)
					{
						// only used exact date match, when operator is not 'cn' (contains) - default is cn
						if ($fld == $link_c["name"] && !empty($link_c["formatoptions"]) && in_array($foper, array("ne","eq","gt","ge","lt","le")))
						{
							// fix for d/m/Y or d/m/y date format. strtotime expects m/d/Y
							if (stristr($link_c["formatoptions"]["newformat"],"d/m/Y"))
							{
								$fldata = preg_replace('/(\d+)\/(\d+)\/(\d+)/i','$2/$1/$3',$fldata);
							}
							// fix for d-m-y (2 digit year) for strtotime
							else if (strstr($link_c["formatoptions"]["newformat"],"d-m-y"))
							{
								$fldata = preg_replace('/(\d+)-(\d+)-(\d+)/i','$3-$2-$1',$fldata);
							}
							else if (strstr($link_c["formatoptions"]["newformat"],"d/M/Y") || strstr($link_c["formatoptions"]["newformat"],"d-M-Y"))
							{
								$fldata = preg_replace('/\/\-/i',' ',$fldata);
							}							

							if ($link_c["formatter"] == "date")
								$fldata = $this->custom_date_format("Y-m-d",$fldata);
							else if ($link_c["formatter"] == "datetime")
								$fldata = $this->custom_date_format("Y-m-d H:i:s",$fldata);
						}

						if ($fld == $link_c["name"] && !empty($link_c["dbname"]))
						{
							$fld = $link_c["dbname"];
						}
					}
					
					$fld = $this->wrap_field($fld);
					
					// make case insensitive for oracle
					if ($foper == "cn")
						if (strpos($this->db_driver,"oci8") !== false || strpos($this->db_driver,"db2") !== false)
							$fld = "LOWER($fld)";

					// costruct where
					$wh .= " AND ".$fld;
					switch ($foper) {					
						case "eq":
							if(is_numeric($fldata)) {
								$wh .= " = ".$fldata;
							} else {
								$wh .= " = '".$fldata."'";
							}
							break;
						case "ne":
							if(is_numeric($fldata)) {
								$wh .= " <> ".$fldata;
							} else {
								$wh .= " <> '".$fldata."'";
							}
							break;
						case "lt":
							if(is_numeric($fldata)) {
								$wh .= " < ".$fldata;
							} else {
								$wh .= " < '".$fldata."'";
							}
							break;
						case "le":
							if(is_numeric($fldata)) {
								$wh .= " <= ".$fldata;
							} else {
								$wh .= " <= '".$fldata."'";
							}
							break;
						case "gt":
							if(is_numeric($fldata)) {
								$wh .= " > ".$fldata;
							} else {
								$wh .= " > '".$fldata."'";
							}
							break;
						case "ge":
							if(is_numeric($fldata)) {
								$wh .= " >= ".$fldata;
							} else {
								$wh .= " >= '".$fldata."'";
							}
							break;
						case "ew":
							$wh .= " LIKE '%".$fldata."'";
							break;
						case "en":
							$wh .= " NOT LIKE '%".$fldata."'";
							break;
						case "cn":
							
							// make case insensitive for oracle
							if (strpos($this->db_driver,"oci8") !== false || strpos($this->db_driver,"db2") !== false)
								$wh .= " LIKE LOWER('%".$fldata."%')";
							else
								$wh .= " LIKE '%".$fldata."%'";
								
							break;
						case "nc":
							$wh .= " NOT LIKE '%".$fldata."%'";
							break;
						case "in":
							$wh .= " IN (".$fldata.")";
							break;
						case "ni":
							$wh .= " NOT IN (".$fldata.")";
							break;
						case "nu":
							$wh .= " IS NULL";
							break;
						case "nn":
							$wh .= " IS NOT NULL";
							break;
						case "bw":
						default:
							$fldata .= "%";
							$wh .= " LIKE '".$fldata."'";
							break;
					}
				}
			}
			// setting to persist where clause in export option
			$_SESSION["jqgrid_{$grid_id}_filter"] = $wh;
			$_SESSION["jqgrid_{$grid_id}_filter_request"] = $_REQUEST["filters"];
		}
		elseif($searchOn=='false') 
		{
			unset($_SESSION["jqgrid_{$grid_id}_filter"]);
			unset($_SESSION["jqgrid_{$grid_id}_filter_request"]);
		}
		
		### P ###
		if ($this->options["treeGrid"]==true)
		{
			
			foreach ($this->options["colModel"] as &$c)
			{
				if ( in_array($c["name"],array($this->options["treeConfig"]["id"],$this->options["treeConfig"]["parent"])) )	
					$c["hidden"]=true;
			}
			
			// hide actions column
			$this->actions["rowactions"] = false;
			
			$this->options["ExpandColClick"]=true;
			$this->options["ExpandColumn"]=$this->options["treeConfig"]["column"];
			$this->options["treedatatype"]="json";
			$this->options["treeGridModel"]="adjacency";
			// $this->options["loadonce"]=true;
			$this->options["treeReader"]=array(
									"parent_id_field"=>$this->options["treeConfig"]["parent"],
									"level_field"=>"level",
									"leaf_field"=>"isLeaf",
									"expanded_field"=>"expanded",
									"loaded"=>"loaded",
									"icon_field"=>"icon"
									);		
					
			if ($this->options["treeConfig"]["loaded"] === false)
			{
				$this->options["treeConfig"]["expanded"] = false;
				
				// Get parameters from the grid
				$node = (integer)$_REQUEST["nodeid"];
				$n_lvl = (integer)$_REQUEST["n_level"];

				// check to see which node to load
				if($node >0) 
				{ 
				   $where_tree = $this->options["treeConfig"]["parent"].'='.$node; // parents
				   $n_lvl = $n_lvl+1; // we should ouput next level
				} 
				else 
				{
				   $where_tree = 'ISNULL('.$this->options["treeConfig"]["parent"].')'; // roots
				}
				
				$wh .= " AND ($where_tree)";
			}
			
		}		
		
		// generate main json
		if (isset($_GET['jqgrid_page']))
		{
			$page = $_GET['jqgrid_page']; // get the requested page
			$limit = $_GET['rows']; // get how many rows we want to have into the grid
			$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
			$sord = $_GET['sord']; // get the direction

			// if set, use dbname for sorting (//JOHN - 26/5/19 - tri par alis name et nom par db name pour sql avec union)
			foreach ($this->options["colModel"] as $c)
				if ($c["name"] == $sidx && !empty($c["dbname"] && empty($c["sortalias"]))) 
				{
					$sidx = $c["dbname"];
					break;
				}
				
			if(!$sidx) $sidx = 1;
			if(!$limit) $limit = 20;

			// use tilda sign for sort by + except multiple sort e.g. fix `gender asc, name` desc & sql func e.g. concat(a,b)
			if($sidx != 1 && strstr($sidx,",") === false && strstr($sidx,"(") === false)
			{
				$sidx = $this->wrap_field($sidx);
			}
				
			// persist for export data
			if (isset($_GET["export"]))
			{
				$sidx = $_SESSION["jqgrid_{$grid_id}_sort_by"];
				$sord = $_SESSION["jqgrid_{$grid_id}_sort_order"];
				$limit = $_SESSION["jqgrid_{$grid_id}_rows"];
			}
			else
			{
				$_SESSION["jqgrid_{$grid_id}_sort_by"] = $sidx;
				$_SESSION["jqgrid_{$grid_id}_sort_order"] = $sord;
				$_SESSION["jqgrid_{$grid_id}_rows"] = $limit;
			}
						
			### P ###
			// if export option is requested
			if (isset($_GET["export"]))
			{
				set_time_limit(0);
				$arr = array();

				// export data array (if grid loaded from array)
				if (is_array($this->table))
				{
					$t = $this->table;
					foreach($t[0] as $k => $v)
						$temp[$k] = ucwords($k);

					$arr[] = $temp;
					foreach ($t as $key => $value) {
						$arr[] = $value;
					}
					
					// custom export function if needed
					if (!empty($this->events["on_export"]))
					{	
						$func = $this->events["on_export"][0];
						$obj = $this->events["on_export"][1];
						$continue = $this->events["on_export"][2];

						if ($obj)
							call_user_func(array($obj,$func),array("data" => $arr, "grid" => &$this));
						else
							call_user_func($func,array("data" => $arr, "grid" => &$this));

						if (!$continue)
							exit(0);						
					}					
				}
				// if grid loaded from db
				else
				{
					// by default export all
					$export_where = "";
					
					// if range is filtered OR filters are passed in url (export selected feature)
					if ($this->options["export"]["range"] == "filtered" || !empty($_GET["filters"]))
						$export_where = $_SESSION["jqgrid_{$grid_id}_filter"];

					$limit_sql= "";
					if ($this->options["export"]["paged"] == "1")
					{
						$offset = $limit*$page - $limit; // do not put $limit*($page - 1)
						if ($offset<0) $offset = 0;
						$limit_sql = "LIMIT $limit OFFSET $offset";
					}

					// preserve subqueries
					$matches_subsql = $this->remove_subsql();

					// if sql is set on on_select event
					if (!empty($this->internal["sql"]))
					{
						$SQL = $this->internal["sql"]." $limit_sql";
					}
					else if (($p = stripos($this->select_command,"GROUP BY")) !== false)
					{
						//$start = substr($this->select_command,0,$p);
						//$end = substr($this->select_command,$p);
						//$SQL = $start.$export_where.$end." ORDER BY $sidx $sord $limit_sql";
						
						$tmpsql = explode('GROUP BY', $this->select_command);
						$SQL = implode($export_where.' GROUP BY ', $tmpsql);
						
					}
					else
						$SQL = $this->select_command.$export_where." ORDER BY $sidx $sord $limit_sql";

					// re-adjust subqueries in sql
					$SQL = $this->add_subsql($SQL,$matches_subsql);
				
					// custom export function if needed
					if (!empty($this->events["on_export"]))
					{	
						$func = $this->events["on_export"][0];
						$obj = $this->events["on_export"][1];
						$continue = $this->events["on_export"][2];

						if ($obj)
							call_user_func(array($obj,$func),array("sql" => $SQL, "grid" => &$this));
						else
							call_user_func($func,array("sql" => $SQL, "grid" => &$this));

						if (!$continue)
							exit(0);						
					}
	
					$SQL = $this->prepare_sql($SQL,$this->db_driver);
					$result = $this->execute_query($SQL);

					foreach ($this->options["colModel"] as $c)
					{
						$header[$c["name"]] = $c["title"];
					}

					$arr[] = $header;
					
					if ($this->con)
					{
						$rows = $result->GetRows();
						foreach($rows as $row)
						{
							$export_data = array();

							foreach($header as $k=>$v)
								$export_data[$k] = $row[$k];	

							$arr[] = $export_data;						
						}
					}
					else
					{
						while($row = mysqli_fetch_array ($result,MYSQLI_ASSOC))
						{
							$export_data = array();

							foreach($header as $k=>$v)
								$export_data[$k] = $row[$k];
								
							$arr[] = $export_data;
						}						
					}
				}

				$col_widths = array();
				// export only selected columns
				$cols_not_to_export = array();
				$cols_to_export = array();
				if ($this->options["colModel"])
				{
					foreach ($this->options["colModel"] as $c)
					{
						// column chooser integration with export
						if (isset($_COOKIE["jqgrid_colchooser"]) && !empty($_COOKIE["jqgrid_colchooser"]))
						{
							$colchooser = explode(",",$_COOKIE["jqgrid_colchooser"]);
							if (!in_array($c["name"],$colchooser))
								$c["export"] = false;
						}
						
						if ($c["export"] === false)
							$cols_not_to_export[] = $c["name"];
						else
						{
							$cols_to_export[] = $c["name"];
							$col_widths[$c["name"]] = (!empty($c["width"]) ? $c["width"] : 'auto');
						}
					}
				}
				
				// custom on_data_display event execution (for export)
				if (!empty($this->events["on_data_display"]))
				{
					$func = $this->events["on_data_display"][0];
					$obj = $this->events["on_data_display"][1];
					
					// remove header
					$h = array_shift($arr);
					
					if ($obj)
						call_user_func(array($obj,$func),array("params" => &$arr));
					else
						call_user_func($func,array("params" => &$arr));

					// add header
					array_unshift($arr,$h);
				}	
				
				// fix for d/m/Y date format in export. strtotime expects m/d/Y
				foreach($this->options["colModel"] as $c)
				{
					foreach ($arr as &$rec)
					{
						// skip header from date format conversion
						if ($rec === $arr[0])
							continue;

						// show masked data in password
						if (isset($c["formatter"]) && $c["formatter"] == "password")
							$rec[$c["name"]] = "*****";
						
						if (!empty($rec[$c["name"]]) && ($c["formatter"] == "date" || $c["formatter"] == "datetime"))
						{
							$dt = $rec[$c["name"]];

							$js_dt_fmt = $c["formatoptions"]["newformat"];
							
							// replace non-dates to empty string
							if ($dt != "1970-01-01" && $dt != "0000-00-00" && $dt != '0000-00-00 00:00:00')
								$rec[$c["name"]] = $this->custom_date_format($js_dt_fmt,$dt);
							else
								$rec[$c["name"]] = "";
						}

						### P ###
						// Replace condition data in pdf export
						$col_name = $c["name"];
						if (isset($c["default"]) && !isset($rec[$col_name]))
							$rec[$col_name] = $c["default"];

						// link data in grid to any given url
						if (!empty($c["default"]))
						{
							// replace any param in link e.g. http://domain.com?id={id} given that, there is a $col["name"] = "id" exist
							$rec[$col_name] = $this->replace_row_data($rec,$c["default"]);
						}
						
						// check conditional data
						if (!empty($c["condition"][0]))
						{
							$r = true;

							// replace {} placeholders from conditional data
							/* JOHN - 17/6/19 - fix bug on condition replacement	
							$c["condition"][1] = $this->replace_row_data($rec,$c["condition"][1]);
							$c["condition"][2] = $this->replace_row_data($rec,$c["condition"][2]);

							// fix as $row was used in condition and $rec is var here
							$c["condition"][0] = str_replace("row","rec",$c["condition"][0]);
							
							eval("\$r = ".$c["condition"][0].";");
							$rec[$col_name] = ( $r ? $c["condition"][1] : $c["condition"][2]);
							*/

							$cond1 = $this->replace_row_data($rec,$c["condition"][1]);
							$cond2 = $this->replace_row_data($rec,$c["condition"][2]);

							// fix as $row was used in condition and $rec is var here
							$cond0 = str_replace("row","rec",$c["condition"][0]);
							
							eval("\$r = ".$cond0.";");
							$rec[$col_name] = ( $r ? $cond1 : $cond2);

							$rec[$col_name] = strip_tags($rec[$col_name]);
						}
							
						// check data filter (alternate of grid on_data_display, but for current column)
						if (!empty($c["on_data_display"]))
						{
							$func = $c["on_data_display"][0];
							$obj = $c["on_data_display"][1];
							$param = $c["on_data_display"][2];
							
							if (!empty($param))
								$params = array($rec,$param);
							else
								$params = array($rec);
							
							if ($obj)
								$rec[$col_name] = call_user_func_array(array($obj,$func),$params);
							else
								$rec[$col_name] = call_user_func_array($func,$params);
						}						
					}
				}

				// remove db columns as well as virtual columns
				if (!empty($cols_to_export))
				{
					$export_arr = array();
					foreach($arr as $arr_item)
					{
						foreach($arr_item as $k=>$i)
						{
							if (!in_array($k, $cols_to_export))
							{
								unset($arr_item[$k]);
							}
						}
						$export_arr[] = $arr_item;
					}	
					$arr = $export_arr;
				}

				if (!$this->options["export"]["filename"])
					$this->options["export"]["filename"] = $grid_id;
					
				if (!$this->options["export"]["sheetname"])
					$this->options["export"]["sheetname"] = ucwords($grid_id). " Sheet";
					
				// fix for ie - http://support.microsoft.com/kb/316431
				if(preg_match('/(?i)msie /',$_SERVER['HTTP_USER_AGENT']))
					header('Cache-control: cache,must-revalidate');

				if ($this->options["export"]["format"] == "pdf")
				{
					if ($this->options["export"]["render_type"] == "html")
					{
						$html = "";
						// if customized pdf render is defined, use that
						if (!empty($this->events["on_render_pdf"]))
						{	
							$func = $this->events["on_render_pdf"][0];
							$obj = $this->events["on_render_pdf"][1];
							if ($obj)
								$html = call_user_method($func,$obj,array("grid" => $this, "data" => $arr));
							else
								$html = call_user_func($func,array("grid" => $this, "data" => $arr));
						}
						else
						{
							$html .= "<h1>".$this->options["export"]["heading"]."</h1>";
							$html .= '<table border="0" cellpadding="4" cellspacing="2">';
							
							$i = 0;
							foreach($arr as $v)
							{
								$shade = ($i++ % 2) ? 'bgcolor="#efefef"' : '';
								$html .= "<tr>";
								foreach($v as $d)
								{
									// bold header
									if  ($i == 1)
										$html .= "<td bgcolor=\"lightgrey\"><strong>$d</strong></td>";
									else
										$html .= "<td $shade>$d</td>";
								}
								$html .= "</tr>";
							}

							$html .= "</table>";
						}
					}

					$orientation = $this->options["export"]["orientation"];
					if ($orientation == "landscape")
						$orientation = "L";
					else
						$orientation = "P";

					$paper = $this->options["export"]["paper"];

					// Using opensource TCPdf lib
					// for more options visit http://www.tcpdf.org/examples.php

					require_once('tcpdf/config/lang/eng.php');
					require_once('tcpdf/class.TCPDF.EasyTable.php');

					// create new PDF document
					$pdf = new TCPDF_EasyTable($orientation, PDF_UNIT, $paper, true, 'UTF-8', false);

					// set document information
					$pdf->SetCreator("Easyiso.com");
					$pdf->SetAuthor('Easyiso.com');
					$pdf->SetTitle('Easyiso.com');
					$pdf->SetSubject($this->options["caption"]);
					$pdf->SetKeywords('Easyiso.com');

					// remove default header/footer
					$pdf->setPrintHeader(true);
					$pdf->setPrintFooter(true);

					// set default monospaced font
					$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
					$pdf->setFontSubsetting(false);

					//set margins
					$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

					//set auto page breaks
					$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

					//set image scale factor
					$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

					// set some language dependent data:

					// lines for rtl pdf generation

					if ($this->options["direction"] == "rtl")
					{
						$lg = Array();
						$lg['a_meta_charset'] = 'UTF-8';
						$lg['a_meta_dir'] = 'rtl';
						$lg['a_meta_language'] = 'fa';
						$lg['w_page'] = 'page';
					}			
					$pdf->setLanguageArray($lg);

					// To set your custom font
					// $fontname = $pdf->addTTFfont('/path-to-font/DejaVuSans.ttf', 'TrueTypeUnicode', '', 32);
					// set font http://www.tcexam.org/doc/code/classTCPDF.html#afd56e360c43553830d543323e81bc045
				    # Table parameters
					$pdf->SetFont('helvetica', '', 12);

					// for special charset language, uncoment this line
					// $pdf->SetFont('cid0jp', '', 10);
					// $pdf->SetFont('DejaVuSans', '', 10);

					// add a page
					$pdf->AddPage();

					// output the HTML content
					if ($this->options["export"]["render_type"] == "html")
					{
						$pdf->writeHTML($html, true, false, true, false, '');
						$pdf->Output($this->options["export"]["filename"].".pdf", 'I');
						die;
					}
					
				    $pdf->SetY( 10, true );
					
					$pdf->SetFont('helvetica', '', 15);
					$pdf->SetHeaderCellsFontStyle('b');
					$pdf->Cell( 0, 15, $this->options["export"]["heading"], 0, 1 );

					$pdf->SetFont('helvetica', '', 12);
				    $pdf->SetHeaderCellsFontStyle(''); // 'b'
			        $pdf->SetHeaderCellsFillColor(150,150,150);
			        $pdf->SetHeaderCellsFontColor(0,0,0);
			        $pdf->SetFillColor(240,240,240); // for cells background
			        $pdf->SetCellFontColor(0,0,0);

			        // auto set column widths based on grid column width
					$margins = $pdf->GetMargins();
					$pdf_page_width = $pdf->GetPageWidth() - $margins['left'] - $margins['right'];
			        $total_width = 0;
					
			        foreach ($col_widths as $key => $value) 
			        	$total_width += $value;	

					$new_widths = array();
					
					// adjust equal width column if not defined
					if ($total_width==0)
					{
						$cnt = count($col_widths);
						foreach ($col_widths as $key => $value) 
						{
							$new_widths[$key] = $pdf_page_width / $cnt;	
							$new_widths[] = $pdf_page_width / $cnt;	
						}						
					}
					else
					{
						foreach ($col_widths as $key => $value) 
						{
							$new_widths[$key] = $pdf_page_width * ($value/$total_width);	
							$new_widths[] = $pdf_page_width * ($value/$total_width);	
						}
					}
					
			        $pdf->SetCellWidths($new_widths);
			        $pdf->SetCellFillStyle(2);
			        $pdf->SetCellPadding(1);
			        $pdf->SetCellMinimumHeight(null);

					// if customized pdf render is defined, use that
					if (!empty($this->events["on_render_pdf"]))
					{	
						$func = $this->events["on_render_pdf"][0];
						$obj = $this->events["on_render_pdf"][1];
						if ($obj)
							call_user_func(array($obj,$func),array("pdf" => &$pdf, "data" => &$arr));
						else
							call_user_func($func,array("pdf" => &$pdf, "data" => &$arr));
					}
					
					$h = $arr[0];
				    array_shift($arr);
				    $pdf->EasyTable($arr,$h);

				    if (strstr($this->options["export"]["filename"],".pdf") === false)
				    	$this->options["export"]["filename"] .= ".pdf";
					
					//Close and output PDF document
					$pdf->Output($this->options["export"]["filename"], 'I');
					die;
				}
				else if ($this->options["export"]["format"] == "csv")
				{
					if (strstr($this->options["export"]["filename"],".csv") === false)
						$this->options["export"]["filename"] .= ".csv";
										
		            header( 'Content-Type: text/csv' );
					header( 'Content-Disposition: attachment;filename='.$this->options["export"]["filename"]);		

		            $fp = fopen('php://output', 'w');
		            foreach ($arr as $key => $value) 
		            {
		            	fputcsv($fp, $value);
		            }
		            die;			
				}
				else
				{
					if (!empty($this->events["on_render_excel"]))
					{	
						$func = $this->events["on_render_excel"][0];
						$obj = $this->events["on_render_excel"][1];
						if ($obj)
							$html = call_user_method($func,$obj,array("grid" => $this, "data" => &$arr));
						else
							$html = call_user_func($func,array("grid" => $this, "data" => &$arr));
					}
					
					include_once("php-export-data.class.php");
					$excel = new ExportDataExcel('browser');
					
					if (strstr($this->options["export"]["filename"],".xls") === false &&
						strstr($this->options["export"]["filename"],".xlsx") === false )
						$this->options["export"]["filename"] .= ".xls";
										
					$excel->filename = $this->options["export"]["filename"];
					$excel->initialize();
					foreach($arr as $row) 
					{
						$excel->addRow($row);
					}
					$excel->finalize();
					die;
				}
			}

			// preserve subqueries
			$matches_subsql = $this->remove_subsql();

			// if defined in on_select event
			if (!empty($this->internal["sql_count"]))
			{
				$sql_count = $this->internal["sql_count"]; 
			}
			else if (!empty($this->select_count))
			{
				$sql_count = $this->select_count.$wh;
			}
			else if (($p = stripos($this->select_command,"GROUP BY")) !== false)
			{
				$sql_count = $this->select_command;
				//$p = stripos($sql_count,"GROUP BY");
				//$start_q = substr($sql_count,0,$p);
				//$end_q = substr($sql_count,$p);
				//$sql_count = "SELECT count(*) as c FROM ($start_q $wh $end_q) pg_tmp";
				
				$tmpsql = explode('GROUP BY', $sql_count);
				$sql_count = "SELECT count(*) as c FROM (".implode($wh.' GROUP BY ', $tmpsql).") pg_tmp";
				
			}
			else
			{
				$sql_count = $this->select_command.$wh;
				$sql_count = "SELECT count(*) as c FROM (".$sql_count.") pg_tmp";
			}

			// re-adjust subqueries in sql
			$sql_count = $this->add_subsql($sql_count,$matches_subsql);
			
			# print_r($sql_count);
//die($sql_count);			
			$result = $this->execute_query($sql_count);

			if ($this->con)
			{
				$row = $result->FetchRow();
			}
			else
			{
				$row = mysqli_fetch_array ($result,MYSQLI_ASSOC);
			}

			$count = $row['c'];

			// fix for oracle, alias in capitals
			if (empty($count)) 
				$count = $row['C'];

			if( $count > 0 ) {
				$total_pages = ceil($count/$limit);
			} else {
				$total_pages = 0;
			}

			if ($page > $total_pages) $page=$total_pages;
			$start = $limit*$page - $limit; // do not put $limit*($page - 1)
			if ($start<0) $start = 0;

			$responce = new stdClass();
			$responce->page = $page;
			$responce->total = $total_pages;
			$responce->records = $count;

			if (!empty($this->internal["sql"]))
			{
				$SQL = $this->internal["sql"] . " LIMIT $limit OFFSET $start";
			}
			else if (($p = stripos($this->select_command,"GROUP BY")) !== false)
			{
				//$start_q = substr($this->select_command,0,$p);
				//$end_q = substr($this->select_command,$p);
				//$SQL = "$start_q $wh $end_q ORDER BY $sidx $sord LIMIT $limit OFFSET $start";
				
				$tmpsql = explode('GROUP BY', $this->select_command);
				$SQL = implode("$wh GROUP BY ", $tmpsql)." ORDER BY $sidx $sord LIMIT $limit OFFSET $start";
				
			}
			else
			{
				$SQL = $this->select_command.$wh." ORDER BY $sidx $sord LIMIT $limit OFFSET $start";
			}

			// re-adjust subqueries in sql
			$SQL = $this->add_subsql($SQL,$matches_subsql);

			$SQL = $this->prepare_sql($SQL,$this->db_driver);

			$result = $this->execute_query($SQL);

			if ($this->con)
			{
				$rows = $result->GetRows();
				
				// simulate artificial paging for mssql
				if (count($rows) > $limit)
				{
					// fix for last page
					if (count($rows) == $count)
					{
						$left = count($rows) % $limit;
						
						if ($left==0) $left = $limit;
						
						$rows = array_slice($rows,count($rows) - $left);
					}
					else
						$rows = array_slice($rows,count($rows) - $limit);
				}
			}
			else
			{
				$rows = array();
				while($row = mysqli_fetch_array ($result,MYSQLI_ASSOC))
					$rows[] = $row;
			}

			### P ###
			// update extra data for tree grid
			if ($this->options["treeGrid"] == true)
				$this->add_tree_data($rows);

			### P ###
			// custom on_data_display event execution
			if (!empty($this->events["on_data_display"]))
			{
				$func = $this->events["on_data_display"][0];
				$obj = $this->events["on_data_display"][1];
				
				if ($obj)
					call_user_func(array($obj,$func),array("params" => &$rows));
				else
					call_user_func($func,array("params" => &$rows));
			}
			
			// preserve userdata for response
			if (!empty($rows["userdata"]))
			{
				$userdata = $rows["userdata"];
				unset($rows["userdata"]);
			}

			foreach ($rows as $row)	
			{
				$orig_row = $row;
				// apply php level formatter for image url 30.12.10
				foreach($this->options["colModel"] as $c)
				{
					$col_name = $c["name"];
					
					### P ###
					if (isset($c["default"]) && !isset($row[$col_name]))
						$row[$col_name] = $c["default"];

					// link data in grid to any given url
					if (!empty($c["default"]))
					{
						// replace any param in link e.g. http://domain.com?id={id} given that, there is a $col["name"] = "id" exist
						$row[$col_name] = $this->replace_row_data($orig_row,$c["default"]);
					}
					
					// check conditional data
					if (!empty($c["condition"][0]))
					{
						$r = true;

						// replace {} placeholders from connditional data
						$c["condition"][1] = $this->replace_row_data($orig_row,$c["condition"][1]);
						$c["condition"][2] = $this->replace_row_data($orig_row,$c["condition"][2]);

						eval("\$r = ".$c["condition"][0].";");
						$row[$col_name] = ( $r ? $c["condition"][1] : $c["condition"][2]);
					}	

					// check data filter (alternate of grid on_data_display, but for current column)
					if (!empty($c["on_data_display"]))
					{
						$func = $c["on_data_display"][0];
						$obj = $c["on_data_display"][1];
						$param = $c["on_data_display"][2];
						
						if (!empty($param))
							$params = array($row,$param);
						else
							$params = array($row);

						if ($obj)
							$row[$col_name] = call_user_func_array(array($obj,$func),$params);
						else
							$row[$col_name] = call_user_func_array($func,$params);
					}
					### P-END ###
					
					// datetime formating fix
					if (!empty($row[$c["name"]]) && ($c["formatter"] == "datetime") )
					{
						$dt = $row[$c["name"]];
						$js_dt_fmt = $c["formatoptions"]["newformat"];
						
						$row[$c["name"]] = $this->custom_date_format($js_dt_fmt,$dt);
					}
					
					// link data in grid to any given url
					if (!empty($c["link"]))
					{
						// replace any param in link e.g. http://domain.com?id={id} given that, there is a $col["name"] = "id" exist
						// replace_row_data not used due to urlencode work
						foreach($this->options["colModel"] as $link_c)
						{
							// if there is url in data, don't urlencode
							if (strstr($orig_row[$link_c["name"]],"http://"))
								$link_row_data = $orig_row[$link_c["name"]];
							else
								$link_row_data = urlencode($orig_row[$link_c["name"]]);

							$c["link"] = str_replace("{".$link_c["name"]."}", $link_row_data, $c["link"]);
						}
						
						$attr = "";
						if (!empty($c["linkoptions"]))
							$attr = $c["linkoptions"];
						
						$row[$col_name] = htmlentities($row[$col_name],ENT_QUOTES, "UTF-8");
						
						$row[$col_name] = "<a $attr href='{$c["link"]}'>{$row[$col_name]}</a>";
					}

					// render row data as "src" value of <img> tag
					if (isset($c["formatter"]) && $c["formatter"] == "image")
					{
						$attr = array();
						foreach($c["formatoptions"] as $k=>$v)
							$attr[] = "$k='$v'";
						
						$attr = implode(" ",$attr);
						$row[$col_name] = "<img $attr src='".$row[$col_name] ."'>";
					}
						
					// show masked data in password
					if (isset($c["formatter"]) && $c["formatter"] == "password")
						$row[$col_name] = "*****";		
				}

				foreach($row as $k=>$r)
					$row[$k] = stripslashes($row[$k]);

				$responce->rows[] = $row;
			}
			
			// set custom userdata in footer (controlled with on_data_display event)
			if (!empty($userdata))
				$responce->userdata = $userdata;

			echo json_encode($responce);
			die;
		}		
		
		### P ###
		// if loading from array
		if (is_array($this->table))
		{
			$this->options["data"] = $this->table;
			$this->options["datatype"] = "local";	
			
			if (!isset($this->actions["rowactions"]))
				$this->actions["rowactions"] = false;		
				
			if (!isset($this->actions["add"]))
				$this->actions["add"] = false;		
				
			if (!isset($this->actions["edit"]))
				$this->actions["edit"] = false;
				
			if (!isset($this->actions["delete"]))
				$this->actions["delete"] = false;		
		}

		// few overides - pagination fixes
		$this->options["pager"] = '#'.$grid_id."_pager";
		$this->options["jsonReader"] = array("repeatitems" => false, "id" => "0");

		// allow/disallow edit,del operations
		if ( ($this->actions["edit"] === false && $this->actions["delete"] === false) || $this->options["cellEdit"] === true)
			$this->actions["rowactions"] = false;
			
		if ($this->actions["rowactions"] !== false)
		{
			// CRUD operation column
			$f = false;
			$defined = false;
			foreach($this->options["colModel"] as &$c)
			{
				if ($c["name"] == "act")
				{
					$defined = &$c;
				}
					
				if (!empty($c["width"]))
				{
					$f = true;
				}
			}
			
			// icon col fix, text links as old behavior (fixed:true, mean exact px)
			if ($this->internal["actionicon"] === true)
				$w = ($this->actions["clone"] === true)?"95":"65";
			else
				$w = ($this->actions["clone"] === true)?"120":"100";
			
			// width adjustment for row actions column
			$action_column = array("name"=>"act", "fixed"=>true, "align"=>"center", "index"=>"act", "width"=>"$w", "sortable"=>false, "search"=>false, "viewable"=>false);

			if (!$defined)
			{
				$this->options["colNames"][] = "Actions";
				$this->options["colModel"][] = $action_column;
			}
			else
				$defined = array_merge($action_column,$defined);
		}		

		// simulate field access right options
		$str_add_form = '';
		$str_edit_form = '';
		$str_delete_form = '';
		$str_edit_access = '';
		$str_inline_access = '';

		foreach($this->options["colModel"] as &$c)
		{
			// remove dbname when creating as it expose db structure
			unset($c["dbname"]);
			
			// auto reload & edit for link pattern fix
			if (!empty($c["link"]))
			{
				$this->options["reloadedit"] = true;
				$c["formatter"] = "function(cellvalue, options, rowObject){ 

										arr = jQuery(document).data('link_{$c["name"]}');
										if (!arr) arr = {};
										
										if (jQuery(cellvalue).text() != '')
										{
											arr[jQuery(cellvalue).text()] = cellvalue;
											jQuery(document).data('link_{$c["name"]}',arr);
											return arr[jQuery(cellvalue).text()];
										}
										else
										{
											// fix for link text 'undefined'
											if (typeof(arr[cellvalue]) == 'undefined')
												return '';
											else
											return arr[cellvalue];
										}


									}";
				$c["unformat"] = "function(cellvalue, options, cell){return jQuery(cell).text();}";				
			}

			// make readonly field while editing
			if (isset($c["editrules"]["readonly"]))
			{
				if ($c["editrules"]["readonly"] === true)
				{
					$tag = "input";

					if ( !empty($c["edittype"]) )
						$tag = $c["edittype"];

					if (!empty($c["editrules"]["readonly-when"]))
					{
						$cond = $c["editrules"]["readonly-when"];
						
						if (count($cond) == 2)
						{
							// "readonly-when"=>array("==","male")
							if (!is_numeric($cond[1]))
								$cond[1] = '"'.$cond[1].'"';
							
							$str_edit_access .= 'if (jQuery("#tr_'.$c["index"].' .DataTD '.$tag.'",formid).val() '.$cond[0].' '.$cond[1].')';
							$str_inline_access .= 'if (jQuery("'.$tag.'[name='.$c["index"].']:last").val() '.$cond[0].' '.$cond[1].')';
						}
						elseif (count($cond) == 3)
						{
							// "readonly-when"=>array("client_id","==","4")
							if (!is_numeric($cond[2]))
								$cond[2] = '"'.$cond[2].'"';
							
							$str_edit_access .= 'if (jQuery("input[name='.$cond[0].']:last, select[name='.$cond[0].']:last",formid).val() '.$cond[1].' '.$cond[2].')';
							$str_inline_access .= 'if (jQuery("input[name='.$cond[0].']:last, select[name='.$cond[0].']:last").val() '.$cond[1].' '.$cond[2].')';
						}
						elseif (is_string($cond))
						{
							// "readonly-when"=>"function" - when return true, field will be readonly
							$str_edit_access .= "if ({$cond}(formid))";
							$str_inline_access .= "if ({$cond}())";
						}
					}

					// make textbox hidden, for postback
					$text_val = '';
					$str_edit_access .= '{';
					if ($tag == "select")
						$text_val = 'jQuery(".DataTD '.$tag.'[name='.$c["index"].'] option:selected",formid).text()';
					else
						$text_val = 'jQuery(".DataTD '.$tag.'[name='.$c["index"].']").val()';

					if ($c["formoptions"]["rowpos"])
					{
						$str_edit_access .= 'jQuery("#TblGrid_'.$grid_id.' tr:eq('.($c["formoptions"]["rowpos"]+1).') td:nth-child('.($c["formoptions"]["colpos"]*2).')",formid).append("&nbsp;" + '.$text_val.');';
						$str_edit_access .= 'jQuery("#TblGrid_'.$grid_id.' tr:eq('.($c["formoptions"]["rowpos"]+1).') td:nth-child('.($c["formoptions"]["colpos"]*2).') '.$tag.'",formid).hide();';
					}
					else
					{
						$str_edit_access .= 'jQuery("#TblGrid_'.$grid_id.' #tr_'.$c["index"].' .DataTD",formid).append("&nbsp;" + '.$text_val.');';
						$str_edit_access .= 'jQuery("#tr_'.$c["index"].' .DataTD '.$tag.'",formid).hide();';
					}
				
					
					// remove required (*) from readonly
					$str_edit_access .= 'jQuery("#tr_'.$c["index"].' .DataTD font",formid).hide();';
					$str_edit_access .= '}';
					
					$str_inline_access .= '{';
					$str_inline_access .= 'jQuery("'.$tag.'[name='.$c["index"].']:last").hide();';
					$str_inline_access .= 'jQuery("'.$tag.'[name='.$c["index"].']:last").parent().not(":has(span)").append("<span></span>");';
					$str_inline_access .= 'jQuery("'.$tag.'[name='.$c["index"].']:last").parent().children("span").html(jQuery("'.$tag.'[name='.$c["index"].']:last").val());';
					$str_inline_access .= '}';
				}
			}

			if (!empty($c["show"]))
			{
				if ($c["show"]["list"] === false)
					$c["hidden"] = true;
				else
					$c["hidden"] = false;

				if ($c["formoptions"]["rowpos"])
				{
					$str_pos = '';
					$str_pos .= 'jQuery("#TblGrid_'.$grid_id.' tr:eq('.($c["formoptions"]["rowpos"]+1).') td:nth-child('.($c["formoptions"]["colpos"]*2).')").html("");';				
					$str_pos .= 'jQuery("#TblGrid_'.$grid_id.' tr:eq('.($c["formoptions"]["rowpos"]+1).') td:nth-child('.($c["formoptions"]["colpos"]*2-1).')").html("");';	
				}

				// changed .hide() to .remove() because validation rules apply with .hide()
				if ($c["show"]["edit"] === false)
				{
					$str_edit_access .= 'jQuery("#tr_'.$c["index"].'",formid).remove();';
					if (!empty($str_pos)) $str_edit_access .= $str_pos;
				}
				else
					$str_edit_access .= 'jQuery("#tr_'.$c["index"].'",formid).show();';

				if ($c["show"]["add"] === false)
				{
					$str_add_access .= 'jQuery("#tr_'.$c["index"].'",formid).remove();';
					if (!empty($str_pos)) $str_add_access .= $str_pos;
				}
				else
					$str_add_access .= 'jQuery("#tr_'.$c["index"].'",formid).show();';

				if ($c["show"]["view"] === false)
				{
					$str_view_access .= 'jQuery("#trv_'.$c["index"].'").hide();';
					if ($c["formoptions"]["rowpos"])
					{
						$str_pos = '';
						$str_pos .= 'jQuery("#ViewTbl_'.$grid_id.' tr:eq('.($c["formoptions"]["rowpos"]-1).') td:nth-child('.($c["formoptions"]["colpos"]*2).')").html("");';				
						$str_pos .= 'jQuery("#ViewTbl_'.$grid_id.' tr:eq('.($c["formoptions"]["rowpos"]-1).') td:nth-child('.($c["formoptions"]["colpos"]*2-1).')").html("");';	
						$str_view_access .= $str_pos;
					}
				}
				else
					$str_view_access .= 'jQuery("#trv_'.$c["index"].'").show();';

				unset($c["show"]);
			}
		}

		// set before show form events

		if (!empty($this->internal["add_options"]["beforeShowForm"]))
			$str_add_form = $str_add_access . $this->internal["add_options"]["beforeShowForm"];
		else
			$str_add_form = $str_add_access;

		if (!empty($this->internal["edit_options"]["beforeShowForm"]))
			$str_edit_form = $str_edit_access . $this->internal["edit_options"]["beforeShowForm"];
		else
			$str_edit_form = $str_edit_access;

		if (!empty($this->internal["delete_options"]["beforeShowForm"]))
			$str_delete_form = $str_delete_access . $this->internal["delete_options"]["beforeShowForm"];
		else
			$str_delete_form = $str_delete_access;

		if (!empty($this->internal["view_options"]["beforeShowForm"]))
			$str_view_form = $str_view_access . $this->internal["view_options"]["beforeShowForm"];
		else
			$str_view_form = $str_view_access;

		### P ###
		$fx = "";

		// append add options beforeShowForm implementation
		if ( !empty($this->options["add_options"]["beforeShowForm"]) )
			$fx = "var o=".$this->options["add_options"]["beforeShowForm"]."; o(formid);";
		$this->options["add_options"]["beforeShowForm"] = 'function(formid) { '.$str_add_form.$fx.' }';
		
		// append edit options beforeShowForm implementation
		if ( !empty($this->options["edit_options"]["beforeShowForm"]) )
			$fx = "var o=".$this->options["edit_options"]["beforeShowForm"]."; o(formid);";
		$this->options["edit_options"]["beforeShowForm"] = 'function(formid) { '.$str_edit_form.$fx.' }';
		
		// append del options beforeShowForm implementation
		if ( !empty($this->options["delete_options"]["beforeShowForm"]) )
			$fx = "var o=".$this->options["delete_options"]["beforeShowForm"]."; o(formid);";
		$this->options["delete_options"]["beforeShowForm"] = 'function(formid) { '.$str_delete_form.$fx.' }';
		
		// append view options beforeShowForm implementation
		if ( !empty($this->options["view_options"]["beforeShowForm"]) )
			$fx = "var o=".$this->options["view_options"]["beforeShowForm"]."; o(formid);";
		$this->options["view_options"]["beforeShowForm"] = 'function(formid) { '.$str_view_form. $fx . ' }';

		// focus / select newly inserted row
		if (empty($this->options["add_options"]["afterComplete"]))
		$this->options["add_options"]["afterComplete"] = "function (response, postdata) {
																r = JSON.parse(response.responseText); 
																jQuery( document ).ajaxComplete(function() {	
																	jQuery('#{$grid_id}').setSelection(r.id);
																	jQuery( document ).unbind('ajaxComplete');
																	});
															}";
		// event for dialog < > navigation
		$this->options["view_options"]["afterclickPgButtons"] = 'function(formid) { '.$str_view_access.' }';
		### P-END ###
		

		$reload_after_edit = "";
		// after save callback
		if (!empty($this->options["onAfterSave"]))
			$reload_after_edit .= "var fx_save = {$this->options["onAfterSave"]}; fx_save();";
		if ($this->options["reloadedit"] === true)
			$reload_after_edit .= "jQuery('#$grid_id').jqGrid().trigger('reloadGrid',[{current:true}]);";

		
		### P ###
		if (empty($this->options["add_options"]["success_msg"]))
			$this->options["add_options"]["success_msg"] = "Record added";
		if (empty($this->options["edit_options"]["success_msg"]))
			$this->options["edit_options"]["success_msg"] = "Record updated";
		if (empty($this->options["edit_options"]["success_msg_bulk"]))
			$this->options["edit_options"]["success_msg_bulk"] = "Record(s) updated";
		if (empty($this->options["delete_options"]["success_msg"]))
			$this->options["delete_options"]["success_msg"] = "Record deleted";


		if (empty($this->options["add_options"]["afterSubmit"]))	
		$this->options["add_options"]["afterSubmit"] = 'function(response) { if(response.status == 200)
																				{ 
																					fx_success_msg("'.$this->options["add_options"]["success_msg"].'",1);
																			      	return [true,""];
																				} 
																			}';

		if (empty($this->options["edit_options"]["afterSubmit"]))
		$this->options["edit_options"]["afterSubmit"] = 'function(response) { if(response.status == 200)
																				{ 
																					'.$reload_after_edit.'
																					fx_success_msg("'.$this->options["edit_options"]["success_msg"].'",1);
																			      	return [true,""];
																				} 
																			}';

		if (empty($this->options["delete_options"]["afterSubmit"]))
		$this->options["delete_options"]["afterSubmit"] = 'function(response) { if(response.status == 200)
																				{ 
																					fx_success_msg("'.$this->options["delete_options"]["success_msg"].'",1);
																			      	return [true,""];
																				} 
																			}';
		### P-END ###

		// search options for templates
		$this->options["search_options"]["closeAfterSearch"] = true;
		$this->options["search_options"]["multipleSearch"] = ($this->actions["search"] == "advance")?true:false;
		
		// multiple search group function
		if ($this->actions["search"] == "group")
		{
			$this->options["search_options"]["multipleSearch"] = true;
			$this->options["search_options"]["multipleGroup"] = true;
		}
		
		$this->options["search_options"]["sopt"] = array('eq','ne','lt','le','gt','ge','bw','bn','in','ni','ew','en','cn','nc','nu','nn');
	
		$out = json_encode_jsfunc($this->options);
		$out = substr($out,0,strlen($out)-1);

		// connect bulk edit unrequire // fix for required cols
		if ($this->actions["bulkedit"] === true)
		{
			$out_fx = "";
			// chain 'afterShowForm' function with base working
			if (!empty($this->options["edit_options"]["afterShowForm"]))
			{
				$out_fx = "var fx = ".$this->options["edit_options"]["afterShowForm"]."; fx(f);";
			}

			$this->options["edit_options"]["afterShowForm"] = "function(f){ $out_fx return fx_bulk_unrequire('{$grid_id}'); }";
		}
		
		// create Edit/Delete - Save/Cancel column in grid
		if ($this->actions["rowactions"] !== false)
		{
			$act_links = array();
			
			### P-START ###
			if ($this->internal["actionicon"] === true) // icons as action links
			{
				if ($this->actions["edit"] !== false)
					$act_links[] = "<a class=\"ui-custom-icon ui-icon ui-icon-pencil\" title=\"Edit this row\" href=\"javascript:void(0);\" onclick=\"jQuery(this).dblclick();\"></a>";
				
				### P ###
				if ($this->actions["clone"] === true)
					$act_links[] = "<a class=\"ui-custom-icon ui-icon ui-icon-copy\" title=\"Clone this row\" href=\"javascript:void(0);\" onclick=\"fx_clone_row(\'$grid_id\',\''+cl+'\'); \"></a>";
				### P-END ###
				
				if ($this->actions["delete"] !== false)
					$act_links[] = "<a class=\"ui-custom-icon ui-icon ui-icon-trash\" title=\"Delete this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#$grid_id\').resetSelection(); jQuery(\'#$grid_id\').setSelection(\''+cl+'\'); jQuery(\'#del_$grid_id\').click(); \"></a>";
				
				$act_links = implode("", $act_links);
				
				$extraparam = "{}";
				if (!empty($this->options["edit_options"]["editData"]))
				{
					$extraparam = addslashes(json_encode($this->options["edit_options"]["editData"]));
					$extraparam = str_replace('"',"'",$extraparam);
				}
				
				$out .= ",'gridComplete': function()
							{
								var ids = jQuery('#$grid_id').jqGrid('getDataIDs');
								for(var i=0;i < ids.length;i++)
								{
									var cl = ids[i];
						
									be = '$act_links';
						
									// il_save, ilcancel, iledit are clicked for inlineNav button reset
									se = '<a class=\"ui-custom-icon ui-icon ui-icon-disk\" title=\"Save this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#{$grid_id}_ilsave\').click(); if (jQuery(\'#$grid_id\').saveRow(\''+cl+'\',null,null,{$extraparam}) || jQuery(\'.editable\').length==0) { jQuery(this).parent().hide(); jQuery(this).parent().prev().show(); ". addslashes($reload_after_edit)." }\"></a>';
									ce = '<a class=\"ui-custom-icon ui-icon ui-icon-cancel\" title=\"Restore this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#{$grid_id}_ilcancel\').click(); jQuery(\'#$grid_id\').restoreRow(\''+cl+'\'); jQuery(this).parent().hide(); jQuery(this).parent().prev().show();\"></a>';
						
									// for inline add option
									if (ids[i].indexOf('jqg') != -1)
									{
										se = '<a class=\"ui-custom-icon ui-icon ui-icon-disk\" title=\"Save this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#{$grid_id}_ilsave\').click(); \">Save</a>';
										ce = '<a class=\"ui-custom-icon ui-icon ui-icon-cancel\" title=\"Restore this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#{$grid_id}_ilcancel\').click(); jQuery(this).parent().hide(); jQuery(this).parent().prev().show();\">Cancel</a>';
										jQuery('#$grid_id').jqGrid('setRowData',ids[i],{act:'<span style=display:none id=\"edit_row_{$grid_id}_'+cl+'\">'+be+'</span>'+'<span id=\"save_row_{$grid_id}_'+cl+'\">'+se+ce+'</span>'});
									}
									else
										jQuery('#$grid_id').jqGrid('setRowData',ids[i],{act:'<span id=\"edit_row_{$grid_id}_'+cl+'\">'+be+'</span>'+'<span style=display:none id=\"save_row_{$grid_id}_'+cl+'\">'+se+ce+'</span>'});
								}
							}";						
			}
			else // text based action links
			{
				if ($this->actions["edit"] !== false)
					$act_links[] = "<a title=\"Edit this row\" href=\"javascript:void(0);\" onclick=\"jQuery(this).dblclick();\">Edit</a>";

				### P ###
				if ($this->actions["clone"] === true)
					$act_links[] = "<a title=\"Clone this row\" href=\"javascript:void(0);\" onclick=\"fx_clone_row(\'$grid_id\',\''+cl+'\'); \">Clone</a>";
					### P-END ###

				if ($this->actions["delete"] !== false)
					$act_links[] = "<a title=\"Delete this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#$grid_id\').resetSelection(); jQuery(\'#$grid_id\').setSelection(\''+cl+'\'); jQuery(\'#del_$grid_id\').click(); \">Delete</a>";

				$act_links = implode(" | ", $act_links);

				$out .= ",'gridComplete': function()
							{
								var ids = jQuery('#$grid_id').jqGrid('getDataIDs');
								for(var i=0;i < ids.length;i++)
								{
									var cl = ids[i];
									
									be = '$act_links'; 
									
									// il_save, ilcancel, iledit are clicked for inlineNav button reset
									se = ' <a title=\"Save this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#{$grid_id}_ilsave\').click(); if (jQuery(\'#$grid_id\').saveRow(\''+cl+'\') || jQuery(\'.editable\').length==0) { jQuery(this).parent().hide(); jQuery(this).parent().prev().show(); ". addslashes($reload_after_edit)." }\">Save</a>'; 
									ce = ' | <a title=\"Restore this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#{$grid_id}_ilcancel\').click(); jQuery(\'#$grid_id\').restoreRow(\''+cl+'\'); jQuery(this).parent().hide(); jQuery(this).parent().prev().show();\">Cancel</a>'; 
									
									// for inline add option							
									if (ids[i].indexOf('jqg') != -1)
									{
										se = ' <a title=\"Save this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#{$grid_id}_ilsave\').click(); \">Save</a>'; 
										ce = ' | <a title=\"Restore this row\" href=\"javascript:void(0);\" onclick=\"jQuery(\'#{$grid_id}_ilcancel\').click(); jQuery(this).parent().hide(); jQuery(this).parent().prev().show();\">Cancel</a>'; 
										jQuery('#$grid_id').jqGrid('setRowData',ids[i],{act:'<span style=display:none id=\"edit_row_{$grid_id}_'+cl+'\">'+be+'</span>'+'<span id=\"save_row_{$grid_id}_'+cl+'\">'+se+ce+'</span>'});
									}
									else
										jQuery('#$grid_id').jqGrid('setRowData',ids[i],{act:'<span id=\"edit_row_{$grid_id}_'+cl+'\">'+be+'</span>'+'<span style=display:none id=\"save_row_{$grid_id}_'+cl+'\">'+se+ce+'</span>'});
								}	
							}";
			}		
		}					
		
		$out .= ",'ondblClickRow': function (id, iRow, iCol, e) {";
		
		// double click editing option
		if ($this->actions["rowactions"] !== false && $this->actions["edit"] !== false && $this->options["cellEdit"] !== true)
		{
			$is_inline = "false";
			if ($this->actions["inline"] || $this->actions["inlineadd"])
				$is_inline = "true";
				
			$out .= "
					if (!e) e = window.event;
					var element = e.target || e.srcElement;
					var is_inline = {$is_inline};
					
					// only make sub/parent grid inline editable - previously both become editable if same rowid
					if (jQuery(element).closest('table').attr('id') != '{$grid_id}')
						return;

					// if no editable row, reset lastSel
					if(jQuery('.editable').length==0) lastSel = null;

					// if row already dblclicked, ignore
					if (id==lastSel)
						return;
					
					// for inlineNav mode fix
					if (is_inline)
					{
						// if dblclicked and then single clicked (unselect row) then dblclick other, cancel last editing
						if(id!==lastSel && lastSel != undefined)
							jQuery('#{$grid_id}_ilcancel').click();

						jQuery('#{$grid_id}').resetSelection();
					}
					
					if(id && id!==lastSel && id.indexOf('jqg') != 0)
					{ 
						// reset data msg, for new row edit without save last row
						if (typeof(lastSel) != 'undefined' && jQuery('#{$grid_id} > tbody > tr > td > .editable').length >0)
							if(!confirm('Do you want to reset changes?'))
								return;
							
						jQuery('#{$grid_id}').restoreRow(lastSel); 
						
						// to enable autosave on dblclick new row + dont edit on validation error
						// if (typeof(lastSel) != 'undefined')
							// if (!jQuery('#$grid_id').saveRow(lastSel))
								// return;
						
						// disabled previously edit icons
						jQuery('#edit_row_{$grid_id}_'+lastSel).show();
						jQuery('#save_row_{$grid_id}_'+lastSel).hide();								
						
						// highlight last off - row on multiselect dblclick
						if (!is_inline) jQuery('#{$grid_id}').setSelection(lastSel);
						lastSel=id; 								
					}
					
					// highlight - row on multiselect dblclick
					if (!is_inline) jQuery('#{$grid_id}').setSelection(id);
					
					// preserve horizontal scroll position
					var sl = jQuery('div.ui-jqgrid-bdiv').scrollLeft();
					jQuery('#$grid_id').editRow(id, true, function()
															{
																// focus on dblclicked element
																setTimeout(function(){ jQuery('input, select, textarea', element).focus(); },100);
																setTimeout(function(){ jQuery('div.ui-jqgrid-bdiv').scrollLeft(sl); },100);
															}, 
															function()
															{
																jQuery('#edit_row_{$grid_id}_'+id).show();
																jQuery('#save_row_{$grid_id}_'+id).hide();

																return true;
															},null,{},
															function()
															{
																// force reload grid after inline save
																$reload_after_edit
															},null,
															function()
															{
																jQuery('#edit_row_{$grid_id}_'+id).show();
																jQuery('#save_row_{$grid_id}_'+id).hide();

																return true;
															}
												); 
					
					// for inlineNav edit button fix
					if (is_inline)
					{
						jQuery('#{$grid_id}').setSelection(id, true);
						jQuery('#{$grid_id}_iledit').click();
					}
					
					// hide edit and show save
					jQuery('#edit_row_{$grid_id}_'+id).hide();
					jQuery('#save_row_{$grid_id}_'+id).show();
						
					// frozen columns height adjustments on edit
					jQuery('.frozen-bdiv tr.jqgrow').each(function () {
						var h = jQuery('#'+jQuery.jgrid.jqID(this.id)).height();
						jQuery(this).height(h);
					});
			
					$str_inline_access";
		}

		// chain 'ondblClickRow' function with base working
		if (!empty($this->options["ondblClickRow"]))
		{
			$out .= "var fx = ".$this->options["ondblClickRow"]."; fx(id, iRow, iCol);";
			unset($this->options["ondblClickRow"]);
		}
			
		$out .= "}";
		
		### P ###
		// if subgrid is there, enable subgrid feature
		if (isset($this->options["subgridurl"]) && $this->options["subgridurl"] != '') 
		{
			// we pass two parameters
			// subgrid_id is a id of the div tag created within a table
			// the row_id is the id of the row
			// If we want to pass additional parameters to the url we can use
			// the method getRowData(row_id) - which returns associative array in type name-value
			// here we can easy construct the following
					
			$pass_params = "false";
			if (!empty($this->options["subgridparams"]))
				$pass_params = "true";
				
			// chain 'subGridRowExpanded' function with base working
			if (!empty($this->options["subGridRowExpanded"]))
			{
				$str_fx = "var fx = ".$this->options["subGridRowExpanded"]."; fx();";
				unset($this->options["subGridRowExpanded"]);
			}
			
			$out .= ",'subGridRowExpanded': function(subgridid, id) 
											{ 
												var data = {subgrid:subgridid, rowid:id};
												
												if('$pass_params' == 'true') {
													var anm= '".$this->options["subgridparams"]."';
													anm = anm.split(',');
													var rd = jQuery('#".$grid_id."').jqGrid('getRowData', id);
													if(rd) {
														for(var i=0; i<anm.length; i++) {
															anm[i] = anm[i].trim();
															if(rd[anm[i]]) {
																data[anm[i]] = rd[anm[i]];
															}
														}
													}
												}
												jQuery('#'+jQuery.jgrid.jqID(subgridid)).load('".$this->options["subgridurl"]."',data,function(){ ".$str_fx." });
											}";				
		}

		// on error
		$out .= ",'loadError': function(xhr,status, err) { 
					try 
					{
						jQuery.jgrid.info_dialog(jQuery.jgrid.errors.errcap,'<div class=\"ui-state-error\">'+ xhr.responseText +'</div>', 
													jQuery.jgrid.edit.bClose,{buttonalign:'right'});
													
						jQuery('#info_dialog').abscenter();
					} 
					catch(e) { alert(xhr.responseText);}
				}
				";

		// on row selection operation
		$out .= ",'onSelectRow': function(ids) { ";

				### P ###
				if (isset($this->options["detail_grid_id"]) && $this->options["detail_grid_id"] != '') 
				{
					$detail_grid_id	= $this->options["detail_grid_id"];
					$d_grids = explode(",", $detail_grid_id);

					foreach($d_grids as $detail_grid_id) 
					{
						$detail_url = $this->options["url"];

						// remove master grid's grid_id param
						$detail_url = str_replace('grid_id=','',$detail_url);

						// append grid_id param for detail grid
						$s = (strstr($this->options["url"], "?")) ? "&":"?";
						$detail_url .= $s."grid_id=". $detail_grid_id;

						$out .= "
		
						var data = '';
						if ('{$this->options["subgridparams"]}'.length > 0)
						{
							var anm = '".$this->options["subgridparams"]."';
							anm = anm.split(',');
							var rd = jQuery('#".$grid_id."').jqGrid('getRowData', ids);
							if(rd) {
								for(var i=0; i<anm.length; i++) {
									anm[i] = anm[i].trim();
									if(rd[anm[i]]) {
										data += '&' + anm[i] + '=' + rd[anm[i]];
									}
								}
							}
						}	
							
						if(ids == null) 
						{
							ids=0;
							if(jQuery('#".$detail_grid_id."').jqGrid('getGridParam','records') >0 )
							{
								jQuery('#".$detail_grid_id."').jqGrid('setGridParam',{datatype:'json',url:'".$detail_url."&rowid='+ids+data,editurl:'".$detail_url."&rowid='+ids+data,cellurl:'".$detail_url."&rowid='+ids+data,jqgrid_page:1});
								jQuery('#".$detail_grid_id."').trigger('reloadGrid',[{jqgrid_page:1}]);
							}
						} 
						else 
						{
							jQuery('#".$detail_grid_id."').jqGrid('setGridParam',{datatype:'json',url:'".$detail_url."&rowid='+ids+data,editurl:'".$detail_url."&rowid='+ids+data,cellurl:'".$detail_url."&rowid='+ids+data,jqgrid_page:1});
							jQuery('#".$detail_grid_id."').trigger('reloadGrid',[{jqgrid_page:1}]);			
						}

						// enable detail grid buttons if master row selected
						jQuery('#".$detail_grid_id."_pager_left .ui-pg-button').not(':has(span.ui-separator)').removeClass('ui-state-disabled');
						jQuery('#".$detail_grid_id."_ilsave, #".$detail_grid_id."_ilcancel').addClass('ui-state-disabled');
						
						jQuery('#".$detail_grid_id."').data('jqgrid_detail_grid_params','&rowid='+ids+data);
						";
					}
				};

				### P ###
				// obseleted now
				if (!empty($this->events["js_on_select_row"])) 
				{
					$out .= "if (typeof({$this->events["js_on_select_row"]}) != 'undefined') {$this->events["js_on_select_row"]}(ids);";
				}	

				// chain 'onSelectRow' function with base working
				if (!empty($this->options["onSelectRow"]))
				{
					$out .= "var fx = ".$this->options["onSelectRow"]."; fx(ids);";
					unset($this->options["onSelectRow"]);
				}
			
		// closing of select row events
		$out .= "}";
		
		// fix for formatting, to apply on only new records of virtual scroll
		if($this->options["scroll"] == true)
		{		
			$out .= ",'beforeRequest': function() {";
				$out .= "jQuery('#$grid_id').data('jqgrid_rows',jQuery('#$grid_id tr.jqgrow').length);";
			$out .= "}";
		}

		// on load complete operation
		$out .= ",'loadComplete': function(ids) {";

				// In case 'All' param is used in pager
				$out .= "jQuery('#{$grid_id}_pager option[value=\"All\"]').val(99999);";
				$out .= "jQuery('#{$grid_id}_toppager option[value=\"All\"]').val(99999);";
				
				// select 'All' in pager by default
				if ($this->options["rowNum"] == "99999")
				{
					$out .= "jQuery('#{$grid_id}_pager select.ui-pg-selbox').val(99999);";
					$out .= "jQuery('#{$grid_id}_toppager select.ui-pg-selbox').val(99999);";
				}
				
				// show no record message at center
				$out .= "if (jQuery('#{$grid_id}').getGridParam('records') == 0) 
						{ 
							if (jQuery('#div_no_record_{$grid_id}').length==0) 
								jQuery('#gbox_{$grid_id} .ui-jqgrid-bdiv').not('.frozen-bdiv').append('<div id=\"div_no_record_{$grid_id}\" align=\"center\" style=\"padding:30px 0;\">'+jQuery.jgrid.defaults.emptyrecords+'</div>'); 
							else 
								jQuery('#div_no_record_{$grid_id}').show();
						}
						else
						{
							jQuery('#div_no_record_{$grid_id}').hide();
						}";

				if (isset($this->options["detail_grid_id"]) && $this->options["detail_grid_id"] != '') 
				{
					$detail_grid_id	= $this->options["detail_grid_id"];
					$d_grids = explode(",", $detail_grid_id);

					foreach($d_grids as $detail_grid_id) 
					{
						$detail_url = $this->options["url"];
						$s = (strstr($this->options["url"], "?")) ? "&":"?";
						$detail_url .= $s."grid_id=". $detail_grid_id;

						$out .= "
								jQuery('#".$detail_grid_id."').jqGrid('setGridParam',{url:'".$detail_url."&rowid=',editurl:'".$detail_url."&rowid=',jqgrid_page:1});
								jQuery('#".$detail_grid_id."').trigger('reloadGrid',[{jqgrid_page:1}]);
								jQuery('#".$detail_grid_id."').data('jqgrid_detail_grid_params','');
								jQuery('#".$detail_grid_id."_pager_left .ui-pg-button').addClass('ui-state-disabled');
								";
					}

				}		

				// formatting fix for virtual scrolling
				$fix_format = "";
				if($this->options["scroll"] == true)
				{
					$fix_format .= "var last_rows = 0;
									if (typeof(jQuery('#$grid_id').data('jqgrid_rows')) != 'undefined')
										i = i + jQuery('#$grid_id').data('jqgrid_rows');
									";
				}
				
				
				// celledit option and readonly mode
				if ($this->options["cellEdit"] === true)
				{
					foreach($this->options["colModel"] as $t)
						if ($t["editrules"]["readonly"] == true)
							$fix_format .= "jQuery('#{$grid_id} tr.jqgrow:eq('+i+') td[aria-describedby={$grid_id}_{$t[name]}]').addClass('not-editable-cell');";
				}				

				$out .= "if(ids && ids.rows) jQuery.each(ids.rows,function(i){
							$fix_format
							";
					
						### P ###
						if (is_array($this->conditional_css) && count($this->conditional_css))
						{
							foreach ($this->conditional_css as $value) 
							{
								// if wrong column specified, skip formatting
								$out .= "if (typeof(this.{$value['column']}) == 'undefined') return;";

								// using {column} placeholder in formatting value
								preg_match('/{(.*)}/', $value['value'], $match);
								if (count($match))
								{
									// if html remove it using text(), if string convert toString(), if numeric use parseFloat
									if ($value["op"] == "cn" || $value["op"] == "eq" || $value["op"] == "=")
										$value['value'] = "'+ ( /(<([^>]+)>)/ig.test(this.$match[1]) ? jQuery(this.$match[1]).text() : (jQuery.isNumeric(this.$match[1]) ? parseFloat(this.$match[1]) : this.$match[1].toString()) )+ '";
									else
										$value['value'] = "( /(<([^>]+)>)/ig.test(this.$match[1]) ? jQuery(this.$match[1]).text() : (jQuery.isNumeric(this.$match[1]) ? parseFloat(this.$match[1]) : this.$match[1].toString()) )";
								}

								// filter extras if not numeric
								$out .= "if (!jQuery.isNumeric(this.{$value['column']})) 
								this.{$value['column']} = this.{$value['column']}.replace(/(<([^>]+)>)/ig,'');
								";							
								if ($value["op"] == "cn")
								{
									$out .= "
										if (this.{$value['column']}.toString().toLowerCase().indexOf('{$value['value']}'.toString().toLowerCase()) != -1)
										{
											if ('".$value["class"]."' != '')
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').addClass('".$value["class"]."');
											else if (\"".$value["css"]."\" != '')
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit').css({{$value[css]}});
											else if ('".$value["cellclass"]."' != '')
											{ 
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit'); 
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+') td[aria-describedby={$grid_id}_{$value['column']}]').addClass('".$value["cellclass"]."');
											}
											else if (\"".$value["cellcss"]."\" != '')
											{
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit');
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+') td[aria-describedby={$grid_id}_{$value['column']}]').css('background','inherit').css({{$value[cellcss]}});
											}
										}";
								}
								else if ($value["op"] == "eq" || $value["op"] == "=" || $value["op"] == "==")
								{
									$out .= "
										if (this.{$value['column']}.toString().toLowerCase() == '{$value['value']}'.toString().toLowerCase())
										{
											if ('".$value["class"]."' != '')
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').addClass('".$value["class"]."');
											else if (\"".$value["css"]."\" != '')
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit').css({{$value['css']}});
											else if ('".$value["cellclass"]."' != '')
											{
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit');
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+') td[aria-describedby={$grid_id}_{$value['column']}]').addClass('".$value["cellclass"]."');
											}
											else if (\"".$value["cellcss"]."\" != '')
											{
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit');
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+') td[aria-describedby={$grid_id}_{$value['column']}]').css('background','inherit').css({{$value['cellcss']}});
											}
										}";
								}
								else if ($value["op"] == "<" || $value["op"] == "<=" || $value["op"] == ">" || $value["op"] == ">=" || $value["op"] == "!=")
								{
									// if numeric, do parseFloat
									$out .= "
										if (jQuery.isNumeric(this.{$value['column']}))
											this.{$value['column']} = parseFloat(this.{$value['column']});
											
										if (this.{$value['column']} {$value["op"]} {$value['value']})
										{
											if ('".$value["class"]."' != '')
											{
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').addClass('".$value["class"]."');
											}
											else if (\"".$value["css"]."\" != '')
											{
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit').css({{$value['css']}});
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+') a').css('background-image','inherit').css({{$value['css']}});
											}
											else if ('".$value["cellclass"]."' != '')
											{
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit');
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+') td[aria-describedby={$grid_id}_{$value['column']}]').addClass('".$value["cellclass"]."');
											}
											else if (\"".$value["cellcss"]."\" != '')
											{
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+')').css('background-image','inherit');
												jQuery('#$grid_id,#{$grid_id}_frozen').find('tr.jqgrow:eq('+i+') td[aria-describedby={$grid_id}_{$value['column']}]').css('background','inherit').css({{$value['cellcss']}});
											}
										}";
								}
								// column formatting
								else if (empty($value["op"]) && !empty($value["column"]) && !empty($value["css"]))
								{
									$out .= "
										{
											if (jQuery.browser.msie)
												jQuery('#$grid_id,#{$grid_id}_frozen').find('td[aria-describedby={$grid_id}_{$value["column"]}]').css('background','inherit').css({{$value['css']}});
											else
												jQuery('#$grid_id,#{$grid_id}_frozen').find('td[aria-describedby={$grid_id}_{$value["column"]}]').removeClass('.ui-widget-content').css({{$value['css']}});
										}";							
								}
							}
						}
						### P-END ###

					// frozen columns height adjustments on edit
					$out .= "jQuery('.frozen-bdiv tr.jqgrow').each(function () {
						var h = jQuery('#'+jQuery.jgrid.jqID(this.id)).height();
						if (jQuery.browser.chrome)
							h+=2;
							
						jQuery(this).height(h);
					});";
						
			$out .= "});";

			### P ###
			
			// obseleted now
			if (!empty($this->events["js_on_load_complete"])) 
			{
				$out .= "if (typeof({$this->events["js_on_load_complete"]}) != 'undefined') {$this->events["js_on_load_complete"]}(ids);";
			}

			// chain 'loadComplete' function with base working
			if (!empty($this->options["loadComplete"]))
			{
				$out .= "var fx = ".$this->options["loadComplete"]."; fx(ids);";
				unset($this->options["loadComplete"]);
			}
			
		// closing of load complete events
		$out .= "}";

		// closing of param list
		$out .= "}";

		// Navigational grid params
		if (!isset($this->navgrid["param"]))
		{
			// remove edit dialog for celledit (excelview) - was conflicting
			if ($this->options["cellEdit"] === true)
				$this->actions["edit"] = false;
				
			$this->navgrid["param"]["edit"] = ($this->actions["edit"] === false) ? false:true;
			$this->navgrid["param"]["add"] = ($this->actions["add"] === false) ? false:true;
			$this->navgrid["param"]["del"] = ($this->actions["delete"] === false) ? false:true;
			$this->navgrid["param"]["view"] = ($this->actions["view"] === false) ? false:true;
			$this->navgrid["param"]["refresh"] = ($this->actions["refresh"] === false) ? false:true;
			### P ### -- turn false
			$this->navgrid["param"]["search"] = ($this->actions["search"] === false) ? false : true;

			// fix for del and delete text
			if (!empty($this->navgrid["param"]["delete"]))
				$this->navgrid["param"]["del"] = $this->navgrid["param"]["delete"];
		}

		// Generate HTML/JS code
		ob_start();
		?>
			<table id="<?php echo $grid_id?>"></table> 
			<div id="<?php echo $grid_id."_pager"?>"></div> 
			<script>
			var phpgrid = $("#<?php echo $grid_id?>");
			var phpgrid_pager = $("#<?php echo $grid_id."_pager"?>");
			var fx_ajax_file_upload;
			var fx_replace_upload;
			var fx_bulk_update;
			var fx_get_dropdown;
			var fx_reload_dropdown;

			$(document).ready(function(){
				<?php echo $this->render_js($grid_id,$out);?>
			});	

			</script>	
		<?php
		return ob_get_clean();
	}
	
	/**
	 * JS code related to grid rendering
	 */
	function render_js($grid_id,$out)
	{
	?>
		var lastSel;
		fx_clone_row = function (grid,id) 
		{
			myData = {};
			myData.id = id;
			myData.grid_id = grid;
			myData.oper = 'clone';
			jQuery.ajax({
				url: jQuery("#"+grid).jqGrid('getGridParam', 'url'),
				dataType: "html",
				data: myData,
				type: "POST",
				error: function(res, status) {
					alert(res.status+" : "+res.statusText+". Status: "+status);
				},
				success: function( data ) {
				}
			});
			jQuery("#"+grid).trigger('reloadGrid',[{jqgrid_page:1}]);
		};

		var extra_opts = {};
		
		<?php ### P ### ?>
		if (typeof(opts) != 'undefined') extra_opts = opts;
		if (typeof(opts_<?php echo $grid_id?>) != 'undefined') extra_opts = opts_<?php echo $grid_id?>;

		// if bootstrap, increase subgrid icon width
		if (jQuery("link[href*='ui.bootstrap']").length)
			extra_opts["subGridWidth"] = "33px";
		
		var grid_<?php echo $grid_id?> = jQuery("#<?php echo $grid_id?>").jqGrid( jQuery.extend(<?php echo $out?>, extra_opts ) );
		
		jQuery("#<?php echo $grid_id?>").jqGrid('navGrid','#<?php echo $grid_id."_pager"?>',
				<?php echo json_encode_jsfunc($this->navgrid["param"])?>,
				<?php echo json_encode_jsfunc($this->options["edit_options"])?>,
				<?php echo json_encode_jsfunc($this->options["add_options"])?>,
				<?php echo json_encode_jsfunc($this->options["delete_options"])?>,
				<?php echo json_encode_jsfunc($this->options["search_options"])?>,
				<?php echo json_encode_jsfunc($this->options["view_options"])?>
				);	
		
		// Set grouping header using callGridMethod
		<?php if (!empty($this->group_header)) { ?>
		jQuery("#<?php echo $grid_id?>").jqGrid("setGroupHeaders", <?php echo json_encode_jsfunc($this->group_header)?>);	
		<?php } ?>

		<?php ### P ### ?>		
		<?php if ($this->actions["inlineadd"] !== false || $this->actions["inline"] === true) { ?>
		jQuery('#<?php echo $grid_id?>').jqGrid('inlineNav','#<?php echo $grid_id."_pager"?>',{"add":true,"edit":true,"save":true,"cancel":true,
		"addParams":{
						"addRowParams":
						{
							keys: true,
							"oneditfunc": function(id)
							{
								jQuery("#edit_row_<?php echo $grid_id?>_"+id+" a:first").parent().hide().next().show();
								// jQuery("div.frozen-div, div.frozen-bdiv").hide();
							},
							"afterrestorefunc": function(id)
							{
								jQuery("#save_row_<?php echo $grid_id?>_"+id+" a:last").parent().hide().prev().show();
								// jQuery(".frozen-div, .frozen-bdiv").show();
							},
							"aftersavefunc":function (id, res)
							{
								// set new id for row (in case of load session row id 'id')
								// http://stackoverflow.com/questions/15345391/displaying-jqg1-instead-of-id-returned-from-database-on-inline-add
								
								var result = jQuery.parseJSON(res.responseText);
								$(this).jqGrid("setCell", id, "id", result.id);
								
								// but reload grid, to work properly
								jQuery('#<?php echo $grid_id?>').trigger("reloadGrid",[{jqgrid_page:1}]);
							},
							"errorfunc": function(id,res)
							{
								jQuery.jgrid.info_dialog(jQuery.jgrid.errors.errcap,'<div class=\"ui-state-error\">'+ res.responseText +'</div>', 
															jQuery.jgrid.edit.bClose,{buttonalign:'right'});
								jQuery('#<?php echo $grid_id?>').trigger("reloadGrid",[{jqgrid_page:1}]);
							}
						}
		}
		,"editParams":{	
							keys: true,	
							"aftersavefunc":function (id, res)
							{
								// jQuery(".frozen-div, .frozen-bdiv").show();
								// but reload grid, to work properly			
								jQuery('#<?php echo $grid_id?>').trigger("reloadGrid",[{jqgrid_page:1}]);
							},
							"errorfunc": function(id,res)
							{
								jQuery.jgrid.info_dialog(jQuery.jgrid.errors.errcap,'<div class=\"ui-state-error\">'+ res.responseText +'</div>', 
															jQuery.jgrid.edit.bClose,{buttonalign:'right'});
								jQuery('#<?php echo $grid_id?>').trigger("reloadGrid",[{jqgrid_page:1}]);
							},
							"oneditfunc": function(id)
							{
								jQuery("#edit_row_<?php echo $grid_id?>_"+id+" a:first").parent().hide().next().show();
								// jQuery("div.frozen-div, div.frozen-bdiv").hide();
							},
							"afterrestorefunc": function(id)
							{
								jQuery("#save_row_<?php echo $grid_id?>_"+id+" a:last").parent().hide().prev().show();
								// jQuery(".frozen-div, .frozen-bdiv").show();
							}
		}});
		<?php } ?>
			
		<?php if ($this->actions["autofilter"] !== false) { ?>
		// auto filter with contains search
		jQuery("#<?php echo $grid_id?>").jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false, defaultSearch:'cn'}); 
		// jQuery("#<?php echo $grid_id?>").jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false, searchOperators : true}); 
		<?php } ?>

		<?php if ($this->actions["showhidecolumns"] !== false) { ?>
		// show/hide columns
		jQuery("#<?php echo $grid_id?>").jqGrid('navButtonAdd',"#<?php echo $grid_id."_pager"?>",{caption:"Columns",title:"Hide/Show Columns", buttonicon :'ui-icon-note',
			onClickButton:function(){
				jQuery("#<?php echo $grid_id?>").jqGrid('columnChooser',{width : 250, height:150, modal:true, done:function(){ c = jQuery('#colchooser_<?php echo $grid_id?> select').val(); var colModel = jQuery("#<?php echo $grid_id?>").jqGrid("getGridParam", "colModel"); str = ''; jQuery(c).each(function(i){ str += colModel[c[i]]['name'] + ","; }); document.cookie = 'jqgrid_colchooser=' + str; }, "dialog_opts" : {"minWidth": 270} });
				jQuery("#colchooser_<?php echo $grid_id?>").parent().position({
				   	my: "top",
			   		at: "top",
					of: $("#gbox_<?php echo $grid_id?>")
				});				
	      
			} 
		});
		<?php } ?>

		<?php ### P ### ?>
		<?php if ($this->actions["bulkedit"] === true) { ?>
		jQuery("#<?php echo $grid_id?>").jqGrid('navButtonAdd',"#<?php echo $grid_id."_pager"?>",{
				'caption'      : 'Bulk Edit', 
				'buttonicon'   : 'ui-icon-pencil', 
				'onClickButton': function()
				{
					var ids = jQuery('#<?php echo $grid_id?>').jqGrid('getGridParam','selarrrow');
							
					// don't process if nothing is selected
					if (ids.length == 0)
					{
						jQuery.jgrid.info_dialog(jQuery.jgrid.errors.errcap,'<div class=\"ui-state-error\">'+jQuery.jgrid.nav.alerttext+'</div>', 
													jQuery.jgrid.edit.bClose,{buttonalign:'right'});
						return;
					}
					// added dummy value to blank dialog fields
					else if (ids.length == 1)
					{
						ids = [ids,-99];
					}
					
					// save to identify bulk edit dialog
					jQuery('#<?php echo $grid_id?>').data('bulk-edit-count',ids.length);
					
					jQuery('#<?php echo $grid_id?>').jqGrid('editGridRow', ids, <?php echo json_encode_jsfunc($this->options["edit_options"])?>);
					jQuery('#edithd<?php echo $grid_id?> .ui-jqdialog-title').html("Bulk Edit");
					jQuery('#editmod<?php echo $grid_id?> .binfo').show();
					jQuery('#editmod<?php echo $grid_id?> .bottominfo').html("Note: Blank fields will be skipped");
					
					// insert empty option
					jQuery('#editmod<?php echo $grid_id?> select').not('.checkbox').prepend("<option value='-'>- Empty -</option>");
					
					// dont append blank option if already exist
					if ( jQuery('#editmod<?php echo $grid_id?> select > option:first').val() != '' )
						jQuery('#editmod<?php echo $grid_id?> select').prepend("<option value=''></option>");
					
					// set dropdown blank
					jQuery('#editmod<?php echo $grid_id?> select').val('');
					
					return true;
				},
				'position': 'last'
		});
		
		// function to un-require required field in bulkedit
		function fx_bulk_unrequire(list)
		{
			var grid = jQuery("#"+list);
			
			var cnames = grid.jqGrid('getGridParam','colModel');
			var req_fields = [];
			var checkbox_fields = [];
			var is_bulkedit = false;
			
			for (var a=0;a < cnames.length;a++)
			{
				var p = grid.jqGrid('getColProp',cnames[a].name);
				
				if (p.editrules && p.editrules.required)
					req_fields[req_fields.length] = cnames[a].name;

				if (p.edittype && p.edittype.toLowerCase() == 'checkbox')
					checkbox_fields[checkbox_fields.length] = cnames[a].name;
			}
			
			// if bulk edit
			var count = grid.data('bulk-edit-count');
			grid.data('bulk-edit-count',0);
			
			if(count > 1)
				is_bulkedit = true;

			for (var a=0;a < req_fields.length;a++)
			{
				grid.jqGrid('setColProp',req_fields[a],{editrules:{'required':!is_bulkedit}});
				
				// remove required *
				jQuery("#tr_"+req_fields[a]+" TD.DataTD font").remove();
			}

			// disable checkbox in bulk edit
			for (var a=0;is_bulkedit && a < checkbox_fields.length;a++)
			{
				var vals = [];
				var p = grid.jqGrid('getColProp',checkbox_fields[a]);
				if (p.editoptions.value)
					vals = p.editoptions.value.split(":");
					
				// relace with dropdown
				str = '<select class="FormElement ui-widget-content ui-corner-all checkbox" onchange="$(\'#'+checkbox_fields[a]+'\').val(this.value);" name="'+checkbox_fields[a]+'"><option value="'+vals[0]+'">Checked</option><option value="'+vals[1]+'">Un-Checked</option> </select>';
				jQuery("#tr_"+checkbox_fields[a]+" td.DataTD input").replaceWith(str);
				
				// replace with radio
				// str = '<input onclick="$(\'#'+checkbox_fields[a]+'\').val(this.value);" type="radio" name="rd_'+checkbox_fields[a]+'" checked="checked" value="NULL"> Unchanged <input type="radio" onclick="$(\'#'+checkbox_fields[a]+'\').val(this.value);" name="rd_'+checkbox_fields[a]+'" value="'+vals[0]+'"> Checked <input onclick="$(\'#'+checkbox_fields[a]+'\').val(this.value);" type="radio" name="rd_'+checkbox_fields[a]+'" value="'+vals[1]+'"> Unchecked';
				// jQuery("#tr_"+checkbox_fields[a]+" td.DataTD").append(str);
				// jQuery("#"+checkbox_fields[a]).attr("checked","checked").val("NULL").hide();
				
				// remove checkbox
				// jQuery("#tr_"+checkbox_fields[a]).remove();
			}
		}		
		<?php } ?>
			
		<?php ### P ### ?>
		<?php if (isset($this->actions["clone"]) && $this->actions["clone"] === true) { ?>
		// Clone button
		jQuery("#<?php echo $grid_id?>").jqGrid('navButtonAdd',"#<?php echo $grid_id."_pager"?>",{caption:"",title:"Clone", buttonicon :'ui-icon-copy',
			onClickButton:function(){
				var selr = jQuery("#<?php echo $grid_id?>").jqGrid('getGridParam','selrow');
				if (!selr)
				{
					var alertIDs = {themodal:'alertmod',modalhead:'alerthd',modalcontent:'alertcnt'};
					if (jQuery("#"+alertIDs.themodal).html() === null) {
					    jQuery.jgrid.createModal(alertIDs,"<div>"+jQuery.jgrid.nav.alerttext+
					        "</div><span tabindex='0'><span tabindex='-1' id='jqg_alrt'></span></span>",
					        {gbox:"#gbox_"+jQuery.jgrid.jqID(this.p.id),jqModal:true,drag:true,resize:true,
					        caption:jQuery.jgrid.nav.alertcap,
					        top:100,left:100,width:200,height: 'auto',closeOnEscape:true,
					        zIndex: null},"","",true);
					}
					jQuery.jgrid.viewModal("#"+alertIDs.themodal,
					    {gbox:"#gbox_"+jQuery.jgrid.jqID(this.p.id),jqm:true});
					jQuery("#jqg_alrt").focus();
					return;
				}

				fx_clone_row("<?php echo $grid_id?>",selr);
			} 
		});		
		<?php } ?>
			
		<?php if ($this->actions["export"] === true || $this->actions["export_excel"] === true || $this->actions["export_pdf"] === true || $this->actions["export_csv"] === true) {
		$order_by = "&sidx=".$this->options["sortname"]."&sord=".$this->options["sortorder"]."&rows=".$this->options["rowNum"];
		?>
		function jqgrid_process_export(type)
		{
				type = type || "";
				var detail_grid_params = jQuery("#<?php echo $grid_id?>").data('jqgrid_detail_grid_params');
				detail_grid_params = detail_grid_params || "";
		
				if ("<?php echo $this->options["url"]?>".indexOf("?") != -1)
					window.open("<?php echo $this->options["url"]?>" + "&export=1&jqgrid_page=1&export_type="+type+"<?php echo $order_by?>"+detail_grid_params);
				else
					window.open("<?php echo $this->options["url"]?>" + "?export=1&jqgrid_page=1&export_type="+type+"<?php echo $order_by?>"+detail_grid_params);
		}
		<?php } ?> 

		<?php ### P ### ?>
		<?php if ($this->actions["export"] === true) { ?>
		// Export to what is defined in file
		jQuery("#<?php echo $grid_id?>").jqGrid('navButtonAdd',"#<?php echo $grid_id."_pager"?>",{caption:"Export",title:"Export", buttonicon :'ui-icon-extlink',
			onClickButton:function(){
				jqgrid_process_export();
			} 
		});
		<?php } ?>
			
		<?php ### P ### ?>
		<?php if (isset($this->actions["export_excel"]) && $this->actions["export_excel"] === true) { ?>
		// Export to excel
		jQuery("#<?php echo $grid_id?>").jqGrid('navButtonAdd',"#<?php echo $grid_id."_pager"?>",{caption:"Excel",title:"Excel", buttonicon :'ui-icon-extlink',
			onClickButton:function(){
				jqgrid_process_export('excel');
			} 
		});
		<?php } ?>
			
		<?php ### P ### ?>
		<?php if (isset($this->actions["export_pdf"]) && $this->actions["export_pdf"] === true) { ?>
		// Export to pdf
		jQuery("#<?php echo $grid_id?>").jqGrid('navButtonAdd',"#<?php echo $grid_id."_pager"?>",{caption:"PDF",title:"PDF", buttonicon :'ui-icon-extlink',
			onClickButton:function(){
				jqgrid_process_export('pdf');
			} 
		});		
		<?php } ?>	

		<?php ### P ### ?>
		<?php if (isset($this->actions["export_csv"]) && $this->actions["export_csv"] === true) { ?>
		// Export to csv
		jQuery("#<?php echo $grid_id?>").jqGrid('navButtonAdd',"#<?php echo $grid_id."_pager"?>",{caption:"CSV",title:"CSV", buttonicon :'ui-icon-extlink',
			onClickButton:function(){
				jqgrid_process_export('csv');
			} 
		});		
		<?php } ?>

		function link_multiselect(elem)
		{
			var $elem = $(elem), id = elem.id,
				inToolbar = typeof id === "string" && id.substr(0, 3) === "gs_",
				options = {
					selectedList: 4,
					height: 400,
					checkAllText: "Tout",
					uncheckAllText: "Aucun",
					noneSelectedText: "",
					open: function () {
						var $menu = $(".ui-multiselect-menu:visible");
						$menu.width("auto");
						return;
					}
				},
				$options = $elem.find("option");
			if ($options.length > 0 && $options[0].selected) {
				$options[0].selected = false; // unselect the first selected option
			}
			if (inToolbar) {
				options.minWidth = 'auto';
			}
			$elem.multiselect(options).multiselectfilter({placeholder:''});
			$elem.siblings('button.ui-multiselect').css({
				width: inToolbar ? "98%" : "100%",
				height: "20px",
				fontWeight: "normal",
				backgroundColor: "white",
				color: "black",
				backgroundImage: "inherit",
				borderStyle: "inset"
			});  
			
			// adjust font size as per theme
			jQuery(".ui-multiselect-checkboxes li").css("font-size","inherit");
			
		};	
	
		function link_date_picker(el,fmt,toolbar,opts)
		{
			toolbar = toolbar || 0;
			setTimeout(function(){
				if(jQuery.ui) 
				{ 
					if(jQuery.ui.datepicker) 
					{ 
						opts = (typeof(opts) == 'undefined') ? {} : opts;

						if (toolbar == 0) 
						{
							if (!jQuery.browser.msie && !jQuery("link[href*='ui.bootstrap']").length)
								jQuery(el).css('width',parseInt(jQuery(el).css('width'))-30);
							else 
								jQuery(el).css('width','75%');
								
							jQuery(el).after(' <button>Calendar</button>').next().button({icons:{primary: 'ui-icon-calendar'}, text:false}).css({'font-size':'69%', 'margin-left':'2px'}).click(function(e){jQuery(el).datepicker('show');return false;});
						}

						jQuery(el).datepicker(
												jQuery.extend(
												{
												language: "fr",
												"disabled":false,
												"dateFormat":fmt,
										        "changeMonth": false,
										        "changeYear": false,
												"nextText": '', 
												"prevText": '',
												"firstDay": 1,
												"onSelect": function (dateText, inst) 
															{
																// if toolbar and not search dialog
																if (toolbar && jQuery(el).closest('.searchFilter').length == 0)
																{
											                    	setTimeout(function () {
											                        jQuery("#<?php echo $grid_id?>")[0].triggerToolbar();
											                    	}, 50);
																}
																jQuery(el).trigger('change');
										                	}
        										},opts)
        									).on('changeDate', function(e) {
													if (toolbar && jQuery(el).closest('.searchFilter').length == 0)
														{
															setTimeout(function () {
															jQuery("#<?php echo $grid_id?>")[0].triggerToolbar();
															}, 50);
														}
														jQuery(el).trigger('change');
												});
						
						// disable html autocomplete
						$(el).attr("autocomplete", "off");
						
						// if not bootstrap
						if (!jQuery("link[href*='ui.bootstrap']").length)
							jQuery('.ui-datepicker').css({'font-size':'69%'}); 
					} 
				}
			},300);
		}				

		function link_datetime_picker(el,fmt,toolbar,opts)
		{
			setTimeout(function(){
				if(jQuery.ui) 
				{ 
					if(jQuery.ui.datepicker) 
					{
						opts = (typeof(opts) == 'undefined') ? {} : opts;
						if (toolbar == 0) 
						{	
							if (!jQuery.browser.msie && !jQuery("link[href*='ui.bootstrap']").length)
								jQuery(el).css('width',parseInt(jQuery(el).css('width'))-30);
							else 
								jQuery(el).css('width','75%');
																							
							jQuery(el).after(' <button>Calendar</button>').next().button({icons:{primary: 'ui-icon-calendar'}, text:false}).css({'font-size':'69%', 'margin-left':'2px'}).click(function(e){jQuery(el).datetimepicker('show');return false;});
						}
						
						jQuery(el).datetimepicker(
													jQuery.extend(
													{
													language: "fr",
													"disabled":false,
													"dateFormat":fmt, 
													"changeMonth": false,
													"changeYear": false,
													"nextText": '', 
													"prevText": '',
													"onSelect": function (dateText, inst) 
															{
																// if toolbar and not search dialog
																if (toolbar && jQuery(el).closest('.searchFilter').length == 0)
																{
											                    	setTimeout(function () {
											                        jQuery("#<?php echo $grid_id?>")[0].triggerToolbar();
											                    	}, 50);
																}
																jQuery(el).trigger('change');
										                	}
													}, opts)

												).on('changeDate', function(e) {
													if (toolbar && jQuery(el).closest('.searchFilter').length == 0)
														{
															setTimeout(function () {
															jQuery("#<?php echo $grid_id?>")[0].triggerToolbar();
															}, 50);
														}
														jQuery(el).trigger('change');
												});
												
						// disable html autocomplete
						$(el).attr("autocomplete", "off");
												
						// if not bootstrap
						if (!jQuery("link[href*='ui.bootstrap']").length)
							jQuery('.ui-datepicker').css({'font-size':'69%'});
					}
				}
			},100);
		}	

		<?php ### P ### ?>
		function link_editor(el)
		{
			// disable for inline edit
			// if (jQuery(el).closest('.jqgrow').length)
				// return;
				
			setTimeout(function(){
				// remove nbsp; from start of textarea
				if(el.previousSibling) el.parentNode.removeChild(el.previousSibling);
				
				$(el).parent().css('padding-left','5px');
			
				var editor = CKEDITOR.replace( el, {
					on: {
						change: function(){ jQuery(el).val(editor.getData()); }
					},
					height: '100px',
					enterMode : CKEDITOR.ENTER_BR
				});

				// unblock typing in ckeditor dialog - events tracked with firebug > script > global pause > stack
				$(document).unbind('keypress').unbind('keydown').unbind('mousedown');
				
			},100);
		}

		<?php ### P ### ?>
		<?php if ($this->require_upload_ajax) { ?>
		fx_replace_upload = function(el,field)
		{
			// replace hidden input text with file upload
			jQuery(el).parent().append("<input id='"+field+"_file' size='10' name='" + field + "_file" + "' type='file' onchange='return fx_ajax_file_upload(\""+field+"\",\"<?php echo $this->options["url"]?>\");' />");
			
			// remove msg
			jQuery(el).parent().children("span").remove();
			
			// remove delete button
			jQuery(el).parent().children("input[type=button]").remove();
		}
		<?php } ?>

		function link_upload(el,field)
		{
			setTimeout(function(){

				if(jQuery(el).val() != '')
				{
					// edit
					jQuery(el).parent().append("<span id='"+field+"_name'>"+jQuery(el).val().substring(jQuery(el).val().lastIndexOf('/')+1)+"</span> ");
					jQuery(el).parent().append("<input type='button' value='Delete' onclick='jQuery(\"#"+field+"\").val(\"\"); fx_replace_upload(\"#"+field+"\",\""+field+"\");' />");
					jQuery(el).hide();
				}
				else
				{
					// add
					jQuery(el).parent().append("<input id='"+field+"_file' size='10' name='" + field + "_file" + "' type='file' onchange='fx_ajax_file_upload(\""+field+"\",\"<?php echo $this->options["url"]?>\");' />");
					jQuery(el).hide();
				}

			},100);
		}

		<?php ### P ### ?>
		function link_autocomplete(el,update_field,force_select)
		{
			setTimeout(function()
			{
				if(jQuery.ui) 
				{ 
					if(jQuery.ui.autocomplete)
					{
						jQuery(el).autocomplete({	"appendTo":"body","disabled":false,"delay":300,
													"minLength":1,
													"source":function (request, response)
															{
																request.element = el.name;
																request.oper = 'autocomplete';
																jQuery.ajax({
																	url: "<?php echo $this->options["url"]?>",
																	dataType: "json",
																	data: request,
																	type: "POST",
																	error: function(res, status) {
																		alert(res.status+" : "+res.statusText+". Status: "+status);
																	},
																	success: function( data ) {
																		response( data );
																	}
																});
															},
													"select":function (event, ui)
															{
																// change function to set target value
																var ival,self_field;
																
																if(ui.item) {
																	ival = ui.item.id || ui.item.value;
																}
																
																// if callback is defined for autocomplete, call it
																if (update_field instanceof Function)
																{
																	update_field(ui.item.data);
																	
																	// reset variable to fill autocomplete field after callback function
																	self_field = el.name;
																}
																else
																{
																	self_field = update_field;
																}

																if(ival) {
																	jQuery("input[name='"+self_field+"'].editable, input[id='"+self_field+"']").val(ival);
																	jQuery("textarea[name='"+self_field+"'].editable, textarea[id='"+self_field+"']").val(ival);
																} else {
																	jQuery("input[name='"+self_field+"']").val("");
																	jQuery("textarea[name='"+self_field+"']").val("");
																}
															},
													"change": function( event, ui ) 
															{
																if (!force_select) return;
																
																var select = jQuery(el);
																if ( !ui.item ) 
																{
																	var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
																		valid = false;
																		
																	select.children( "option" ).each(function() {
																		if ( $( this ).text().match( matcher ) ) {
																			this.selected = valid = true;
																			return false;
																		}
																	});
																	
																	if ( !valid ) {
																		
																		// show error msg if new entry
																		$('#FormError>td').html('<div>'+"&nbsp;Selection didn't match any item"+'</div>'); $('#FormError').show().delay(3000).fadeOut();	
																		
																		// remove invalid value, as it didn't match anything
																		$(this).val("");
																		select.val("");
																		
																		setTimeout(function(){$(select).focus();},100);
																		
																		return false;
																	}
																}
															}
												});

						jQuery(el).autocomplete('widget').css('font-size','11px');
						
					} // if jQuery.ui.autocomplete
				} // if jQuery.ui
			},200); // setTimeout
		} // link_autocomplete

		<?php ### P ### ?>
		fx_get_dropdown = function (o,field,for_search_bar)
		{
			// dont process hidden elements, removed for select2 dependent
			// if (!jQuery(o).is(":visible"))
				// return;
			
			var request = {};
			request['value'] = o.value;

			if (o.event == 'onload')
				request['event'] = 'onload';

			// for dialog, else inline
			if (jQuery(o).closest('.FormGrid').length)
				grid_id = jQuery(o).closest('.FormGrid').attr('id').replace('FrmGrid_','');
			else 
				grid_id = jQuery(o).closest('.ui-jqgrid-btable').attr('id');
			
			grid = jQuery('#'+grid_id);

			// get editable and non-editable data, both
			var row = grid.getRowData(grid.getGridParam('selrow'));
			for (var a in row)
				request[a] = row[a];

			// override html data (from above) with content of editable fields
			jQuery(".editable").each(function(){ request[jQuery(this).attr('name')] = jQuery(this).val(); });
			
			// for dialogs, load param from visible form selection
			jQuery(".FormGrid:visible .FormElement").each(function(){ request[jQuery(this).attr('id')] = jQuery(this).val(); });
			
			if (for_search_bar)
				jQuery(".ui-search-input input, .ui-search-input select").each(function(){ request[jQuery(this).attr('name')] = jQuery(this).val(); });
			
			// dont send 'act' column data
			request['act'] = null;
			
			// to detect internal ajax call
			request['nd'] = '12345';
			
			request['src'] = jQuery(o).attr('name');
				
			// if callback is set for dropdown
			if (field instanceof Function)
				request['return'] = "json";
			else
			{
				request['return'] = "option";
				jQuery('select[name='+field+'].editable').html("<option value=''>Loading...</option>");
			}
			
			jQuery.ajax({
						url: grid.getGridParam('url'),
						dataType: 'html',
						data: request,
						type: 'POST',
						error: function(res, status) {
							alert(res.status+' : '+res.statusText+'. Status: '+status);
						},
						success: function( data ) {

							if (for_search_bar == 1)
							{
								data = "<option value=''></option>" + data;
								jQuery('select[name='+field+']').html(data);
							}
							else
							{
								// if callback is defined for dropdown, call it
								if (field instanceof Function)
								{
									field(data);
								}
								else
								{
									// for inline edit
									jQuery('select[name='+field+'].editable').html(data);
									// for dialog edit
									jQuery('select[name='+field+'].FormElement').html(data);
									
									// for celledit mode
									if (field == o.name)
										jQuery(o).html(data);

									// load correct field value
									if (field == o.name)
										val = request['value'] || request[request['src']];
									else
										val = request[field];

									// reselect last option if exist, in new dropdown data
									if (jQuery('select[name='+field+'].editable, select[name='+field+'].FormElement')[0])
										jQuery('select[name='+field+'].editable, select[name='+field+'].FormElement')[0].selectedIndex = -1;
									
									jQuery('select[name='+field+'].editable, select[name='+field+'].FormElement').val(val);
									
									// invoke change event for dependents
									jQuery('select[name='+field+'].editable, select[name='+field+'].FormElement').change();
								}
									
								// load (if any) new values in dropdown k:v values
								if (o.event == 'onload')
								{
									// add new dropdown values in column
									var s = grid.getColProp(field).editoptions.value;
									var rec = s.split(";");
									var vals = new Array();
									for (var x in rec)
									{
										tmp = rec[x].split(":");
										vals[tmp[0]] = tmp[1];
									}
									
									var arr = new Array();
									jQuery('select[name='+field+'] option').each(function()
									{
										vals[$(this).val()] = $(this).text();
									});

									s = '';
									for(var x in vals)
										s += x+":"+vals[x]+";";
									
									// remove last ;
									s = s.substring(0,s.length-1);
									
									grid.setColProp(field,{editoptions:{value:s}});
									
									// reselect dropdown value for cellEdit mode, rest already working
									if (grid.getGridParam('cellEdit'))
									{
										val = request['value'] || request[request['src']];
										jQuery('.ui-jqgrid-btable select[name='+field+']').val(val);
									}
								}
							
							}
						}
					});
		};
		
		// function to reload new values in dropdown (from add/edit dialog) - work with onload-sql
		fx_reload_dropdown = function (f)
		{
			// fill dropdown 1 based on row data
			o = jQuery('select[name='+f+']').get();
			o.event = 'onload'; 
			fx_get_dropdown(o,f);
		}

		<?php ### P ### ?>
		fx_success_msg = function (msg,fade)
		{
			var t = Math.max(0, ((jQuery(window).height() - jQuery('#info_dialog').outerHeight()) / 2) + jQuery(window).scrollTop());
			var l = Math.max(0, ((jQuery(window).width() - jQuery('#info_dialog').outerWidth()) / 2) + jQuery(window).scrollLeft());

			jQuery.jgrid.info_dialog("Info","<div class='ui-state-highlight' style='padding:5px;'>"+msg+"</div>", 
												jQuery.jgrid.edit.bClose,{buttonalign:"right", left:l, top:t  });
											
			jQuery("#info_dialog").abscenter();

	      	if (fade == 1)
			{
				jQuery("#info_dialog").delay(1000).fadeOut();
				setTimeout('jQuery.jgrid.hideModal("#info_dialog");',1200);
			}
		};

		<?php ### P ### ?>
		fx_bulk_update = function (op,data,selection,grid)
		{
			if (typeof(grid) == "undefined")
				grid = '<?php echo $grid_id?>';
				
			if (typeof(selection) == 'undefined')
			{
				// for non multi select
				var selr_one = jQuery('#'+grid).jqGrid('getGridParam','selrow'); // array of id's of the selected rows when multiselect options is true. Empty array if not selection 

				// for multi select
				var selr = [];
				selr = jQuery('#'+grid).jqGrid('getGridParam','selarrrow'); // array of id's of the selected rows when multiselect options is true. Empty array if not selection 
				
				if (selr.length < 2 && selr_one)
					selr[0] = selr_one;

				// don't process if nothing is selected
				if (selr.length == 0)
				{
					jQuery.jgrid.info_dialog(jQuery.jgrid.errors.errcap,'<div class=\"ui-state-error\">Please select rows to edit</div>', 
												jQuery.jgrid.edit.bClose,{buttonalign:'right'});
					return;
				}

				var str = selr[0];
				for (var x=1;x < selr.length;x++)
				{
					str += ',' + selr[x];
				}
			}
			else
				str = selection;
				
			// call ajax to update date in db
			var request = {};
			request['oper'] = 'edit';
			request['id'] = str;
			request['bulk'] = op;
			if (data)
				request['data'] = data;

			jQuery.ajax({
				url: jQuery('#'+grid).jqGrid('getGridParam','url'),
				dataType: 'html',
				data: request,
				type: 'POST',
				error: function(res, status) {
					jQuery.jgrid.info_dialog(jQuery.jgrid.errors.errcap,'<div class=\"ui-state-error\">'+ res.responseText +'</div>', 
							jQuery.jgrid.edit.bClose,{buttonalign:'right'});
				},
				success: function( data ) {
					if (data != "")
					{
						data = JSON.parse(data);
						if (typeof(data.msg) != "undefined")
							fx_success_msg(data.msg,data.fade);
					}
					else
						fx_success_msg("<?php echo $this->options["edit_options"]["success_msg_bulk"]?>",1);
					
					// reload grid for data changes
					jQuery('#'+grid).jqGrid().trigger('reloadGrid',[{jqgrid_page:1}]);
				}
			});

		};


		<?php ### P ### ?>
		<?php if ($this->require_upload_ajax) { ?>
		
		fx_ajax_file_upload = function (field,upload_url)
		{
			// bug fix for chrome, multiple upload issue
			if (jQuery.browser.chrome)
			{
				if (jQuery("input#"+field).data("invoked") == 1)
					return;
				jQuery("input#"+field).data("invoked",1);
			}
			
			//starting setting some animation when the ajax starts and completes
			
			jQuery("input#"+field).parent().not(":has(span)").append('<span id="'+field+'_upload"></span>');
			jQuery("span#"+field+"_upload").html("<img src='http://easycaptures.com/fs/uploaded/858/0869383662.gif'>&nbsp;(Uploading...)");
			jQuery("input#"+field+"_file").hide();
			
			/*
			prepareing ajax file upload
			url: the url of script file handling the uploaded files
						fileElementId: the file type of input element id and it will be the index of  $_FILES Array()
			dataType: it support json, xml
			secureuri:use secure protocol
			success: call back function when the ajax complete
			error: callback function when the ajax failed
			*/
			
			jQuery.extend({
				handleError: function( s, xhr, status, e ) 
				{
					if(xhr.responseText)
						console.log(xhr.responseText);
				}
			});
			
			// hide submit button till upload
			jQuery('#sData').hide();
			jQuery.ajaxFileUpload
			(
				{
					url:upload_url, 
					secureuri:false,
					fileElementId:field+"_file",
					dataType: 'json',
					data: {"field":field+"_file"},
					success: function (data, status)
					{
						// bug fix for chrome, multiple upload issue
						if (jQuery.browser.chrome)
							jQuery("input#"+field).data("invoked",0);
						
						if(typeof(data.error) != 'undefined')
						{
							if(data.error != '')
							{
								//alert(data.error);
								jQuery("tr#FormError td.ui-state-error").html(data.error);
								jQuery("tr#FormError").show();
								jQuery("#"+field+"_upload").html("");

								// reset file upload
								jQuery("input#"+field+"_file").replaceWith(jQuery("input#"+field+"_file")[0].outerHTML);
								
							}
							else
							{
								//alert(data.msg);
								jQuery("#"+field+"_upload").html(data.msg.substring(data.msg.lastIndexOf('/')+1) + " (Uploaded)");

								// hide error if displayed
								jQuery("tr#FormError td.ui-state-error").html("");
								jQuery("tr#FormError").hide();
								
								var o = jQuery("input#"+field);
								jQuery(o).val(data.msg);
																	 
								jQuery(o).parent().append("&nbsp;<input type='button' value='Delete' onclick='fx_replace_upload(\"#"+field+"\",\""+field+"\");' />");
																	 
								jQuery("#"+field+"_file").remove();

							}
						}
						
						// show submit button again
						jQuery('#sData').show();
					},
					error: function (data, status, e)
					{
						// bug fix for chrome, multiple upload issue
						if (jQuery.browser.chrome)
							jQuery("input#"+field).data("invoked",0);

						alert(e);
						jQuery('#sData').show();
					}
				}
			)
			return false;
		};	
		<?php } ?>
		
		<?php ### P ### ?>
		<?php if (isset($this->options["toolbar"]) && $this->options["toolbar"] != "bottom") { ?>
			// Toolbar position button
			
			jQuery(document).ready(function(){

				<?php if ($this->options["toolbar"] == "top") { ?>
					jQuery('#<?php echo $grid_id?>_pager').insertBefore('#<?php echo $grid_id?>_toppager');
				<?php } else if ($this->options["toolbar"] == "both") { ?>
					jQuery('#<?php echo $grid_id?>_pager').clone(true).insertBefore('#<?php echo $grid_id?>_toppager').attr('id','_toppager');
				<?php } ?>

				jQuery('#<?php echo $grid_id?>_pager').removeClass("ui-jqgrid-pager");
				jQuery('#<?php echo $grid_id?>_pager').addClass("ui-jqgrid-toppager");
				jQuery('#<?php echo $grid_id?>_toppager').remove();
				jQuery('#_toppager').attr('id','<?php echo $grid_id?>_toppager');
				
				// for bootstrap
				if (jQuery("link[href*='ui.bootstrap']").length)
				{
					jQuery('div.frozen-div').css('top','+=6px');
					jQuery('div.frozen-bdiv').css('top','+=6px');
				}
			});
		<?php } ?>

		<?php if ($this->options["autoresize"] === true) { ?>
		jQuery(document).ready(function(){
			jQuery(window).bind("resize", function () {
					
				// if multi grids on same page
				jQuery(".ui-jqgrid").each(function(i,o){
					var gid = jQuery(o).attr("id").replace("gbox_","");
					
					var oldWidth = jQuery("#"+gid).jqGrid("getGridParam", "width");
					
					// if grid in tab, use tab width else window's width
					if (jQuery("#"+gid).closest(".ui-tabs").length)
						var newWidth = jQuery(".ui-tabs:first").width() - 40;
					else
					if (jQuery("#"+gid).closest(".tab-content").length)
						var newWidth = jQuery(".tab-content:first").width() - 20;
					else						
						var newWidth = (jQuery(window).width() * 0.975) - $('#sidebar').width() - 40; //!! - JOHN - specif giwwi sidebar menu

					if (oldWidth !== newWidth) 
					{
						jQuery("#"+gid).jqGrid("setGridWidth", newWidth);
					}

					<?php if ($this->options["autoheight"] === true) { ?>
					// adjust height on resize
					jQuery("#"+gid).jqGrid('setGridHeight',jQuery(window).innerHeight()-120);
					<?php } ?>

				});
			}).trigger("resize");
		});
		<?php } ?>
			
		<?php if ($this->options["resizable"] === true) { ?>
		jQuery("#<?php echo $grid_id?>").jqGrid('gridResize',{});
		<?php } ?>

		// bind arrow keys navigation
		jQuery("#<?php echo $grid_id?>").jqGrid('bindKeys',{'onEnter':function(rowid){ jQuery("tr.jqgrow[id="+rowid+"]").dblclick(); } });
		
		<?php ### P ### ?>
		jQuery("#<?php echo $grid_id?>").jqGrid('setFrozenColumns');
		jQuery("#<?php echo $grid_id?>").triggerHandler("jqGridAfterGridComplete");
		
		// center position div (abs)
		jQuery.fn.abscenter = function () {
			this.css("position","absolute");
			this.css("top", Math.max(0, ((jQuery(window).height() - jQuery(this).outerHeight()) / 2) + 
														jQuery(window).scrollTop()) + "px");
			this.css("left", Math.max(0, ((jQuery(window).width() - jQuery(this).outerWidth()) / 2) + 
														jQuery(window).scrollLeft()) + "px");
			return this;
		};

		// center position div (abs)
		jQuery.fn.fixedcenter = function () {
			this.css("position","fixed");
			this.css("top", Math.max(0, ((jQuery(window).height() - jQuery(this).outerHeight()) / 2)) + "px");
			this.css("left", Math.max(0, ((jQuery(window).width() - jQuery(this).outerWidth()) / 2)) + "px");
			return this;
		};
		
		// simulate ENTER on dialogs, and tabbing to submit,cancel
		jQuery.extend(jQuery.jgrid.edit, {
				onInitializeForm: function ($form) {
					jQuery("#sData, #cData").attr("tabIndex",0);
					
					jQuery("td.DataTD>.FormElement, #sData").keypress(function (e) {
						if (e.which === jQuery.ui.keyCode.ENTER && e.target.tagName != "TEXTAREA") {
							jQuery("#sData", $form.next()).trigger("click");
							return false;
						}
					});
					
					jQuery("#cData").keypress(function (e) {
						if (e.which === $.ui.keyCode.ENTER) {
							jQuery("#cData", $form.next()).trigger("click");
							return false;
						}
					});
				}
			});		
				
		// dialog display effect
		jQuery.extend(jQuery.jgrid, {
			showModal : function(h) {
				h.w.show("fade");
			},
			closeModal : function(h) {
				h.w.hide("fade").attr("aria-hidden", "true");
				if(h.o) {h.o.remove();}
			}
		});			
		
		<?php ### P ### ?>
		<?php echo $this->internal["js_dependent_dropdown"]; ?>		
	<?php
	}

	function prepare_sql($sql,$db)
	{
		### P ###
		if (strpos($db,"mssql") !== false)
		{
			$sql = preg_replace("/SELECT (.*) LIMIT ([0-9]+) OFFSET ([0-9]+)/i","select top ($2+$3) $1",$sql);
			#pr($sql,1);
		}
		else if (strpos($db,"firebird") !== false)
		{
			$sql = preg_replace("/SELECT (.*) LIMIT ([0-9]+) OFFSET ([0-9]+)/i","select FIRST $2 SKIP $3 $1",$sql);
			#pr($sql,1);
		}		
		else if (strpos($db,"oci8") !== false)
		{
			preg_match("/(.*) LIMIT ([0-9]+) OFFSET ([0-9]+)/i",$sql,$matches);
			
			if (count($matches))
			{
				$query = $matches[1];
				$limit = $matches[2];
				$offest = $matches[3];

				$offset_min = $offest;
				$offset_max = $offest + $limit;
				
				$sql = "
					SELECT * FROM (
						SELECT a.*,rownum rnum
						FROM ($query) a
					)
					WHERE rnum > $offset_min AND rnum <= $offset_max
				";
			}			
		}
		// @todo: not tested in detail
		else if (strpos($db,"db2") !== false)
		{
			preg_match("/(.*) LIMIT ([0-9]+) OFFSET ([0-9]+)/i",$sql,$matches);
			
			if (count($matches))
			{
				$query = $matches[1];
				$limit = $matches[2];
				$offest = $matches[3];

				$offset_min = $offest;
				$offset_max = $offest + $limit;
				
				$sql = "SELECT b.* FROM (SELECT a.*,row_number() over() as rnum FROM ($query) a) b WHERE b.rnum > $offset_min AND b.rnum <= $offset_max";
			}			
		}
		### P-END ###

		return $sql;
	}

	// replace any param in data pattern e.g. http://domain.com?id={id} given that, there is a $col["name"] = "id" exist
	function replace_row_data($row,$str)
	{
		foreach($this->options["colModel"] as $link_c)
		{
			$link_row_data = $row[$link_c["name"]];
			$str = str_replace("{".$link_c["name"]."}", $link_row_data, $str);
		}
		return $str;
	}

	function addslashes_mssql($str)
	{
	 	if (is_array($str)) 
	 	{
			foreach($str AS $id => $value) 
			{
	 			$str[$id] = addslashes_mssql($value);
	 		}
	 	} 
	 	else 
	 	{
	 		$str = str_replace("'", "''", $str);
	 	}

	 	return $str;
	}
	
	function escape_string($v)
	{
		if (strpos($this->db_driver, "mssql")!== false)
			$v = $this->addslashes_mssql($v);
		else if (strpos($this->db_driver, "postgres") !== false)
			$v = $this->addslashes_mssql($v);
		else
			$v = addslashes($v);
				
	 	return $v;
	}

	// preserve subqueries
	function remove_subsql()
	{
		$match = array();

		// extract all aggregate fxs
		preg_match_all("/[a-zA-Z_]+\\([^)]+\)/i", $this->select_command, $fx);
		$fx = $fx[0];
		$match = array_merge($match,$fx);
		
		// put placeholders for agg fxs
		for($i=0;$i<count($match);$i++)
			$this->select_command = str_replace($match[$i],"{".$i."}",$this->select_command);
			
		// extract subqueries
		preg_match_all("/\(([\s\S]*select [^)]*)\)/i", $this->select_command, $subsql);	
		$subsql = $subsql[0];
		$match = array_merge($match,$subsql);

		// put placeholder for subqueries
		for($i=0;$i<count($match);$i++)
			$this->select_command = str_replace($match[$i],"{".$i."}",$this->select_command);
		
		return $match;
	}
	
	// re-adjust subqueries in sql
	function add_subsql($sql,$match)
	{
		// replace placeholder in rev order
		// first subqueries then agg fxs
		for($i=count($match)-1;$i>=0;$i--)
			$sql = str_replace("{".$i."}",$match[$i],$sql);
			
		return $sql;
	}
	
	// wrap fields with space names ``,"",[]
	function wrap_field($field)
	{
		// if field has space replaced with - (SELECT `i d` as `i-d`..)
		if (strpos($field,'-') !== false)
			$field = str_replace("-"," ",$field);
	
		// add tilde sign for mysql
		if (strpos($this->db_driver, "mysql")!== false || !isset($this->db_driver))
		{
			$field = "`".$field."`";
			// if dbname table.field alias
			$field = str_replace(".","`.`",$field);
		}
		elseif (strpos($this->db_driver, "db2")!== false)
		{
			$field = '"'.$field.'"';
			// if dbname table.field alias
			$field = str_replace(".",'"."',$field);
		}
		
		return $field;
	}
	
	### P ###
	function add_tree_data(&$data)
	{
		$id = $this->options["treeConfig"]["id"];
		$p_id = $this->options["treeConfig"]["parent"];
		$table = $this->table;
	
		function update_tree_level(&$datas, $depth = 0, $parent = "",$grid)
		{
			$id = $grid->options["treeConfig"]["id"];
			$p_id = $grid->options["treeConfig"]["parent"];
			
			if($depth > 1000) return ''; // Make sure not to have an endless recursion
			for($i=0, $ni=count($datas); $i < $ni; $i++){
				if($datas[$i][$p_id] == $parent){
					$datas[$i]["level"] = intval($depth);
					update_tree_level($datas, $depth+1, $datas[$i][$id],$grid);
				}
			}
		}		
		
		// for loaded=false case, 
		if (!isset($_REQUEST["nodeid"]))
			$node = 0;
		else
			$node = intval($_REQUEST["nodeid"]);
		
		if (!isset($_REQUEST["n_level"]) || !is_numeric($_REQUEST["n_level"]))
			$n_lvl = 0;
		else
			$n_lvl = intval($_REQUEST["n_level"])+1;
		
		// update tree level
		update_tree_level($data,$n_lvl,$node,$this);
		
		$SQL = "SELECT t1.{$id} FROM {$table} AS t1 LEFT JOIN {$table} AS t2 ON t1.{$id} = t2.{$p_id} WHERE t2.{$id} IS NULL";
		$SQL = $this->prepare_sql($SQL,$this->db_driver);
		$result = $this->execute_query($SQL);

		if ($this->con)
		{
			$rows = $result->GetRows();
		}
		else
		{
			$rows = array();
			while($r = mysqli_fetch_array ($result,MYSQLI_ASSOC))
				$rows[] = $r;
		}	
		
		foreach($data as &$row)
		{
			$row["loaded"] = "true";
			$row["expanded"] = "true";
			
			if ($this->options["treeConfig"]["loaded"] === false)
				$row["loaded"] = "false";
				
			if ($this->options["treeConfig"]["expanded"] === false)
				$row["expanded"] = "false";
			
			$row["isLeaf"] = "false";
			
			foreach($rows as $r)
			{
				if ($row["emp_id"] == $r["emp_id"])
				{
					$row["isLeaf"] = "true";
					break;
				}
			}	
		}
	}
	
	// fix for date > 2038 with php 5.2
	function custom_date_format($fmt, $date)
	{
		// fix for d/m/Y or d/m/y date format. strtotime expects m/d/Y
		if (stristr($link_c["formatoptions"]["newformat"],"d/m/Y"))
		{
			$val['data'] = preg_replace('/(\d+)\/(\d+)\/(\d+)/i','$2/$1/$3',$val['data']);
		}
		// fix for d-m-y (2 digit year) for strtotime
		else if (strstr($link_c["formatoptions"]["newformat"],"d-m-y"))
		{
			$val['data'] = preg_replace('/(\d+)-(\d+)-(\d+)/i','$3-$2-$1',$val['data']);
		}
		else if (strstr($link_c["formatoptions"]["newformat"],"d/M/Y") || strstr($link_c["formatoptions"]["newformat"],"d-M-Y"))
		{
			$val['data'] = preg_replace('/\/\-/i',' ',$val['data']);
		}
					
		if (floatval(PHP_VERSION) >= 5.2)
			$data[$c["index"]] = date_format(date_create($date),$fmt);
		else
			$data[$c["index"]] = date($fmt,strtotime($date));
			
		return $data[$c["index"]];
	}
}

# In PHP 5.2 or higher we don't need to bring this in
if (!function_exists('json_encode')) 
{
	require_once 'JSON.php';
	function json_encode($arg)
	{
		global $services_json;
		if (!isset($services_json)) {
			$services_json = new Services_JSON();
		}
		return $services_json->encode($arg);
	}

	function json_decode($arg)
	{
		global $services_json;
		if (!isset($services_json)) {
			$services_json = new Services_JSON();
		}
		return $services_json->decode($arg);
	}
}

/**
 * Common function to display errors
 */
if (!function_exists('phpgrid_error'))
{
	function phpgrid_error($msg)	
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
		die($msg);	
	}
}

/**
 * Common function to display custom success messages
 */
if (!function_exists('phpgrid_msg'))
{
	function phpgrid_msg($msg,$fade=1)	
	{
		die(json_encode(array("msg"=>$msg, "fade"=>$fade)));
	}
}

/**
 * Internal debug function
 */
if (!function_exists('phpgrid_pr'))
{
	function phpgrid_pr($arr, $exit=0)
	{
		echo "<pre>";
		print_r($arr);
		echo "</pre>";
		
		if ($exit)
			die;
	}
}

/**
 * Function to encode JS function reference from PHP array
 * http://www.php.net/manual/en/function.json-encode.php#105749
 */
function json_encode_jsfunc($input=array(), $funcs=array(), $level=0)
{
	foreach($input as $key=>$value)
	{
		if (is_array($value))
		{
			$ret = json_encode_jsfunc($value, $funcs, 1);
			$input[$key]=$ret[0];
			$funcs=$ret[1];
		}
		else
		{
			if (substr($value,0,8)=='function')
			{
				$func_key="#".rand()."#";
				$funcs[$func_key]=$value;
				$input[$key]=$func_key;
			}
			// for json data, incase of local array
			else if (substr($value,0,2)=='[{')
			{
				$func_key="#".rand()."#";
				$funcs[$func_key]=$value;
				$input[$key]=$func_key;
			}
		}
	}
  	if ($level==1)
	{
		return array($input, $funcs);
	}
  	else
	{
		$input_json = json_encode($input);
	  	foreach($funcs as $key=>$value)
		{
			$input_json = str_replace('"'.$key.'"', $value, $input_json);
		}
	  	return $input_json;
	}
}

/*
// resume older session or create new
session_start();

// check if internal grid's ajax call or not
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
$is_ajax = $is_ajax && (isset($_REQUEST["nd"]) || isset($_REQUEST["oper"]) || isset($_REQUEST["export"]));

// preserve old POST data for ajax call and reload on ajax
if (!$is_ajax)
{
	$_SESSION["jqgrid_post"] = serialize($_POST);
}
else
{
	$old_post = unserialize($_SESSION["jqgrid_post"]);
	if (!$old_post) $old_post = array();
	$_REQUEST = array_merge($old_post, $_REQUEST);
	$_POST = array_merge($old_post, $_POST);
}

*/