<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$path_to_root="..";
$page_security = 'SA_OPEN';
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/reporting/includes/reports_classes.inc");
$js = "";
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Reports and Analysis"), false, false, "", $js);

$reports = new BoxReports;

$dim = get_company_pref('use_dimension');

$reports->addReportClass(_('Customer'));
$reports->addReport(_('Customer'),101,_('Customer &Balances'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Currency Filter') => 'CURRENCY',
			_('Zero values') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Customer'),102,_('&Aged Customer Analysis'),
	array(	_('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Currency Filter') => 'CURRENCY',
			_('Summary Only') => 'YES_NO',
				_('Zero values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Customer'),115,_('&Overdue Customer Analysis'),
	array(	_('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Currency Filter') => 'CURRENCY',
			_('Summary Only') => 'YES_NO',
				_('Zero values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Customer'),103,_('Customer &Detail Listing'),
	array(	_('Activity Since') => 'DATEBEGIN',
			_('Sales Areas') => 'AREAS',
			_('Sales Folk') => 'SALESMEN',
			_('Activity Greater Than') => 'TEXT',
			_('Activity Less Than') => 'TEXT',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Customer'),104,_('&Price Listing'),
	array(	_('Currency Filter') => 'CURRENCY',
			_('Inventory Category') => 'CATEGORIES',
			_('Sales Types') => 'SALESTYPES',
			_('Show Pictures') => 'YES_NO',
			_('Show GP %') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Customer'),105,_('&Order Status Listing'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Stock Location') => 'LOCATIONS',
			_('Back Orders Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Customer'),106,_('&Sales Report per Salesman'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Customer'),107,_('Print &Invoices'),
	array(	_('From') => 'INVOICE',
			_('To') => 'INVOICE',
			_('Currency Filter') => 'CURRENCY',
			_('email Customers') => 'YES_NO',
			_('Payment Link') => 'PAYMENT_LINK',
			_('Comments') => 'TEXTBOX'));
$reports->addReport(_('Customer'),110,_('Print &Deliveries'),
	array(	_('From') => 'DELIVERY',
			_('To') => 'DELIVERY',
			_('email Customers') => 'YES_NO',
			_('Print as Packing Slip') => 'YES_NO',
			_('Comments') => 'TEXTBOX'));
/*$reports->addReport(_('Customer'),108,_('Print &Statement of Account'),
	array(	_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Currency Filter') => 'CURRENCY',
			_('Email Customers') => 'YES_NO',
			_('Comments') => 'TEXTBOX'));*/
$reports->addReport(_('Customer'),109,_('&Print Sales Orders'),
	array(	_('From') => 'ORDERS',
			_('To') => 'ORDERS',
			_('Currency Filter') => 'CURRENCY',
			_('Email Customers') => 'YES_NO',
			_('Print as Quote') => 'YES_NO',
			_('Comments') => 'TEXTBOX'));
// $reports->addReport(_('Customer'),111,_('&Print Sales Quotations'),
	// array(	_('From') => 'QUOTATIONS',
			// _('To') => 'QUOTATIONS',
			// _('Currency Filter') => 'CURRENCY',
			// _('Email Customers') => 'YES_NO',
			// _('Comments') => 'TEXTBOX'));
// $reports->addReport(_('Customer'),111,_('&Print Sales Quotations'),
	// array(	_('From') => 'QUOTATIONS',
			// _('To') => 'QUOTATIONS',
			// _('Currency Filter') => 'CURRENCY',
			// _('Email Customers') => 'YES_NO',
			// _('Comments') => 'TEXTBOX'));
$reports->addReport(_('Customer'),112,_('Print Receipts'),
	array(	_('From') => 'RECEIPT',
			_('To') => 'RECEIPT',
			_('Currency Filter') => 'CURRENCY',
			_('Comments') => 'TEXTBOX'));
$reports->addReport(_('Customer'),113,_('Sales Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Area') => 'AREAS',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));			
$reports->addReport(_('Customer'),114,_('Sales Report per Item'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Items') => 'ITEMS_',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Customer'),215,_('PDC Report for Collection'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('OR #') => 'TEXT',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));


$reports->addReportClass(_('Supplier'));
// $reports->addReport(_('Supplier'),201,_('Supplier Statements'),
	// array(	_('Start Date') => 'DATEBEGIN',
			// _('End Date') => 'DATEEND',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('Currency Filter') => 'CURRENCY',
					// _('Zero values') => 'YES_NO',
										// _('Sort By') => 'SORT_ORDER',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// $reports->addReport(_('Supplier'),202,_('&Aged Supplier Analyses'),
	// array(	_('End Date') => 'DATE',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('Currency Filter') => 'CURRENCY',
			// _('Summary Only') => 'YES_NO',
					// _('Zero values') => 'YES_NO',

			// _('Graphics') => 'GRAPHIC',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// $reports->addReport(_('Supplier'),207,_('&Overdue Supplier Analysis'),
	// array(	_('End Date') => 'DATE',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('Currency Filter') => 'CURRENCY',
			// _('Summary Only') => 'YES_NO',
					// _('Zero values') => 'YES_NO',
			// _('Graphics') => 'GRAPHIC',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// $reports->addReport(_('Supplier'),203,_('&Payment Report'),
	// array(	_('End Date') => 'DATE',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('Currency Filter') => 'CURRENCY',
			// _('Zero values') => 'YES_NO',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// $reports->addReport(_('Supplier'),204,_('Items Received Report'),
	// array(	_('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// $reports->addReport(_('Supplier'),205,_('Outstanding PO Report'),
	// array(	_('End Date') => 'DATEENDM',
			// _('Item Code') => 'ITEMS2',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// $reports->addReport(_('Supplier'),206,_('PO Summary Report'),
	// array(	_('End Date') => 'DATEENDM',
			// _('Item Code') => 'ITEMS2',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// $reports->addReport(_('Supplier'),209,_('Print Purchase &Orders'),
	// array(	_('From') => 'PO',
			// _('To') => 'PO',
			// _('Currency Filter') => 'CURRENCY',
			// _('Email Customers') => 'YES_NO',
			// _('Comments') => 'TEXTBOX'));
// $reports->addReport(_('Supplier'),213,_('Print Receiving Report'),
	// array(	_('From') => 'GRN',
			// _('To') => 'GRN',
			// _('Email Customers') => 'YES_NO',
			// _('Comments') => 'TEXTBOX'));
// $reports->addReport(_('Supplier'),210,_('Print Payments'),
	// array(	_('From') => 'REMITTANCE',
			// _('To') => 'REMITTANCE',
			// _('Currency Filter') => 'CURRENCY',
			// _('Email Customers') => 'YES_NO',
			// _('Comments') => 'TEXTBOX'));
// $reports->addReport(_('Supplier'),214,_('PDC Report for Payments'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('OR #') => 'TEXT',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
			
$reports->addReport(_('Supplier'),727,_('Supplier APV List'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Order by APV#') => 'YES_NO',
			_('APV#') => 'TEXT',
			_('PO#') => 'TEXT',
			_('Receiving#') => 'TEXT',
			_('Invoice#') => 'TEXT',
			_('Destination') => 'DESTINATION'));	
			
$reports->addReport(_('Supplier'),728,_('Receiving Summary'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Group by supplier') => 'YES_NO',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('Receiving#') => 'TEXT',
			// _('Invoice#') => 'TEXT',
			_('Destination') => 'DESTINATION'));	
			
$reports->addReport(_('Supplier'),221,_('VAT / NON_VAT Supplier List'),
	array(_('VAT / NON-VAT') => 'V_NV',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('Supplier'),729,_('Consigment Summary'),
			array(_('Select Year') => 'YEARS',
			_('Select Month') => 'MONTHS',
			_('Destination') => 'DESTINATION'));	
			
$reports->addReport(_('Supplier'),730,_('Consigment Summary w/ Vat Non-Vat'),
			array(_('Select Year') => 'YEARS',
			_('Select Month') => 'MONTHS',
			_('VAT / NON-VAT') => 'V_NV',
			_('Destination') => 'DESTINATION'));	
			
$reports->addReport(_('Supplier'),219,_('Supplier Debit Memos'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Status') => 'DM_STAT',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('Supplier'),2199,_('Pending Supplier Debit Memos from 2015 to Present'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Destination') => 'DESTINATION',
			_('Group by supplier') => 'YES_NO'
			));
			
$reports->addReport(_('Supplier'),21992,_('Pending Supplier APV from 2015 to Present'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Destination') => 'DESTINATION',
			_('Group by supplier') => 'YES_NO',
			_('Status') => 'DM_STAT'
			));
			
$reports->addReport(_('Supplier'),2192,_('Supplier Debit Memo 2'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			//_('Status') => 'DM_STAT',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('Supplier'),2193,_('------'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			//_('Status') => 'DM_STAT',
			_('Destination') => 'DESTINATION'));


$reports->addReport(_('Supplier'),220,_('Itemized Receiving Report (MS)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));

$reports->addReportClass(_('Inventory'));
$reports->addReport(_('Inventory'),301,_('Inventory &Valuation Report'),
	array(	_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Inventory'),302,_('Inventory &Planning Report'),
	array(	_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Inventory'),303,_('Stock &Check Sheets'),
	array(	_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			_('Show Pictures') => 'YES_NO',
			_('Inventory Column') => 'YES_NO',
			_('Show Shortage') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Inventory'),304,_('Inventory &Sales Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Inventory'),305,_('&GRN Valuation Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));

$reports->addReportClass(_('Manufacturing'));
$reports->addReport(_('Manufacturing'),401,_('&Bill of Material Listing'),
	array(	_('From product') => 'ITEMS',
			_('To product') => 'ITEMS',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('Manufacturing'),409,_('Print &Work Orders'),
	array(	_('From') => 'WORKORDER',
			_('To') => 'WORKORDER',
			_('Email Locations') => 'YES_NO',
			_('Comments') => 'TEXTBOX'));
$reports->addReportClass(_('Dimensions'));
if ($dim > 0)
{
	$reports->addReport(_('Dimensions'),501,_('Dimension &Summary'),
	array(	_('From Dimension') => 'DIMENSION',
			_('To Dimension') => 'DIMENSION',
			_('Show Balance') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
	//$reports->addReport(_('Dimensions'),502,_('Dimension Details'),
	//array(	_('Dimension'),'DIMENSIONS'),
	//		_('Comments'),'TEXTBOX')));
}
	// $reports->addReportClass(_('Banking'));
		// $reports->addReport(_('Banking'),601,_('Bank &Statement'),
		// array(	_('Bank Accounts') => 'BANK_ACCOUNTS',
				// _('Start Date') => 'DATEBEGINM',
				// _('End Date') => 'DATEENDM',
				// _('Comments') => 'TEXTBOX',
				// _('Destination') => 'DESTINATION'));
			
			
	/*$reports->addReport(_('Banking'),712278,_('AUB Bank Recon (Check Payment)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			
			
	$reports->addReport(_('Banking'),712280,_('AUB Bank Recon (Deposit)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			
			
			
	
			
			
	$reports->addReport(_('Banking'),712279,_('METROBANK Bank Recon (Online Payment)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			
			
	$reports->addReport(_('Banking'),712281,_('METROBANK Bank Recon (Deposit)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));*/
			
	$reports->addReport(_('Banking'),712284,_('BPI Bank Recon (Check Payment)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			
			
	// $reports->addReport(_('Banking'),712282,_('BPI Bank Recon (Credit/Debit Card)'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Trade or Non-Trade') => 'T_NT_A',
			// _('Retail / Belen Tan') => 'R_B_A'
			// ));			
			
	$reports->addReport(_('Banking'),712293,_('BPI Bank Recon (Credit/Debit Card)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));		

			
	$reports->addReport(_('Banking'),712285,_('BDO Bank Recon (Check Payment)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			
	$reports->addReport(_('Banking'),712286,_('BDO Bank Recon (Deposit)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			

	/*$reports->addReport(_('Banking'),712287,_('DIFF CHECKER AUB Bank Recon (Deposit)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));*/
			
			
	$reports->addReport(_('Banking'),712288,_('Bank Recon Deposit Summary'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A',
			_('Bank Accounts') => 'BANK_ACCOUNT'
			
			));

			
	$reports->addReport(_('Banking'),712289,_('Bank Recon Deposit Details'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A',
			_('Bank Accounts') => 'BANK_ACCOUNT'
			));
			
	$reports->addReport(_('Banking'),712291,_('Bank Recon Disbursement Summary'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A',
			_('Bank Accounts') => 'BANK_ACCOUNT'
			));
			
	$reports->addReport(_('Banking'),71290,_('Bank Recon Disbursement Details'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A',
			_('Bank Accounts') => 'BANK_ACCOUNT'
			));

	$reports->addReport(_('Banking'),71294,_('Bank Reconciliation Summary'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A',
			_('Bank Accounts') => 'BANK_ACCOUNT'
			));

			
// $reports->addReport(_('Banking'),712283,_('METROBANK Bank Recon (Credit/Debit Card)'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Trade or Non-Trade') => 'T_NT_A',
			// _('Retail / Belen Tan') => 'R_B_A'
			// ));
			
$reports->addReportClass(_('General Ledger'));
$reports->addReport(_('General Ledger'),701,_('Chart of &Accounts'),
	array(	_('Show Balances') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),702,_('List of &Journal Entries'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Type') => 'SYS_TYPES',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
//$reports->addReport(_('General Ledger'),703,_('GL Account Group Summary'),
//	array(	_('Comments'),'TEXTBOX')));

// if ($dim == 2)
// {
	// $reports->addReport(_('General Ledger'),704,_('GL Account &Transactions'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('From Account') => 'GL_ACCOUNTS',
			// _('To Account') => 'GL_ACCOUNTS',
			// _('Dimension')." 1" =>  'DIMENSIONS1',
			// _('Dimension')." 2" =>  'DIMENSIONS2',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
	// $reports->addReport(_('General Ledger'),705,_('Annual &Expense Breakdown'),
	// array(	_('Year') => 'TRANS_YEARS',
			// _('Dimension')." 1" =>  'DIMENSIONS1',
			// _('Dimension')." 2" =>  'DIMENSIONS2',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
	// $reports->addReport(_('General Ledger'),706,_('&Balance Sheet'),
	// array(	_('Start Date') => 'DATEBEGIN',
			// _('End Date') => 'DATEENDM',
			// _('Dimension')." 1" => 'DIMENSIONS1',
			// _('Dimension')." 2" => 'DIMENSIONS2',
			// _('Decimal values') => 'YES_NO',
			// _('Graphics') => 'GRAPHIC',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
	// $reports->addReport(_('General Ledger'),707,_('&Profit and Loss Statement'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Compare to') => 'COMPARE',
			// _('Dimension')." 1" =>  'DIMENSIONS1',
			// _('Dimension')." 2" =>  'DIMENSIONS2',
			// _('Decimal values') => 'YES_NO',
			// _('Graphics') => 'GRAPHIC',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
	// $reports->addReport(_('General Ledger'),708,_('Trial &Balance'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Zero values') => 'YES_NO',
			// _('Only balances') => 'YES_NO',
			// _('Dimension')." 1" =>  'DIMENSIONS1',
			// _('Dimension')." 2" =>  'DIMENSIONS2',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// }
// else if ($dim == 1)
// {
	// $reports->addReport(_('General Ledger'),704,_('GL Account &Transactions'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('From Account') => 'GL_ACCOUNTS',
			// _('To Account') => 'GL_ACCOUNTS',
			// _('Dimension') =>  'DIMENSIONS1',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
	// $reports->addReport(_('General Ledger'),705,_('Annual &Expense Breakdown'),
	// array(	_('Year') => 'TRANS_YEARS',
			// _('Dimension') =>  'DIMENSIONS1',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
	// $reports->addReport(_('General Ledger'),706,_('&Balance Sheet'),
	// array(	_('Start Date') => 'DATEBEGIN',
			// _('End Date') => 'DATEENDM',
			// _('Dimension') => 'DIMENSIONS1',
			// _('Decimal values') => 'YES_NO',
			// _('Graphics') => 'GRAPHIC',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
	// $reports->addReport(_('General Ledger'),707,_('&Profit and Loss Statement'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Compare to') => 'COMPARE',
			// _('Dimension') => 'DIMENSIONS1',
			// _('Decimal values') => 'YES_NO',
			// _('Graphics') => 'GRAPHIC',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
	// $reports->addReport(_('General Ledger'),708,_('Trial &Balance'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Zero values') => 'YES_NO',
			// _('Only balances') => 'YES_NO',
			// _('Dimension') => 'DIMENSIONS1',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
// }
// else
// {
	$reports->addReport(_('General Ledger'),704,_('GL Account Transactions'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Account (*Ctrl+click to select multiple)') => 'GL_ACCOUNTS_MULTI',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Group per Person/Item') => 'YES_NO',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),7044,_('GL Account Transactions test'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Account (*Ctrl+click to select multiple)') => 'GL_ACCOUNTS_MULTI',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Group per Person/Item') => 'YES_NO',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),7045,_('DM Account Transactions'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Account (*Ctrl+click to select multiple)') => 'GL_ACCOUNTS_MULTI',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Group per Person/Item') => 'YES_NO',
			_('Destination') => 'DESTINATION'));
			
	$reports->addReport(_('General Ledger'),705,_('Annual Expense Breakdown'),
	array(	_('Year') => 'TRANS_YEARS',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
			
if ($dim == 1)
{
	$reports->addReport(_('General Ledger'),706,_('&Balance Sheet'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Dimension') => 'DIMENSIONS1',
			_('Decimal values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
}
else
{
	$reports->addReport(_('General Ledger'),706,_('&Balance Sheet'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Decimal values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
}

	$reports->addReport(_('General Ledger'),707,_('&Profit and Loss Statement'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			// _('Compare to') => 'COMPARE',
			// _('Decimal values') => 'YES_NO',
			_('Display Zero values') => 'YES_NO',
			// _('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
			
			
	if($_SESSION['wa_current_user']->username != 'audit') {
	$reports->addReport(_('General Ledger'),7073,_('&Profit and Loss Statement NEW'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			// _('Compare to') => 'COMPARE',
			// _('Decimal values') => 'YES_NO',
			_('Display Zero values') => 'YES_NO',
			// _('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
			}
			
			
			
	$reports->addReport(_('General Ledger'),7072,_('&Profit and Loss Comparison'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			// _('Compare to') => 'COMPARE',
			// _('Decimal values') => 'YES_NO',
			_('Display Zero values') => 'YES_NO',
			// _('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
			
if ($dim == 1)
{
	$reports->addReport(_('General Ledger'),708,_('Trial &Balance'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Zero values') => 'YES_NO',
			_('Only balances') => 'YES_NO',
			_('Dimension') => 'DIMENSIONS1',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
}
else
{
	$reports->addReport(_('General Ledger'),708,_('Trial &Balance'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Zero values') => 'YES_NO',
			_('Only balances') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
}
// }
$reports->addReport(_('General Ledger'),709,_('Ta&x Report'),
	array(	_('Start Date') => 'DATEBEGINTAX',
			_('End Date') => 'DATEENDTAX',
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
$reports->addReport(_('General Ledger'),710,_('Audit Trail'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Type') => 'SYS_TYPES_ALL',
			_('User') => 'USERS',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),713,_('RR, APV and CV Monitoring'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			// _('Type') => 'SYS_TYPES_ALL',
			_('User') => 'USERS',
			_('Comments') => 'TEXTBOX',
			// // _('Destination') => 'DESTINATION'
			));

$reports->addReport(_('General Ledger'),711,_('APV Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT'
			));
			
$reports->addReport(_('General Ledger'),712,_('CV Register'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			
$reports->addReport(_('General Ledger'),7122,_('Check Register'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));

$reports->addReport(_('General Ledger'),7123,_('Check Register BDO'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));


$reports->addReport(_('General Ledger'),7141,_('New Purchase Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));

$reports->addReport(_('General Ledger'),7143,_('Consolidated Purchase Report (Detailed)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),7144,_('Consolidated Purchase Report (Summarized)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));			
			
$reports->addReport(_('General Ledger'),7146,_('VAT Relief (Purchases)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),7147,_('VAT Relief (Sales)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));			
			
$reports->addReport(_('General Ledger'),716,_('PAID ONLINE CV Details'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_MULTI',
			_('Destination') => 'DESTINATION'));

$reports->addReport(_('General Ledger'),717,_('Unpaid Sales Invoice'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),720,_('Unpaid Sales Invoice (up to given date)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));
		
$reports->addReport(_('General Ledger'),718,_('Supplier Transactions'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('With/Without CV') => 'DM_STAT',
			_('Only balances') => 'YES_NO',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Destination') => 'DESTINATION'));

$reports->addReport(_('General Ledger'),719,_('EWT Payable Expenses'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Account (*Ctrl+click to select multiple)') => 'GL_ACCOUNTS_MULTI',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),721,_('Daily GP (item with difference)'),
	array(	_('Date') => 'DATE',
				_('Net of VAT') => 'YES_NO',
				_('Destination') => 'DESTINATION'));

$reports->addReport(_('General Ledger'),722,_('Daily GP (total)'),
	array(	_('Date') => 'DATE',
				_('Net of VAT') => 'YES_NO',
				_('Destination') => 'DESTINATION'));
				
$reports->addReport(_('General Ledger'),723,_('Monthly Movement Discrepancy'),
	array(	_('Start Date') => 'DATEBEGINM',
				_('End Date') => 'DATEENDM',
				_('Destination') => 'DESTINATION'));
				
$reports->addReport(_('General Ledger'),7212,_('Daily GP (item with difference) + per qty'),
	array(	_('Date') => 'DATE',
				_('Net of VAT') => 'YES_NO',
				_('Destination') => 'DESTINATION'));

$reports->addReport(_('General Ledger'),7222,_('Daily GP (total) + per qty'),
	array(	_('Date') => 'DATE',
				_('Net of VAT') => 'YES_NO',
				_('Destination') => 'DESTINATION'));
				
$reports->addReport(_('General Ledger'),731,_('Accounts Payable - Aging Reports'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),7312,_('Accounts Payable - Aging (w/ all transaction type) Reports'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),7315,_('Accounts Payable - Aging (w/ debit memo) Reports'),
array(	_('Start Date') => 'DATEBEGINM',
_('End Date') => 'DATEENDM',
_('Destination') => 'DESTINATION',
_('Trade or Non-Trade') => 'T_NT_A'));
			
			
$reports->addReport(_('General Ledger'),733,_('short'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));
			
	$reports->addReport(_('General Ledger'),7041,_('Advances to supplier'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Account (*Ctrl+click to select multiple)') => 'GL_ACCOUNTS_MULTI',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Group per Person/Item') => 'YES_NO',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),71224,_('Suki Points Comparison'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Group per Person/Item') => 'YES_NO',
			_('Destination') => 'DESTINATION'));
			
// $reports->addReportClass(_('BIR Reports'));
// $reports->addReport(_('BIR Reports'),71222,_('Cash Disbursement Book'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Trade or Non-Trade') => 'T_NT_A',
			// _('Retail / Belen Tan') => 'R_B_A'
			// ));

$reports->addReport(_('BIR Reports'),71223,_('Journal Book'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));

$reports->addReport(_('BIR Reports'),71225,_('General Ledger'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Zero values') => 'YES_NO',
			_('Only balances') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));	
			
// $reports->addReport(_('BIR Reports'),71226,_('Purchase Book'),
		// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Destination') => 'DESTINATION'));
			
$reports->addReport(_('BIR Reports'),71227,_('Cash Receipts Book'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			
$reports->addReport(_('BIR Reports'),712262,_('Purchase Book'),
		array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('BIR Reports'),712272,_('Cash Disbursement Book'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A',
			_('Retail / Belen Tan') => 'R_B_A'
			));
			
$reports->addReport(_('BIR Reports'),712273,_('Accounts Payable Book'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A'
			));
			
$reports->addReport(_('BIR Reports'),712275,_('Debit Memo Book'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A'
			));
			
$reports->addReport(_('General Ledger'),7040,_('Operating Expense'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Account (*Ctrl+click to select multiple)') => 'GL_ACCOUNTS_MULTI',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Group per Person/Item') => 'YES_NO',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),7042,_('OpEx'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Destination') => 'DESTINATION'));
			
$reports->addReport(_('General Ledger'),70402,_('Promo Fund'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Account (*Ctrl+click to select multiple)') => 'GL_ACCOUNTS_MULTI',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Group per Person/Item') => 'YES_NO',
			_('Destination') => 'DESTINATION'));


/*$reports->addReport(_('General Ledger'),70403,_('Promo Fund Bundling'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Destination') => 'DESTINATION'));*/

$reports->addReport(_('General Ledger'),70404,_('Summary of Input Tax'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Input Tax') => 'INPUT_TAX_LIST',
			_('Trade / Non-Trade') => 'T_NT_A',
			_('Group per Person/Item') => 'YES_NO',
			_('Destination') => 'DESTINATION'));	

$reports->addReport(_('General Ledger'),70405,_('Schedule Of Accounts - NT'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Destination') => 'DESTINATION'));		

$reports->addReport(_('General Ledger'),70406,_('Advances To Supplier'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Destination') => 'DESTINATION'));		
			
			
$reports->addReport(_('BIR Reports'),712292,_('Trial Balance Details'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Trade or Non-Trade') => 'T_NT_A'
			));
			
			
$reports->addReport(_('General Ledger'),712294,_('test'),
array(	_('Start Date') => 'DATEBEGINM',
_('End Date') => 'DATEENDM',
_('Destination') => 'DESTINATION',
_('Trade or Non-Trade') => 'T_NT_A'));
// $reports->addReportClass(_('BIR Reports'));			
// $reports->addReport(_('BIR Reports'),801,_('Sales Book'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Destination') => 'DESTINATION'));
			
// $reports->addReport(_('BIR Reports'),216,_('Cash Receipts'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));

// $reports->addReport(_('BIR Reports'),217,_('Purchase Book'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Supplier') => 'SUPPLIERS_NO_FILTER',
			// _('INV #') => 'TEXT',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));
			
// $reports->addReport(_('BIR Reports'),218,_('Cash Disbursement Book'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM',
			// _('Comments') => 'TEXTBOX',
			// _('Destination') => 'DESTINATION'));

// //BIR FORMS IN Q,M and E FORMAT


// $reports->addReport(_('BIR Reports'),'1601C',_('1601C'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM'));			
			
// $reports->addReport(_('BIR Reports'),'1601E',_('1601E'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM'));			
			
// $reports->addReport(_('BIR Reports'),'1702Q',_('1702Q'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM'));									
// $reports->addReport(_('BIR Reports'),'2307',_('2307'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM'));		
// $reports->addReport(_('BIR Reports'),'2550M',_('2550M'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM'));

// $reports->addReport(_('BIR Reports'),'2550Q',_('2550Q'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM'));

// $reports->addReport(_('BIR Reports'),'2551M',_('2551M'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM'));			


// $reports->addReport(_('BIR Reports'),'2551Q',_('2551Q'),
	// array(	_('Start Date') => 'DATEBEGINM',
			// _('End Date') => 'DATEENDM'));				
add_custom_reports($reports);

echo "<script language='javascript'>
		function onWindowLoad() {
			showClass(" . $_GET['Class'] . ")
		}
	Behaviour.addLoadEvent(onWindowLoad);
	</script>
";
echo $reports->getDisplay();

end_page();
?>