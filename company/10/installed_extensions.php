<?php

/*
	Do not edit this file manually. This copy of global file is overwritten
	by extensions editor.
*/

$installed_extensions = array (
	1 => array ( 'tab' => 'GL',
			'name' => 'Checking Accounts',
			'path' => 'checkprint',
			'title' => 'Checking Accounts',
			'active' => '1',
			'type' => 'plugin',
			'filename' => 'check_accounts.php',
			'acc_file' => 'acc_levels.php',
			'access' => 'SA_CHECKPRINTSETUP',
		),
	2 => array ( 'tab' => 'GL',
			'name' => 'Check Voucher',
			'path' => 'checkprint',
			'title' => 'Check Voucher',
			'active' => '1',
			'type' => 'plugin',
			'filename' => 'check_list_201.php',
			'acc_file' => 'acc_levels.php',
			'access' => 'SA_CHECKPRINT',
		),
	5 => array ( 'tab' => 'GL',
			'name' => 'Cost Accruals',
			'path' => 'accruals',
			'title' => 'Cost Accruals',
			'active' => '1',
			'type' => 'plugin',
			'filename' => 'accruals.php',
			'acc_file' => 'acc_levels.php',
			'access' => 'SA_ACCRUALS',
		),
	);
?>