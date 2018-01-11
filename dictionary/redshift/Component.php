<?php 

	namespace Dictionary\Redshift;

	use PDO;



	class Component
	{

		private $_client;

		function __construct($endpoint, $user, $pass){
			$this->_client = new PDO(
				    $endpoint,
				    $user, 
				    $pass
				);
			return $this;
		}

		public function getSchemas(){
			$query = "select * from pg_namespace where nspowner = '" . NSPOWNER . "';";
			return $this->commitQuery($query);
		}
		
		public function getTables($schema){
			$query = "select distinct tablename from pg_table_def where schemaname = '" . $schema . "';";
			return $this->commitQuery($query,$schema);
		}

		public function getColumns($schema,$table){
			$query = "select * from pg_table_def where tablename = '" . $table . "';";
			return $this->commitQuery($query,$schema,$table);
		}

		public function getRows($schema,$table){
			$query = 'select count(*) from ' . $schema . '.' . $table . ';';
			$rows = $this->commitQuery($query,$schema,$table);
			return (isset($rows[0][0]) ? $rows[0][0] : 0);
		}

		public function getLatestUpdate($schema,$table){
			$query = 'select refresh_id from ' . $schema . '.' . $table . ' order by refresh_id DESC LIMIT 1;';
			$latest_update = $this->commitQuery($query,$schema,$table);
			foreach ($latest_update as $refresh_id) {
				if(isset($refresh_id['refresh_id'])){
					$latest_update = date('Y-m-d', strtotime($refresh_id['refresh_id']));
				}
				break;
			}
			return $latest_update;
		}

		public function getSample($schema, $table = null){
			$query = 'select * from ' . $schema . '.' . $table . ' order by refresh_id DESC LIMIT 10;';
			$sample = $this->commitQuery($query,$schema,$table);
			if(count($sample) == 0){
				$query = 'select * from ' . $schema . '.' . $table . ' LIMIT 10;';
				$sample = $this->commitQuery($query,$schema,$table);
			}
			return $sample;
		}

		public function getClient(){
			return $this->_client;
		}

		public function commitQuery($query,$schema = null,$table = null){
			try {
				$client = $this->getClient();
				if($schema != null){
					$client->exec('set search_path to ' . $schema .';');
				}
				$statement = $client->prepare($query);
				$statement->execute();
				$result = $statement->fetchAll();
				return $result;
			} catch (Exception $e) {
				return $e->getMessage();
			}
	}
}
?>