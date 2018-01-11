<?php

namespace Dictionary\Formatter;

class Component
{
	function __construct(){
		return $this;
	}

	public function formatStatisticsData($rows, $columns, $latest_update){
		$statistics = array(
			'rows'		=> 	$rows,
			'columns'	=> 	count($columns),
			'latest_update'	=> 	(is_array($latest_update) ? "N/A" : $latest_update),
			'status'	=> 	"Active"
		);
		return $statistics;
	}

	public function formatColumnsData($raw_columns){
		$columns_defs = array();
		foreach($raw_columns as $column){
			$column_object = (object)$column;

		 	 $column = array(
		 	 	'schemaname' 	=> $column_object->schemaname,
		 	 	'tablename'		=> $column_object->tablename,
		 	 	'column'		=> $column_object->column,	
		 	 	'type'			=> $column_object->type,
		 	 	'encoding'		=> $column_object->encoding,
		 	 	'distkey'		=> ($column_object->distkey ? 'true' : 'false'),
		 	 	'sortkey'		=> $column_object->sortkey,
		 	 	'notnull'		=> ($column_object->notnull ? 'true' : 'false')
		 	 );
		 	 array_push($columns_defs, $column);
		}
		return $columns_defs;
	}

	public function formatSampleData($raw_sample, $formatted_columns){
		$formatted_sample = array();
		foreach($raw_sample as $row){
			$formatted_row = array();
			foreach($formatted_columns as $column){
				if(isset($row[$column['column']])){
					if(($row[$column['column']] == '') || ($row[$column['column']] == null)){
						$row[$column['column']] = null;
					}
					$formatted_row[$column['column']] = $row[$column['column']];
				}else{
					$formatted_row[$column['column']] = null;
				}
			}
			array_push($formatted_sample, $formatted_row);
		}
		return $formatted_sample;
	}
}

?>