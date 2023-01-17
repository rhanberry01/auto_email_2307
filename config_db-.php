<?php

/*Connection Information for the database
- $def_coy is the default company that is pre-selected on login

- host is the computer ip address or name where the database is the default is 192.168.0.148 assuming that the web server is also the sql server

- user is the user name under which the database should be accessed - need to change to the mysql (or other DB) user set up for purpose
  NB it is not secure to use root as the user with no password - a user with appropriate privileges must be set up

- password is the password the user of the database requires to be sent to authorise the above database user

- DatabaseName is the name of the database as defined in the RDMS being used. Typically RDMS allow many databases to be maintained under the same server.
  The scripts for MySQL provided use the name logicworks */


$def_coy = 0;

$tb_pref_counter = 1;

$db_connections = array (
	0 => array ('name' => 'San Roque Supermarket - MAIN',
		'host' => '192.168.0.148',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_nova_test',
		'tbpref' => '0_',
		'srs_branch' => 'nova',
		'ms_host' => '192.168.0.148',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'NOVA_JADE'),


	1 => array ('name' => 'San Roque Supermarket - NAVOTAS',
			'host' => '192.168.0.148',
			'dbuser' => 'root',
			'dbpassword' => 'srsnova',
			'dbname' => 'srs_aria_nova_test',
			'tbpref' => '0_',
			'srs_branch' => 'navotas',
			'ms_host' => '192.168.0.148',
			'ms_dbuser' => 'markuser',
			'ms_dbpassword' => 'tseug',
			'ms_dbname' => 'NOVA_JADE')
	);

?>