<?php 

	namespace Dictionary\DynamoDb;

	use Aws\DynamoDb\DynamoDbClient;
	use Aws\DynamoDb\Marshaler;
	use Dictionary\DynamoDb 	as DynamoDb;	#/dictionary/dynamodb
	use Dictionary\Redshift 	as Redshift; 	#/dictionary/dynamodb
	use Dictionary\Formatter 	as Formatter; 	#/dictionary/formatter
	use Dictionary\Updater 		as Updater; 	#/dictionary/updater
	use Dictionary\Validator 	as Validator; 	#/dictionary/updater


	class Component
	{
		private $_client;
		private $_marshaller;

		function __construct($key,$secret){
			$this->_client = DynamoDbClient::factory(array(
				'region'  => 'us-east-1',
				'version' => 'latest',
			    'credentials' => array(
			        'key'    => $key,
			        'secret' => $secret,
			    )
			));	

			$this->_marshaller = new Marshaler();
			return $this;
		}

		public function fetchCachedItem($key){
			$cached_item = $this->getClient()->getItem(array(
					'ConsistentRead' => true,
					'TableName' => 'dwh_dictionary',
					'Key'       => array(
					'schema'   => array('S' => $key['schema']),
					'table' => array('S' => $key['table'])
				)
			));

			return $cached_item;
		}

		public function getClient(){
			return $this->_client;
		}

		public function getMarshaller(){
			return $this->_marshaller;
		}

		public function formatNewItem($marshaller, $params, $formatted_statistics, $formatted_columns, $formatted_sample){
			$new_item = $marshaller->marshalItem(
				array(
					'schema' 	=> $params['schema'],
					'table' 	=> $params['table'],
					'statistics' => $formatted_statistics,
					'column' => $formatted_columns,
					'sample' => $formatted_sample,
				)
			);

			return $new_item;
		}

		public function formatItemToUpdate($marshaller, $new_item, $cached_item){
			if(isset($cached_item['Item'])){
				$unmarshalled_new_json = $marshaller->unmarshalJson($new_item);
				$unmarshalled_new_item = json_decode($unmarshalled_new_json);

				$unmarshalled_cached_json = $marshaller->unmarshalJson($cached_item['Item']);
				$unmarshalled_cached_item = json_decode($unmarshalled_cached_json);

				$unmarshalled_new_item->description = $unmarshalled_cached_item->description;
				foreach($unmarshalled_new_item->column as $new_column_key => $new_column_value){
					foreach ($unmarshalled_cached_item->column as $cached_key => $cached_value) {
						if($new_column_value->column == $cached_value->column){
							$new_column_value->description = $cached_value->description;
						}
					}
				}
				$updated_item = $marshaller->marshalItem($unmarshalled_new_item);
			}else{
				$updated_item = $new_item;
			}
			return $updated_item;
		}

		public function upsertMetaData($f3, $params){
			$formatter_component = new Formatter\Component();
			// let's instantiate our dynamodb component
			$dynamodb_component = new DynamoDb\Component($f3->get(AWSKEY), $f3->get(AWSSECRET));
			// let's get the dynamodb connection client
			$client = $dynamodb_component->getClient();
			// let's get the marshaller going for item formatting/manipulation
			$marshaller = $dynamodb_component->getMarshaller();

			// let's instantiate our redshift component and fetch metadata from Redshift
			$redshift_component = new Redshift\Component($f3->get(REDSHIFTENDPOINT), $f3->get(REDSHIFTUSER), $f3->get(REDSHIFTPASS));
			$rows = $redshift_component->getRows($params['schema'],$params['table']);
			$columns = $redshift_component->getColumns($params['schema'],$params['table']);
			$latest_update = $redshift_component->getLatestUpdate($params['schema'],$params['table']);
			$sample = $redshift_component->getSample($params['schema'],$params['table']);

			// let's format redshift metadata
			$formatted_statistics = $formatter_component->formatStatisticsData($rows, $columns, $latest_update);
			$formatted_columns = $formatter_component->formatColumnsData($columns);
			$formatted_sample = $formatter_component->formatSampleData($sample, $formatted_columns);
			$new_item = $dynamodb_component->formatNewItem(
				$marshaller, 
				$params, 
				$formatted_statistics, 
				$formatted_columns, 
				$formatted_sample
			);

			// let's fetch cached metadata from dynamodb
			$cached_item = $dynamodb_component->fetchCachedItem($params);

			// let's merge new with cached metadata
			$updated_item = $dynamodb_component->formatItemToUpdate($marshaller, $new_item, $cached_item);

			// let's update, if table has columns
			if(count($columns)){
				$result = $client->putItem(array('TableName' => "dwh_dictionary", 'Item' => $updated_item));
			}
			
			return $updated_item;
		}
	}

 ?>