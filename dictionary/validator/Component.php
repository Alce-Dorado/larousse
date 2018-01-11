<?php

namespace Dictionary\Validator;

/**
* 
*/
class Component
{
	
	function __construct()
	{
		return $this;
	}

	public function validate($f3){
		$db = new \DB\Jig('data/');
		$db_mapper = new \DB\Jig\Mapper($db, 'users');
		$auth = new \Auth($db_mapper, array('id' => 'username', 'pw' => 'password'));
		if(!$auth->basic()){
			die('Request Access from IS.');
		}

		$f3->set(REDSHIFTUSER, '');
		$f3->set(REDSHIFTPASS, '');

		if($f3->get( 'SERVER.PHP_AUTH_USER') != ''){
			$f3->set(REDSHIFTUSER, $f3->get( 'SERVER.PHP_AUTH_USER'));
		}

		if($f3->get( 'SERVER.PHP_AUTH_USER') != ''){
			$f3->set(REDSHIFTPASS, $f3->get( 'SERVER.PHP_AUTH_PW'));
		}

		if(	($f3->get(AWSKEY) 	 		== "") ||
    		($f3->get(AWSSECRET) 		== "") ||
    		($f3->get(REDSHIFTUSER)		== "") ||
    		($f3->get(REDSHIFTPASS)		== "") ||
    		($f3->get(REDSHIFTENDPOINT)	== "")){
    		die("Update this environment's configuration file (config.cfg) with valid AWS, and Redshift credentials");
    	}
	}
}