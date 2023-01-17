<?php

class Hooks {
	function retrieve_exrate($curr, $date)
	{
		return get_extern_rate($curr, 'YAHOO', $date);
		//return get_extern_rate($curr, 'GOOGLE', $date);
	}
}

?>