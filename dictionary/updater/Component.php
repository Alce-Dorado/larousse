<?php

namespace Dictionary\Updater;

use Dictionary\DynamoDb as DynamoDb;
use Dictionary\Redshift as Redshift;
use Dictionary\Formatter as Formatter;

/**
* 
*/
class Component
{
	
	function __construct()
	{
		return $this;
	}

	public function setDefaultParamData($f3, $params){
		$params+=array('schema'=>'*','table'=>'*', 'refresh' => '*');

		if(($params['schema'] == '*')){
			$f3->set('schema', "empty");
		}else{
			$f3->set('schema', $params['schema']);
		}

		if(($params['table'] == '*')){
			$f3->set('table', "empty");
		}else{
			$f3->set('table', $params['table']);
		}

		return $params;
	}

	function setDynamicTemplateData($f3, $unmarshalled_cached_item, $unmarshalled_cache_json){
		$f3->set('description', $unmarshalled_cached_item->description);
		$f3->set('rows',$unmarshalled_cached_item->statistics->rows);
		$f3->set('columns',$unmarshalled_cached_item->statistics->columns);
		$f3->set('latest_update',$unmarshalled_cached_item->statistics->latest_update);
		$f3->set('status',$unmarshalled_cached_item->statistics->status);
		$f3->set('column_defs', $unmarshalled_cached_item->column);
		$f3->set('sample_data', $unmarshalled_cached_item->sample);
		$f3->set('item_json', $unmarshalled_cache_json);
	}
}