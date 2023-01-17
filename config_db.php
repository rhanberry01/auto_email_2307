<?php
$def_coy = 0;
$tb_pref_counter = 1;
$db_connections = array (
	0 => array ('name' => 'San Roque Supermarket - TONDO',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_tondo',
		'tbpref' => '0_',
		'srs_branch' => 'tondo',

		'ms_host' => '192.168.5.21',
		//'ms_host' => '192.168.5.21',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srpos',

		'rs_host' => '192.168.5.21', //change to ip of RS server
		//'rs_host' => '192.168.5.21', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMTON',
		'br_code' => 'srst',
		'br_code2'=>'srst',
		
		'dimension_ref'=>'011',

		'srs_branch_id' => '5'
		),			
		
	1 => array ('name' => 'San Roque Supermarket - GAGALANGIN',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_gala',
		'tbpref' => '0_',
		'srs_branch' => 'gagalangin',

		'ms_host' => '192.168.5.6',
		//'ms_host' => '192.168.5.6',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srgala',

		'rs_host' => '192.168.5.6', //change to ip of RS server
		//'rs_host' => '192.168.5.6', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMGAL',
		'br_code' => 'srsg',
		'br_code2' => 'srsg',
		
		'dimension_ref'=>'010',

		'srs_branch_id' => '6'
		),			
		
	2 => array ('name' => 'San Roque Supermarket - NAVOTAS',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_navotas',
		'tbpref' => '0_',
		'srs_branch' => 'navotas',

		'ms_host' => '192.168.107.100',
		//'ms_host' => '192.168.107.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srnav',

		'rs_host' => '192.168.107.100', //change to ip of RS server
		//'rs_host' => '192.168.107.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMNAVO',
		'br_code' => 'srsnav',
		'br_code2' => 'srsnav',
		
		'dimension_ref'=>'008',

		'srs_branch_id' => '4'
		),			
		
	3 => array ('name' => 'San Roque Supermarket - IMUS',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_imus',
		'tbpref' => '0_',
		'srs_branch' => 'imus',

		'ms_host' => '192.168.108.100',
		//'ms_host' => '192.168.108.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srimu',

		'rs_host' => '192.168.108.100', //change to ip of RS server
		//'rs_host' => '192.168.108.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMIMU',
		'br_code' => 'sri',
		'br_code2' => 'sri',
		
		'dimension_ref'=>'009',

		'srs_branch_id' => '12'
		),				

	4 => array ('name' => 'San Roque Supermarket - NOVA',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_nova',
		'tbpref' => '0_',
		'srs_branch' => 'nova',

		'ms_host' => '192.168.0.179',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srspos',
		
		'rs_host' => '192.168.0.91', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMNOVA',
		'br_code' => 'srsn',
		'br_code2' => 'srsn',
		
		'dimension_ref'=>'003',

		'srs_branch_id' => '1'
		),	
		
	5 => array ('name' => 'San Roque Supermarket - BLUM',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_blum',
		'tbpref' => '0_',
		'srs_branch' => 'blum',
		
		'ms_host' => '192.168.102.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'pos',	
		
		'ms_host' => '192.168.102.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'pos',

		'rs_host' => '192.168.102.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => '',
		'rs_dbname' => 'returned_merchandise',
		'rs_tbpref' => '0_',
		
		'db_133' => '',
		'br_code' => 'srsb',
		'br_code2' => 'srsb',
		
		'dimension_ref'=>'006',
		),			
		
	6 => array ('name' => 'San Roque Supermarket - MALABON',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_malabon',
		'tbpref' => '0_',
		'srs_branch' => 'malabon',

		'ms_host' => '192.168.101.100',
		//'ms_host' => '192.168.101.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srpos',

		'rs_host' => '192.168.101.100', //change to ip of RS server
		//'rs_host' => '192.168.101.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMMALA',
		'br_code' => 'srsm',
		'br_code2' => 'srsm',
		
		'resto_connection' => 14,
		
		'dimension_ref'=>'007',

		'srs_branch_id' => '2'
		),				
		
	7 => array ('name' => 'San Roque Supermarket - CAMARIN',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_camarin',
		'tbpref' => '0_',
		'srs_branch' => 'camarin',

		'ms_host' => '192.168.106.100',
		//'ms_host' => '192.168.106.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRCAMA',

		'rs_host' => '192.168.106.100', //change to ip of RS server
		//'rs_host' => '192.168.106.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMCAMA',
		'br_code' => 'srsc',
		'br_code2' => 'srsc',
		
		'dimension_ref'=>'005',

		'srs_branch_id' => '10'
		),			

	8 => array ('name' => 'San Roque Supermarket - RETAIL',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_retail',
		'tbpref' => '0_',
		'srs_branch' => 'retail',

		'ms_host' => '',
		'ms_dbuser' => '',
		'ms_dbpassword' => '',
		'ms_dbname' => '',
		
		'db_133' => '',
		'br_code' => 'srsret',
		'br_code2' => 'srsret',
		
		'dimension_ref'=>'000',

		'srs_branch_id' => '22'
		),	
			
	
	9 => array ('name' => 'SRS RETAIL - MANALO', //east mart
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_antipolo_manalo',
		'tbpref' => '0_',
		'srs_branch' => 'manalo',

		'ms_host' => '192.168.111.100',
		//'ms_host' => '192.168.111.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSMANT2EM',

		'rs_host' => '192.168.111.100', 
		//'rs_host' => '192.168.111.100', 
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		
		'db_133' => 'SRSMANT2EM',
		'br_code' => 'srsant2',
		'br_code2' => 'srsant2',
		
		'dimension_ref'=>'002',

		'srs_branch_id' => '14'
		),
	
	10 => array ('name' => 'SRS RETAIL - QUEZON', //gems
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_antipolo_quezon',
		'tbpref' => '0_',
		'srs_branch' => 'quezon',

		'ms_host' => '192.168.5.15',
		//'ms_host' => '192.168.5.15',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSANT1GF',

		'rs_host' => '192.168.5.15', //change to ip of RS server
		//'rs_host' => '192.168.5.15', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMANT1GF',
		'br_code' => 'srsant1',
		'br_code2' => 'srsant1',
		
		'dimension_ref'=>'001',

		'srs_branch_id' => '13'
		),
		
	11 => array ('name' => 'SRS RETAIL - CAINTA',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_cainta',
		'tbpref' => '0_',
		'srs_branch' => 'cainta',

		'ms_host' => '192.168.112.100',
		//'ms_host' => '192.168.112.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSMCAINTA',
		
		'rs_host' => '192.168.112.100', //change to ip of RS server
		//'rs_host' => '192.168.112.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMCAINTA',
		'br_code' => 'srscain',
		'br_code2' => 'srscain',
		
		'dimension_ref'=>'004',

		'srs_branch_id' => '15'
		),
	
	12 => array ('name' => 'SRS RETAIL - B SILANG',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_b_silang',
		'tbpref' => '0_',
		'srs_branch' => 'b silang',

		'ms_host' => '192.168.5.38',
		//'ms_host' => '192.168.5.38',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSBSL',
		
		'rs_host' => '192.168.5.38', //change to ip of RS server
		//'rs_host' => '192.168.5.38', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMBSL',
		'br_code' => 'srsbsl',
		'br_code2' => 'srsbsl',
		
		'dimension_ref'=>'013',

		'srs_branch_id' => '9'
		),

	13 => array ('name' => 'SRS RETAIL - VALENZUELA',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_valenzuela',
		'tbpref' => '0_',
		'srs_branch' => 'valenzuela',

		'ms_host' => '192.168.114.100',
		//'ms_host' => '192.168.114.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srsval',
		
		'rs_host' => '192.168.114.100', //change to ip of RS server
		//'rs_host' => '192.168.114.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMVAL',
		'br_code' => 'srsval',
		'br_code2' => 'srsval',
		
		'dimension_ref'=>'012',

		'srs_branch_id' => '17'
		),
	
	14 => array ('name' => 'SRS  - RESTO MALABON',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_malabon_rest',
		'tbpref' => '0_',
		'srs_branch' => 'resto malabon',

		'ms_host' => '192.168.101.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'RestoBMALA',
		
		
		'rs_host' => '192.168.101.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise_resto',
		'cr_dbname' => 'cashier_remittance_resto',
		'rs_tbpref' => '0_',
		
		'db_133' => 'RESTOMMALA',
		'br_code' => 'srsmr',
		'br_code2' => 'srsmr',
		
		'dimension_ref'=>'007a',

		'srs_branch_id' => '3'
		),
		
	15 => array ('name' => 'SRS RETAIL - PUNTURIN valenzuela',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_punturin_val',
		'tbpref' => '0_',
		'srs_branch' => 'punturin',

		'ms_host' => '192.168.115.100',
		//'ms_host' => '192.168.115.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSPUN',
		
		'rs_host' => '192.168.115.100', //change to ip of RS server
		//'rs_host' => '192.168.115.100', //change to ip of RS server
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMPUN',
		'br_code' => 'srspun',
		'br_code2' => 'srspun',
		
		'dimension_ref'=>'014',

		'srs_branch_id' => '18'
		),
		
	16 => array ('name' => 'SRS RETAIL - PATEROS',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_pateros',
		'tbpref' => '0_',
		'srs_branch' => 'pateros',

		'ms_host' => '192.168.116.100',
		//'ms_host' => '192.168.116.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSPAT',
		
		'rs_host' => '192.168.116.100',
		//'rs_host' => '192.168.116.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMPAT',
		'br_code' => 'srspat',
		'br_code2' => 'srspat',
		
		'dimension_ref'=>'015',

		'srs_branch_id' => '7'
		),	
		
	17 => array ('name' => 'SRS RETAIL - COMEMBO',
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_comembo',
		'tbpref' => '0_',
		'srs_branch' => 'comembo',

		'ms_host' => '192.168.117.100',
		//'ms_host' => '192.168.117.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'skum',
		
		'rs_host' => '192.168.117.100',
		//'rs_host' => '192.168.117.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMKUM',
		'br_code' => 'srscom',
		'br_code2' => 'srscom',
		
		'dimension_ref'=>'016',

		'srs_branch_id' => '8'
		),	
	
	18 => array ('name' => utf8_decode('SRS RETAIL - TALON UNO las piñas'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_talon_uno',
		'tbpref' => '0_',
		'srs_branch' => 'talon uno',

		'ms_host' => '192.168.132.100',
		//'ms_host' => '192.168.132.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSPINAS',
		
		'rs_host' => '192.168.132.100',
		//'rs_host' => '192.168.132.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMPINAS',
		'br_code' => 'srstu',
		'br_code2' => 'srstu',
		
		'dimension_ref'=>'020',

		'srs_branch_id' => '11'
		),	
	

	19 => array ('name' => utf8_decode('SRS RETAIL - CAINTA2 san juan'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_cainta_san_juan',
		'tbpref' => '0_',
		'srs_branch' => 'cainta2',
		
		'rs_host' => '192.168.118.100',
		//'rs_host' => '192.168.18.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		

		'ms_host' => '192.168.118.100',
		//'ms_host' => '192.168.18.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srscainta2',
		
		'db_133' => 'SRSMCAINTA2',
		'br_code' => 'srscain2',
		'br_code2' => 'srscain2',
		
		'dimension_ref'=>'017',

		'srs_branch_id' => '16'
		),		
	
	20 => array ('name' => utf8_decode('SRS RETAIL - SAN PEDRO'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_san_pedro',
		'tbpref' => '0_',
		'srs_branch' => 'san pedro',

		'ms_host' => '192.168.119.100',
		//'ms_host' => '192.168.5.13',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'srspedro',
		
		'rs_host' => '192.168.119.100',
		//'rs_host' => '192.168.5.13',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMPEDRO',
		'br_code' => 'srssanp',
		'br_code2' => 'srssanp',
		
		'dimension_ref'=>'018',

		'srs_branch_id' => '19'
		),	
	
	21 => array ('name' => utf8_decode('SRS RETAIL - ALAMINOS'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_alaminos',
		'tbpref' => '0_',
		'srs_branch' => 'alaminos',

		'ms_host' => '192.168.20.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSALAM',
		
		'rs_host' => '192.168.20.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMALAM',
		'br_code' => 'srsal',
		'br_code2' => 'srsal',
		
		'dimension_ref'=>'019',

		'srs_branch_id' => '20'
		),	
	
	22 => array ('name' => utf8_decode('SRS RETAIL - BAGUMBONG'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_bagumbong',
		'tbpref' => '0_',
		'srs_branch' => 'bagumbong',

		'ms_host' => '192.168.5.36',
		//'ms_host' => '192.168.5.36',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSBAG',
		
		
		'rs_host' => '192.168.5.36',
		//'rs_host' => '192.168.5.36',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMBAG',
		'br_code' => 'srsbgb',
		'br_code2' => 'srsbgb',
		
		'dimension_ref'=>'021',

		'srs_branch_id' => '21'
		),			
		
		
	23 => array ('name' => utf8_decode('SRS RETAIL - GRACEVILLE'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_graceville',
		'tbpref' => '0_',
		'srs_branch' => 'graceville',

		'ms_host' => '192.168.102.100',
		//'ms_host' => '192.168.102.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSMUZ',
		
		
		'rs_host' => '192.168.102.100',
		//'rs_host' => '192.168.102.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMMUZ',
		'br_code' => 'srsgv',
		'br_code2' => 'srsgv',
		
		'dimension_ref'=>'022',

		'srs_branch_id' => '23'
		),	
		
		
	24 => array ('name' => utf8_decode('SRS RETAIL - MOLINO'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_molino',
		'tbpref' => '0_',
		'srs_branch' => 'molino',

		'ms_host' => '192.168.122.100',
		//'ms_host' => '192.168.122.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSMOL',
		
		'rs_host' => '192.168.122.100',
		//'rs_host' => '192.168.122.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMMOL',
		'br_code' => 'srsmol',
		'br_code2' => 'srsmol',
		
		 'dimension_ref'=>'023',

		'srs_branch_id' => '24'
		),	
		
	25 => array ('name' => utf8_decode('SRS RETAIL - MANGGAHAN'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_manggahan',
		'tbpref' => '0_',
		'srs_branch' => 'manggahan',

		'ms_host' => '192.168.124.100',
		//'ms_host' => '192.168.124.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSBMANGA',
		
		
		'rs_host' => '192.168.124.100',
		//'rs_host' => '192.168.124.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		
		'db_133' => 'SRSMANGA',
		'br_code' => 'srsman',
		'br_code2' => 'srsman',
		
		 'dimension_ref'=>'024',

		'srs_branch_id' => '25'
		),	
		
	26 => array ('name' => utf8_decode('SRS RETAIL - MONTALBAN'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_montalban',
		'tbpref' => '0_',
		'srs_branch' => 'montalban',

		'ms_host' => '192.168.23.100',
		//'ms_host' => ''192.168.23.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSMONTB',
		
		'rs_host' => '192.168.23.100',
		//'rs_host' => ''192.168.23.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMMONTB',
		'br_code' => 'srsmon',
		'br_code2' => 'srsmon',
		
		 'dimension_ref'=>'025',

		'srs_branch_id' => '26'
		),	
		
	27 => array ('name' => utf8_decode('SRS RETAIL - TANZA'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_tanza',
		'tbpref' => '0_',
		'srs_branch' => 'tanza',

		 'ms_host' => '192.168.5.29',
		 'ms_dbuser' => 'markuser',
		 'ms_dbpassword' => 'tseug',
		 'ms_dbname' => 'SBTANZA',
		 
		'rs_host' => '192.168.5.29',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'cr_dbname' => 'cashier_remittance',
		'rs_dbname' => 'returned_merchandise',
		'rs_tbpref' => '0_',
		
		'db_133' => 'STANZA',
		'br_code' => 'srstanza',
		'br_code2' => 'srstanza',
		
		 'dimension_ref'=>'026',

		'srs_branch_id' => '27'
		),	

	28 => array ('name' => utf8_decode('SRS RETAIL - SAN ISIDRO'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_san_isidro',
		'tbpref' => '0_',
		'srs_branch' => 'san isidro',

		 'ms_host' => '192.168.5.5',
		// 'ms_host' => '192.168.5.5',
		 'ms_dbuser' => 'markuser',
		 'ms_dbpassword' => 'tseug',
		 'ms_dbname' => 'SBISIDRO',
		 
		'rs_host' => '192.168.5.5',
		//'rs_host' => '192.168.5.5',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'cr_dbname' => 'cashier_remittance',
		'rs_dbname' => 'returned_merchandise',
		'rs_tbpref' => '0_',
		
		'db_133' => 'ISIDRO',
		'br_code' => 'srsisidro',
		'br_code2' => 'srsisidro',
		
		 'dimension_ref'=>'027',

		 'srs_branch_id' => '28'
		),
	

	29 => array ('name' => utf8_decode('SRS RETAIL - MARILAO'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_marilao',
		'tbpref' => '0_',
		'srs_branch' => 'marilao',

		'ms_host' => '192.168.128.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SBMARILAO',

		'rs_host' => '192.168.128.100',
		//'rs_host' => '192.168.128.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SMARILAO',
		'br_code' => 'srsmar',
		'br_code2' => 'srsmar',
		
		 'dimension_ref'=>'028',

		'srs_branch_id' => '29'
		),


	30 => array ('name' => utf8_decode('SRS RETAIL - S PALAY'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_s_palay',
		'tbpref' => '0_',
		'srs_branch' => 's palay',

		'ms_host' => '192.168.5.30',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SBPALAY',
		
		'rs_host' => '192.168.5.30',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SPALAY',
		'br_code' => 'srspalay',
		'br_code2' => 'srspalay',
		
		 'dimension_ref'=>'029',

		'srs_branch_id' => '30'
		),

	31 => array ('name' => utf8_decode('SRS RETAIL - AGORA'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_agora',
		'tbpref' => '0_',
		'srs_branch' => 'agora',

		
		'ms_host' => '192.168.127.100',
		//'ms_host' => '192.168.127.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SBAGORA',

		
		'rs_host' => '192.168.127.100',
		//'rs_host' => '192.168.127.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SAGORA',
		'br_code' => 'srsagora',
		'br_code2' => 'srsagora',

		
		'dimension_ref'=>'030',

		'srs_branch_id' => '31'
		),

	32 => array ('name' => utf8_decode('SRS RETAIL - B SILANG 2 (PHASE 10)'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_b_silang_2',
		'tbpref' => '0_',
		'srs_branch' => 'b silang2',

		'ms_host' => '192.168.130.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SRSBSL2',
		
		'rs_host' => '192.168.130.100',
		//'rs_host' => '192.168.130.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SRSMBSL2',
		'br_code' => 'srsbsl2',
		'br_code2' => 'srsbsl2',
		
		 'dimension_ref'=>'031',

		'srs_branch_id' => '32'
		),

	33 => array ('name' => utf8_decode('SRS RETAIL - MARILAO (STA ROSA)'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_sta_rosa',
		'tbpref' => '0_',
		'srs_branch' => 'sta rosa',

		'ms_host' => '192.168.129.100',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SBMARILAO2',
		
		'rs_host' => '192.168.129.100',
		//'rs_host' => '192.168.129.100',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		
		'db_133' => 'SMARILAO2',
		'br_code' => 'srsstarosa',
		'br_code2' => 'srsstarosa',
		
		 'dimension_ref'=>'032',

		'srs_branch_id' => '33'
		),
	

	34 => array ('name' => utf8_decode('SRS RETAIL - BULACAN (STA MARIA)'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_sta_maria',
		'tbpref' => '0_',
		'srs_branch' => 'sta maria',

		'ms_host' => '192.168.5.26',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SBSTAMARIA',
		
		'rs_host' => '192.168.5.26',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		

		'db_133' => 'SSTAMARIA',
		'br_code' => 'srsstamaria',
		'br_code2' => 'srsstamaria',
		
		 'dimension_ref'=>'033',

		'srs_branch_id' => '34'
		),


		35 => array ('name' => utf8_decode('SRS RETAIL - ROSARIO'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_rosario',
		'tbpref' => '0_',
		'srs_branch' => 'rosario',

		'ms_host' => '192.168.5.45',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SBROSARIO',
		
		'rs_host' => '192.168.5.45',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		

		'db_133' => 'SROSARIO',
		'br_code' => 'srsrosario',
		'br_code2' => 'srsrosario',
		
		 'dimension_ref'=>'039',

		'srs_branch_id' => '40'
		),
		36 => array ('name' => utf8_decode('SRS RETAIL - BRIXTON'),
		'host' => '192.168.0.91',
		'dbuser' => 'root',
		'dbpassword' => 'srsnova',
		'dbname' => 'srs_aria_brixton',
		'tbpref' => '0_',
		'srs_branch' => 'brixton',

		'ms_host' => '192.168.5.42',
		'ms_dbuser' => 'markuser',
		'ms_dbpassword' => 'tseug',
		'ms_dbname' => 'SBBRIXTON',
		
		'rs_host' => '192.168.5.42',
		'rs_dbuser' => 'root',
		'rs_dbpassword' => 'srsnova',
		'rs_dbname' => 'returned_merchandise',
		'cr_dbname' => 'cashier_remittance',
		'rs_tbpref' => '0_',
		

		'db_133' => 'SBRIXTON',
		'br_code' => 'srsbrixton',
		'br_code2' => 'srsbrixton',
		
		 'dimension_ref'=>'040',

		'srs_branch_id' => '41'
		),


			
		
	);
?>