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
//---------------------------------------------------------------------------------------------
function get_exchange_rate($rate_id)
{
	$sql = "SELECT * FROM ".TB_PREF."exchange_rates WHERE id=".db_escape($rate_id);
	$result = db_query($sql, "could not get exchange rate for $rate_id");	

	return db_fetch($result);
}

// Retrieves buy exchange rate for given currency/date, zero if no result
function get_date_exchange_rate($curr_code, $date_)
{
	$date = date2sql($date_);
	$sql = "SELECT rate_buy FROM ".TB_PREF."exchange_rates WHERE curr_code=".db_escape($curr_code)
	." AND date_='$date'";
	$result = db_query($sql, "could not get exchange rate for $curr_code - $date_");	

	if(db_num_rows($result) == 0) 
		return 0;
	$row = db_fetch($result);
		return $row[0];
}

//---------------------------------------------------------------------------------------------

function update_exchange_rate($curr_code, $date_, $buy_rate, $sell_rate)
{
	if (is_company_currency($curr_code))
		display_db_error("Exchange rates cannot be set for company currency", "", true);
			
	$date = date2sql($date_);
		
	$sql = "UPDATE ".TB_PREF."exchange_rates SET rate_buy=$buy_rate, rate_sell=".db_escape($sell_rate)
	." WHERE curr_code=".db_escape($curr_code)." AND date_='$date'";
				
	db_query($sql, "could not add exchange rate for $curr_code");				
}

//---------------------------------------------------------------------------------------------

function add_exchange_rate($curr_code, $date_, $buy_rate, $sell_rate)
{
	if (is_company_currency($curr_code))
		display_db_error("Exchange rates cannot be set for company currency", "", true);

	$date = date2sql($date_);
		
	$sql = "INSERT INTO ".TB_PREF."exchange_rates (curr_code, date_, rate_buy, rate_sell)
		VALUES (".db_escape($curr_code).", '$date', ".db_escape($buy_rate)
		.", ".db_escape($sell_rate).")";
	db_query($sql, "could not add exchange rate for $curr_code");				
}

//---------------------------------------------------------------------------------------------

function delete_exchange_rate($rate_id)
{
	$sql = "DELETE FROM ".TB_PREF."exchange_rates WHERE id=".db_escape($rate_id);
	db_query($sql, "could not delete exchange rate $rate_id");		
}

//-----------------------------------------------------------------------------
//	Retrieve exchange rate as of date $date from external source (usually inet)
//
function retrieve_exrate($curr_b, $date)
{
	global $Hooks;

	if (method_exists($Hooks, 'retrieve_exrate'))
		return $Hooks->retrieve_exrate($curr_b, $date);
	else
		return get_extern_rate($curr_b, 'ECB', $date);
}
//-----------------------------------------------------------------------------

function get_extern_rate($curr_b, $provider = 'ECB', $date) 
{
	$curr_a = get_company_pref('curr_default');
	if ($provider == 'ECB')
	{
		$filename = "/stats/eurofxref/eurofxref-daily.xml";
		$site = "www.ecb.int";
	}
	elseif ($provider == 'YAHOO')
	{
		$filename = "/q?s={$curr_a}{$curr_b}=X";
		$site = "finance.yahoo.com";
	}
	elseif ($provider == 'GOOGLE')
	{
		$filename = "/finance/converter?a=1&from={$curr_a}&to={$curr_b}";
		$site = "finance.google.com";
	}
	$contents = '';

	if (function_exists('curl_init'))
	{	// first check with curl as we can set short timeout;
		$retry = 1;
	 	do {
	       $ch = curl_init();
    	   curl_setopt ($ch, CURLOPT_URL, 'http://'.$site.$filename);
	       curl_setopt ($ch, CURLOPT_COOKIEJAR, "cookie.txt");
    	   curl_setopt ($ch, CURLOPT_HEADER, 0);
	       curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    	   curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
	       curl_setopt ($ch, CURLOPT_TIMEOUT, 3);
    	   $contents = curl_exec ($ch);
	       curl_close($ch);
			// due to resolver bug in some curl versions (e.g. 7.15.5) 
			// try again for constant IP.
		   $site="195.128.2.97";
	   } while( ($contents == '') && $retry--);
	   
	} else {
		$handle = @fopen("http://".$site.$filename, 'rb');
		if ($handle) {
			do 
			{
				$data = @fread( $handle, 4096 );
				if ( strlen ( $data ) == 0 ) 
   					break;
   				$contents .= $data; // with this syntax only text will be translated, whole text with htmlspecialchars($data)
			} 
			while (true);
			@fclose( $handle );
		} // end handle
	}
	if (!$contents) {
		display_warning(_("Cannot retrieve currency rate from $provider page. Please set the rate manually."));
	}
	if ($provider == 'ECB')
	{
		$contents = str_replace ("<Cube currency='USD'", " <Cube currency='EUR' rate='1'/> <Cube currency='USD'", $contents);
		$from_mask = "|<Cube\s*currency=\'" . $curr_a . "\'\s*rate=\'([\d.,]*)\'\s*/>|i";
		preg_match ( $from_mask, $contents, $out );
		$val_a = isset($out[1]) ? $out[1] : 0;
		$val_a = str_replace ( ',', '', $val_a );
		$to_mask = "|<Cube\s*currency=\'" . $curr_b . "\'\s*rate=\'([\d.,]*)\'\s*/>|i";
		preg_match ( $to_mask, $contents, $out );
		$val_b = isset($out[1]) ? $out[1] : 0;
		$val_b = str_replace ( ',', '', $val_b );
		if ($val_b) 
		{
			$val = $val_a / $val_b;
		} 
		else 
		{
			$val = 0;
		}
	}
	elseif ($provider == 'YAHOO')
	{
		$val = '';
		if (preg_match('/Last\sTrade:(.*?)Trade\sTime/s', $contents, $matches)) {
			$val = strip_tags($matches[1]);
			$val = str_replace(',', '', $val);
			if ($val != 0)
				$val = 1 / $val;
		}
	}
	elseif ($provider == 'GOOGLE')
	{
		$val = '';
		$regexp = "%([\d|.]+)\s+{$curr_a}\s+=\s+<span\sclass=(.*)>([\d|.]+)\s+{$curr_b}\s*</span>%s";
		if (preg_match($regexp, $contents, $matches)) 
		{
			$val = $matches[3];
			$val = str_replace(',', '', $val);
			if ($val != 0)
				$val = 1 / $val;
		}
    }    
	return $val;
}  /* end function get_extern_rate */

?>