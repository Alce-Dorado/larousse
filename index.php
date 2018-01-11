<?php

require('vendor/autoload.php');

use Dictionary\DynamoDb 	as DynamoDb;	#/dictionary/dynamodb
use Dictionary\Redshift 	as Redshift; 	#/dictionary/dynamodb
use Dictionary\Formatter 	as Formatter; 	#/dictionary/formatter
use Dictionary\Updater 		as Updater; 	#/dictionary/updater
use Dictionary\Validator 	as Validator; 	#/dictionary/updater


$f3=\Base::instance();
$f3->config('config.cfg');
$f3->set('DEBUG',3);
/*
 *	Main route. Used to display schemas, tables and table detail pages.
 *	usage: http://base.url/{schema}/{table}"
 */
$f3->route(array(
		'GET /',
		'GET /@schema',
		'GET /@schema/@table',
		'GET /@schema/@table/@refresh',
	),
    function($f3,$params) {
    	$validator = new Validator\Component();
    	$validator->validate($f3);
    	try {
    		$redshift = new Redshift\Component($f3->get(REDSHIFTENDPOINT), $f3->get(REDSHIFTUSER), $f3->get(REDSHIFTPASS));
    	} catch (Exception $e) {
    		die('Connection to Redshift failed. Please use valid Redshift access credentials');
    	}
    	$updater = new Updater\Component();
		$f3->set('params', $params);
		$params = $updater->setDefaultParamData($f3, $params);

		// let's instantiate our dynamodb component
    	$dynamodb = new DynamoDb\Component($f3->get(AWSKEY), $f3->get(AWSSECRET));

		if(($params['schema'] != '*') && ($params['table'] != '*')){
			// let's get the dynamodb connection client
			$client = $dynamodb->getClient();
			// let's get the marshaller going for item formatting/manipulation
			$marshaller = $dynamodb->getMarshaller();
			// let's fetch the catched item
			$cached_item = $dynamodb->fetchCachedItem($params);

			if(!isset($cached_item['Item']) || $params['refresh'] != '*'){
				$cached_item['Item'] = $dynamodb->upsertMetaData($f3, $params);
			}

			$unmarshalled_cache_json = $marshaller->unmarshalJson($cached_item['Item']);
			$unmarshalled_cached_item = json_decode($unmarshalled_cache_json);

			$updater->setDynamicTemplateData($f3, $unmarshalled_cached_item, $unmarshalled_cache_json);
			echo \Template::instance()->render('web/default/templates/detail_template.phtml');
		}

		if(($params['schema'] != '*') && ($params['table'] == '*')){
			$tables = $redshift->getTables($params['schema']);
			$f3->set('tables', $tables);
			echo \Template::instance()->render('web/default/templates/listing_template.phtml');
		}

		if(($params['schema'] == '*') && ($params['table'] == '*')){
			$schemas = $redshift->getSchemas();
			$f3->set('schemas', $schemas);
			echo \Template::instance()->render('web/default/templates/schema_template.phtml');
		}
    }
);

/*
 *	Route used to make updates (AJAX) to items stored in DynamoDb. We use this route to udpate
 *	descriptions; either table descriptions or column descriptions.
 */
$f3->route('POST /updates',
    function($f3) {
    	$validator = new Validator\Component();
    	$validator->validate($f3);
    	$params = array (
    		'schema' => $f3->get('POST.schema'),
    		'table' =>$f3->get('POST.table')
    	);

    	$table_description = $f3->get('POST.table_description');
    	$column_description = $f3->get('POST.column_description');
    	$column = $f3->get('POST.column');

    	// let's instantiate our dynamodb component
    	$dynamodb = new DynamoDb\Component($f3->get(AWSKEY), $f3->get(AWSSECRET));
		// let's get the dynamodb connection client
		$client = $dynamodb->getClient();
		// let's get the marshaller going for item formatting/manipulation
		$marshaller = $dynamodb->getMarshaller();
		// let's fetch the catched item
		$cached_item = $dynamodb->fetchCachedItem($params);

		$unmarshalled_cache_json = $marshaller->unmarshalJson($cached_item['Item']);
		$unmarshalled_cached_item = json_decode($unmarshalled_cache_json);
		if($column == ""){
			$unmarshalled_cached_item->description = $table_description;
		}

		if($column != ""){
			foreach($unmarshalled_cached_item->column as $column_key => $column_object){
				if($column_object->column == $column){
					$column_object->description = $column_description;
				}
			}
		}

		$item = $marshaller->marshalItem($unmarshalled_cached_item);
		$result = $client->putItem(array('TableName' => "dwh_dictionary", 'Item' => $item));

	}
);

/*
 *	Command Line route.
 * 	usage: php index.php "itemupdate/{schema}/{table}"
 */
$f3->route(array(
		'GET /itemupdate/@schema/@table',
	), function($f3, $params) {
	$validator = new Validator\Component();
    	$validator->validate($f3);
	$dynamodb = new DynamoDb\Component($f3->get(AWSKEY), $f3->get(AWSSECRET));
	return $dynamodb->upsertMetaData($f3, $params);
});


$f3->route(array(
	'GET /createuser/@username/@password',
), function($f3, $param) {
	$db = new \DB\Jig('data/');
	$current_users = $db->read('users');
	$current_users[$param['username']] = array('username' => $param['username'], 'password' => $param['password']);
	
	$db->write('users', $current_users);
});
$f3->run();

?>