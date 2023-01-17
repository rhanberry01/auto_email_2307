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
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Fix Bank Accounts"), false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
// if (isset($_GET['AddedID'])) {
	// $trans_no = $_GET['AddedID'];
	// $cvid = $_GET['CV_id'];
	// $trans_type = ST_CREDITDEBITDEPOSIT;
   	// display_notification_centered( _("Other Income has been fixed"));
	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	// br();
	// display_note(get_cv_view_str($cvid, _("View Transaction")));
   	// hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	// display_footer_exit();
// }

//----------------------------------------------------------------------------------------
if (isset($_POST['Fix'])){
	global $Refs;
	
	set_time_limit(0);
	
	begin_transaction();	
	
// $sql="SELECT * FROM 0_supp_trans AS st
// LEFT JOIN 0_bank_trans AS bt
// ON st.trans_no=bt.trans_no
// LEFT JOIN 0_cheque_details as cd
// ON cd.bank_trans_id=bt.id
// WHERE bt.trans_date>='2015-01-01' AND bt.trans_date<='2015-12-31'
// AND st.type='22'
//AND bt.type='22'
// AND MONTH(st.tran_date)!=MONTH(cd.chk_date)
// AND bt.amount!=0
// ";
	
//WHERE bt.trans_date>='2015-01-01' AND bt.trans_date<='2015-06-30'
//WHERE bt.trans_date>='2015-07-01' AND bt.trans_date<='2015-12-31'


// $sql="SELECT 'srs' as br_code, c.type, c.trans_no,  c.supplier_id, 
// c.tran_date,  b.trans_date, cd.chk_date, c.cv_id,
// a.cv_no,b.id as b_id,  cd.chk_number, cd.chk_amount,
// cd.deposit_date,'0000-00-00' as reconciled
// FROM 0_supp_trans as c
// LEFT JOIN 0_cv_header as a 
// ON c.cv_id=a.id 
// LEFT JOIN 0_bank_trans as b 
// ON a.bank_trans_id=b.id 
// LEFT JOIN 0_cheque_details as cd 
// ON b.id=cd.bank_trans_id
// LEFT JOIN 0_gl_trans as g
// ON g.type_no=c.trans_no
// WHERE b.amount!=0 
// AND a.amount!=0 
// AND c.type='22' 
// AND g.type='22'
// AND b.type='22'
// AND c.ov_amount!=0 
// AND cd.chk_date>='2015-01-01' AND cd.chk_date <='2015-12-31'
// AND g.account='10102299'
// AND cd.chk_number IN (
// 5212261,
// 5295707,
// 5295708,
// 5295709,
// 5295710,
// 5295711,
// 5295712,
// 5295713,
// 5295739,
// 5295745,
// 5295768,
// 5295770,
// 5295776,
// 5295783,
// 5295784,
// 5295787,
// 5295797,
// 5295798,
// 5295799,
// 5295800,
// 5295810,
// 5295812,
// 5295813,
// 5462514,
// 5462515,
// 5462520,
// 5462522,
// 5462534,
// 5462542,
// 5462549,
// 5462581,
// 5462589,
// 5462590,
// 5462591,
// 5462593,
// 5462595,
// 5462596,
// 5462597,
// 5462598,
// 5462599,
// 5462603,
// 5462604,
// 5462607,
// 5462608,
// 5462615,
// 5462621,
// 5462622,
// 5462626,
// 5462630,
// 5462643,
// 5462644,
// 5462646,
// 5462663,
// 5462675,
// 5464502,
// 5464503,
// 5464504,
// 5464507,
// 5464508,
// 5464509,
// 5464510,
// 5464512,
// 5464517,
// 5464519,
// 5464525,
// 5464526,
// 5464527,
// 5464528,
// 5464531,
// 5464532,
// 5464533,
// 5464534,
// 5464539,
// 5464555,
// 5464557,
// 5464558,
// 5464561,
// 5464562,
// 5464572,
// 5464573,
// 5464574,
// 5464578,
// 5464579,
// 5464580,
// 5464581,
// 5464593,
// 5464594,
// 5464597,
// 5464598,
// 5464599,
// 5464601,
// 5464602,
// 5464603,
// 5296323,
// 5296330,
// 5296333,
// 5296338,
// 5296343,
// 5296344,
// 5296364,
// 5296365,
// 5296384,
// 5296388,
// 5296389,
// 5296390,
// 5296396,
// 5296399,
// 5296406,
// 5296407,
// 5296415,
// 5296420,
// 5296421,
// 5296423,
// 5461813,
// 5461822,
// 5461825,
// 5461829,
// 5461835,
// 5461836,
// 5461838,
// 5461840,
// 5461841,
// 5461846,
// 5461847,
// 5461848,
// 5461852,
// 5461853,
// 5461855,
// 5461865,
// 5461873,
// 5461876,
// 5461877,
// 5461881,
// 5461894,
// 5461896,
// 5461897,
// 5461935,
// 5461938,
// 5461942,
// 5461949,
// 5461950,
// 5461951,
// 5461959,
// 5461961,
// 5461962,
// 5461968,
// 5461969,
// 5461972,
// 5461973,
// 5462021,
// 5462028,
// 5462029,
// 5462030,
// 5462058,
// 5462062,
// 5462063,
// 5462064,
// 5462065,
// 5462068,
// 5462070,
// 5462071,
// 5462079,
// 5462080,
// 5462081,
// 5462082,
// 5462097,
// 5462098,
// 5464719,
// 5464720,
// 5464721,
// 5464722,
// 5464723,
// 5464724,
// 5464726,
// 5464741,
// 5464744,
// 5464747,
// 5464748,
// 5464754,
// 5464761,
// 5464763,
// 5464764,
// 5464770,
// 5464771,
// 5464772,
// 5464773,
// 5464780,
// 5464781,
// 5464782,
// 5464784,
// 5464786,
// 5463479,
// 5295374,
// 5295375,
// 5295376,
// 5295377,
// 5295380,
// 5295381,
// 5295384,
// 5295385,
// 5295390,
// 5295391,
// 5295398,
// 5295409,
// 5295413,
// 5295420,
// 5295421,
// 5295427,
// 5295428,
// 5295429,
// 5295439,
// 5295443,
// 5295464,
// 5295466,
// 5295471,
// 5295480,
// 5295482,
// 5295484,
// 5295492,
// 5295494,
// 5295508,
// 5295509,
// 5295516,
// 5295522,
// 5295524,
// 5463408,
// 5463409,
// 5463414,
// 5463426,
// 5463427,
// 5463428,
// 5463431,
// 5463436,
// 5463437,
// 5463438,
// 5463441,
// 5463462,
// 5463464,
// 5463465,
// 5463472,
// 5463478,
// 5463480,
// 5463482,
// 5463485,
// 5463491,
// 5463500,
// 5463501,
// 5463504,
// 5463505,
// 5463506,
// 5463513,
// 5463514,
// 5463515,
// 5463524,
// 5463526,
// 5463527,
// 5463533,
// 5463536,
// 5463540,
// 5463541,
// 5465673,
// 5460249,
// 5460253,
// 5460258,
// 5460259,
// 5460261,
// 5460262,
// 5460263,
// 5460271,
// 5460293,
// 5460294,
// 5460299,
// 5460300,
// 5460301,
// 5460302,
// 5460314,
// 5460316,
// 5460343,
// 5460344,
// 5460360,
// 5460361,
// 5460372,
// 5460373,
// 5460377,
// 5460378,
// 5460379,
// 5463024,
// 5463025,
// 5463026,
// 5463027,
// 5463028,
// 5463034,
// 5463035,
// 5463036,
// 5463041,
// 5463044,
// 5463045,
// 5463052,
// 5463053,
// 5463060,
// 5463062,
// 5463063,
// 5463077,
// 5463079,
// 5463085,
// 5463086,
// 5463101,
// 5463103,
// 5463104,
// 5463105,
// 5463106,
// 5463107,
// 5463108,
// 5463109,
// 5463110,
// 5463162,
// 5463163,
// 5463164,
// 5463165,
// 5463166,
// 5463167,
// 5463170,
// 5463171,
// 5463177,
// 5463178,
// 5463179,
// 5463180,
// 5463181,
// 5463183,
// 5463187,
// 5463190,
// 5463191,
// 5463192,
// 5463197,
// 5460706,
// 5460721,
// 5460726,
// 5460751,
// 5460756,
// 5460757,
// 5460775,
// 5460797,
// 5460798,
// 5460799,
// 5460800,
// 5460801,
// 5460802,
// 5460814,
// 5460815,
// 5460817,
// 5460819,
// 5460820,
// 5460821,
// 5460823,
// 5460830,
// 5460831,
// 5460834,
// 5460865,
// 5460891,
// 5460892,
// 5460893,
// 5460894,
// 5460898,
// 5462304,
// 5462310,
// 5462328,
// 5462348,
// 5462349,
// 5462354,
// 5462355,
// 5462356,
// 5462358,
// 5462396,
// 5462397,
// 5462398,
// 5462400,
// 5462401,
// 5462410,
// 5462411,
// 5462412,
// 5462420,
// 5462424,
// 5462425,
// 5462429,
// 5462431,
// 5462433,
// 5462439,
// 5462440,
// 5462448,
// 5462454,
// 5462468,
// 5462471,
// 5462475,
// 5464304,
// 5464306,
// 5464312,
// 5464313,
// 5464319,
// 5464320,
// 5464322,
// 5464324,
// 5464328,
// 5464330,
// 5464447,
// 5464448,
// 5464452,
// 5464455,
// 5464457,
// 5464461,
// 5464463,
// 5464464,
// 5464484,
// 5464486,
// 5464487,
// 5464488,
// 5464489,
// 5464490,
// 5464491,
// 5464493,
// 5464497,
// 5465201,
// 5465202,
// 5465209,
// 5465211,
// 5465212,
// 5465213,
// 5465214,
// 5465219,
// 5465235,
// 5465237,
// 5465241,
// 5463717,
// 5460440,
// 5460451,
// 5460452,
// 5460453,
// 5460454,
// 5460455,
// 5460456,
// 5460463,
// 5460471,
// 5460479,
// 5460480,
// 5460500,
// 5460501,
// 5460516,
// 5460524,
// 5460528,
// 5460541,
// 5460542,
// 5460543,
// 5460546,
// 5460547,
// 5460548,
// 5460559,
// 5460560,
// 5460561,
// 5460563,
// 5460601,
// 5460602,
// 5460605,
// 5460606,
// 5460607,
// 5460621,
// 5460625,
// 5460626,
// 5460633,
// 5460634,
// 5460636,
// 5460644,
// 5460684,
// 5460687,
// 5460688,
// 5460693,
// 5460694,
// 5460698,
// 5460699,
// 5465838,
// 5463602,
// 5463604,
// 5463607,
// 5463609,
// 5463610,
// 5463620,
// 5463621,
// 5463622,
// 5463623,
// 5463624,
// 5463625,
// 5463628,
// 5463633,
// 5463634,
// 5463635,
// 5463637,
// 5463657,
// 5463658,
// 5463659,
// 5463661,
// 5463678,
// 5463679,
// 5463682,
// 5463685,
// 5463686,
// 5463697,
// 5463698,
// 5463699,
// 5463716,
// 5463718,
// 5463719,
// 5463720,
// 5463724,
// 5463725,
// 5463726,
// 5463735,
// 5463756,
// 5463759,
// 5463760,
// 5463761,
// 5463762,
// 5463763,
// 5463764,
// 5463765,
// 5463766,
// 5463770,
// 5463771,
// 5463777,
// 5463779,
// 5463780,
// 5463781,
// 5463782,
// 5463783,
// 5463784,
// 5463785,
// 5463786,
// 5460904,
// 5460910,
// 5460911,
// 5460912,
// 5460917,
// 5460923,
// 5460924,
// 5460943,
// 5460944,
// 5460945,
// 5460946,
// 5460947,
// 5460953,
// 5460957,
// 5460958,
// 5460959,
// 5460960,
// 5460967,
// 5460975,
// 5460978,
// 5460982,
// 5460994,
// 5460999,
// 5461002,
// 5461003,
// 5461012,
// 5461014,
// 5461043,
// 5461046,
// 5461047,
// 5461048,
// 5461051,
// 5461053,
// 5461063,
// 5461064,
// 5461068,
// 5461073,
// 5461082,
// 5461085,
// 5464108,
// 5464110,
// 5464112,
// 5464114,
// 5464116,
// 5464117,
// 5464118,
// 5464135,
// 5464136,
// 5464137,
// 5464143,
// 5464145,
// 5464146,
// 5464154,
// 5464155,
// 5464156,
// 5464157,
// 5464161,
// 5464165,
// 5464166,
// 5464173,
// 5464174,
// 5464175,
// 5464176,
// 5464177,
// 5464178,
// 5464186,
// 5464187,
// 5464188,
// 5464194,
// 5460002,
// 5460035,
// 5460036,
// 5460037,
// 5460038,
// 5460041,
// 5460056,
// 5460059,
// 5460063,
// 5460064,
// 5460065,
// 5460066,
// 5460085,
// 5460092,
// 5460094,
// 5460116,
// 5460124,
// 5460125,
// 5460126,
// 5460131,
// 5460170,
// 5460182,
// 5460190,
// 5460193,
// 5460194,
// 5460195,
// 5460199,
// 5460200,
// 5461301,
// 5461302,
// 5461303,
// 5461305,
// 5461313,
// 5461317,
// 5461328,
// 5461329,
// 5461330,
// 5461331,
// 5461337,
// 5461338,
// 5461339,
// 5461347,
// 5461349,
// 5461372,
// 5461373,
// 5461378,
// 5461380,
// 5461384,
// 5461385,
// 5461386,
// 5461388,
// 5461390,
// 5461394,
// 5461395,
// 5461401,
// 5461430,
// 5461431,
// 5461436,
// 5461437,
// 5461439,
// 5461441,
// 5461442,
// 5461444,
// 5461445,
// 5461448,
// 5461451,
// 5461472,
// 5461474,
// 5461475,
// 5461476,
// 5461479,
// 5461481,
// 5461486,
// 5461487,
// 5461488,
// 5461489,
// 5461490,
// 5461493,
// 5461496,
// 5461497,
// 5461498,
// 5461508,
// 5461509,
// 5461510,
// 5461513,
// 5461514,
// 5461530,
// 5461533,
// 5461534,
// 5461535,
// 5461544,
// 5461548,
// 5461550,
// 5461551,
// 5461556,
// 5461557,
// 5461563,
// 5461564,
// 5461589,
// 5461590,
// 5461591,
// 5461592,
// 5461596,
// 5461598,
// 5461600,
// 5462702,
// 5462703,
// 5462705,
// 5462706,
// 5462714,
// 5462755,
// 5462756,
// 5462758,
// 5462759,
// 5462760,
// 5462761,
// 5462762,
// 5462763,
// 5462764,
// 5462790,
// 5462792,
// 5462794,
// 5462795,
// 5462796,
// 5462799,
// 5462803,
// 5462804,
// 5462806,
// 5462808,
// 5462815,
// 5462835,
// 5462836,
// 5462839,
// 5462840,
// 5462841,
// 5462842,
// 5462843,
// 5462845,
// 5462846,
// 5462853,
// 5462865,
// 5462866,
// 5462869,
// 5462870,
// 5462874,
// 5462879,
// 5462880,
// 5462881,
// 5462882,
// 5462883,
// 5462885,
// 5462888,
// 5462890,
// 5462891,
// 5462896,
// 5462897,
// 5462909,
// 5462910,
// 5462911,
// 5462921,
// 5462930,
// 5462931,
// 5462932,
// 5462933,
// 5462934,
// 5462935,
// 5462936,
// 5462945,
// 5462947,
// 5462948,
// 5462951,
// 5462965,
// 5462971,
// 5462977,
// 5462981,
// 5462982,
// 5462984,
// 5462986,
// 5462988,
// 5462989,
// 5462992,
// 5462993,
// 5462994,
// 5462995,
// 5463801,
// 5463802,
// 5463803,
// 5463804,
// 5463851,
// 5463852,
// 5463853,
// 5463854,
// 5463855,
// 5463856,
// 5463857,
// 5463858,
// 5463859,
// 5463860,
// 5463861,
// 5463862,
// 5463863,
// 5463866,
// 5463868,
// 5463871,
// 5463872,
// 5463877,
// 5463878,
// 5463879,
// 5463883,
// 5463886,
// 5463887,
// 5463891,
// 5463892,
// 5463894,
// 5463901,
// 5463902,
// 5463904,
// 5463905,
// 5463910,
// 5463914,
// 5463915,
// 5463918,
// 5463920,
// 5463923,
// 5463925,
// 5463926,
// 5463927,
// 5463929,
// 5463931,
// 5463936,
// 5463937,
// 5463939,
// 5463940,
// 5463943,
// 5463944,
// 5463946,
// 5463947,
// 5463949,
// 5463950,
// 5463952,
// 5463953,
// 5463954,
// 5463955,
// 5463956,
// 5463957,
// 5463959,
// 5463963,
// 5463970,
// 5463997,
// 5463998,
// 5463999,
// 5464001,
// 5464002,
// 5464012,
// 5464030,
// 5464031,
// 5464032,
// 5464033,
// 5464034,
// 5464036,
// 5464037,
// 5464038,
// 5464039,
// 5464041,
// 5464042,
// 5464043,
// 5464044,
// 5464045,
// 5464046,
// 5464070,
// 5464072,
// 5464073,
// 5464074,
// 5464075,
// 5464076,
// 5464077,
// 5464078,
// 5464079,
// 5464080,
// 5464081,
// 5464083,
// 5464084,
// 5464086,
// 5464087,
// 5464088,
// 5464089,
// 5464090,
// 5464091,
// 5464092,
// 5464093,
// 5464094,
// 5464095,
// 5464905,
// 5464906,
// 5464911,
// 5464912,
// 5464913,
// 5464914,
// 5464915,
// 5464916,
// 5464918,
// 5464920,
// 5464921,
// 5464922,
// 5464923,
// 5464924,
// 5464925,
// 5464926,
// 5464927,
// 5464928,
// 5464929,
// 5464930,
// 5464931,
// 5464932,
// 5464933,
// 5464936,
// 5464961,
// 5464962,
// 5464963,
// 5464964,
// 5464966,
// 5464967,
// 5464968,
// 5464971,
// 5464972,
// 5464974,
// 5464979,
// 5464983,
// 5464984,
// 5464986,
// 5464992,
// 5465004,
// 5465005,
// 5465006,
// 5296078,
// 5296079,
// 5296089,
// 5296106,
// 5296115,
// 5296117,
// 5466072,
// 5461112,
// 5461113,
// 5461118,
// 5461122,
// 5461137,
// 5461138,
// 5461141,
// 5461142,
// 5461143,
// 5461160,
// 5461161,
// 5461162,
// 5461182,
// 5461202,
// 5461214,
// 5461215,
// 5461220,
// 5461221,
// 5461229,
// 5461231,
// 5461234,
// 5461236,
// 5461237,
// 5461280,
// 5461281,
// 5461283,
// 5461285,
// 5461287,
// 5461288,
// 5461293,
// 5461296,
// 5461297,
// 5461298,
// 5463202,
// 5463204,
// 5463205,
// 5463206,
// 5463207,
// 5463208,
// 5463209,
// 5463212,
// 5463218,
// 5463219,
// 5463221,
// 5463240,
// 5463241,
// 5463242,
// 5463265,
// 5463267,
// 5463269,
// 5463270,
// 5463277,
// 5463278,
// 5463279,
// 5463282,
// 5463283,
// 5463299,
// 5463300,
// 5463301,
// 5463304,
// 5463305,
// 5463306,
// 5463310,
// 5463312,
// 5463313,
// 5463314,
// 5463315,
// 5463316,
// 5463322,
// 5463339,
// 5463340,
// 5463341,
// 5463343,
// 5463344,
// 5463345,
// 5463346,
// 5463347,
// 5463348,
// 5463349,
// 5463355,
// 5463359,
// 5463372,
// 5463373,
// 5463374,
// 5463375,
// 5463376,
// 5463378,
// 5463392,
// 5463393,
// 5463397,
// 5463398,
// 5211869,
// 5211931,
// 5466382,
// 5294380,
// 5294381,
// 5294382,
// 5294383,
// 5294384,
// 5294385,
// 5294386,
// 5294389,
// 5294393,
// 5294394,
// 5294395,
// 5294410,
// 5294411,
// 5294412,
// 5294415,
// 5294416,
// 5294417,
// 5294418,
// 5462102,
// 5462124,
// 5462125,
// 5462130,
// 5462131,
// 5462133,
// 5462134,
// 5462136,
// 5462163,
// 5462164,
// 5462171,
// 5462174,
// 5462176,
// 5462177,
// 5462185,
// 5462186,
// 5462191,
// 5462193,
// 5462194,
// 5462204,
// 5462209,
// 5462212,
// 5462217,
// 5462236,
// 5462237,
// 5462245,
// 5462248,
// 5462250,
// 5462253,
// 5462254,
// 5462255,
// 5462256,
// 5462257,
// 5462264,
// 5462265,
// 5462266,
// 5462278,
// 5462285,
// 5462286,
// 5212355,
// 5212365,
// 5212366,
// 5212375,
// 5212376,
// 5459630,
// 5459631,
// 5459633,
// 5459637,
// 5459638,
// 5459641,
// 5459642,
// 5459645,
// 5459649,
// 5459650,
// 5459651,
// 5459660,
// 5459661,
// 5459662,
// 5459663,
// 5459671,
// 5459672,
// 5459673,
// 5459675,
// 5459676,
// 5459677,
// 5459678,
// 5459679,
// 5459681,
// 5459684,
// 5459685,
// 5459686,
// 5459687,
// 5459688,
// 5459689,
// 5459690,
// 5459691,
// 5459692,
// 5459693,
// 5459696,
// 5459698,
// 5459700,
// 5459701,
// 5459703,
// 5459704,
// 5459705,
// 5459707,
// 5459710,
// 5459711,
// 5459712,
// 5459721,
// 5459722,
// 5459726,
// 5459727,
// 5459728,
// 5459734,
// 5459735,
// 5459755,
// 5459757,
// 5459758,
// 5459771,
// 5459772,
// 5459773,
// 5459774,
// 5459775,
// 5459776,
// 5459781,
// 5459793,
// 5465501,
// 5465502,
// 5465505,
// 5465520,
// 5465521,
// 5465522,
// 5465528
// )
// AND !ISNULL(cd.chk_number) order by b.trans_date";


$sql="SELECT '$myBranchCode' as br_code, c.type, c.trans_no,  c.supplier_id, 
c.tran_date, b.trans_date, cd.chk_date, c.cv_id,c.non_trade,
a.cv_date,a.cv_no,b.id as b_id, cd.chk_number, ROUND(cd.chk_amount,2) as  chk_amount,
cd.deposit_date,'0' as reconciled
FROM 0_supp_trans as c
LEFT JOIN 0_cv_header as a 
ON c.cv_id=a.id 
LEFT JOIN 0_bank_trans as b 
ON a.bank_trans_id=b.id 
LEFT JOIN 0_cheque_details as cd 
ON b.id=cd.bank_trans_id
WHERE a.amount!=0 
AND c.ov_amount!=0 
AND c.type='22' 
AND b.type='22'
AND b.amount!=0 
AND b.bank_act=20
AND !ISNULL(cd.chk_number) 
AND cd.chk_number IN (

5463479,
5465673,
5465838,
5463717,
5466072


)
order by b.trans_date";

$result= db_query($sql, "failed to get bank_accounts id.");
	
	//2305-pdc payable
	//2501601-pdc payable-aub
	while($row = db_fetch($result))
	{
		
		// $sql1 = "UPDATE ".TB_PREF."gl_trans SET account='10102299'
		// WHERE type_no='".$row['trans_no']."' and type='22' and account='2501601'";
		// db_query($sql1,"Failed to update gl_trans.");
		
		$date_ = sql2date($row['chk_date']);
		$ref   = $Refs->get_next(0);
		$memo_ = "Adjustment for Payment, Reverse entry of Transaction#: ".$row['trans_no'];
		
		$trans_type = ST_JOURNAL;

		$trans_id = get_next_trans_no($trans_type);
		
			add_gl_trans($trans_type, $trans_id, $date_, 10102299, 0, 0, $memo_, $row['chk_amount'],null,3,$row['supplier_id']);
			add_gl_trans($trans_type, $trans_id, $date_, 2000, 0, 0, $memo_, -$row['chk_amount'],null,3,$row['supplier_id']);

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $date_, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $date_);
	}

	
	display_notification("Fixing Bank Accounts are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }
}

start_form();
start_row();
submit_center('Fix',_("Reverse entry"), true, '', false);
end_table();
end_form();
end_page();
?>