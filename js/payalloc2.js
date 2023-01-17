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
function focus_alloc(i) {
    save_focus(i);
	i.setAttribute('_last', get_amount(i.name));
}

function blur_alloc(i) {
		
		if (i.name.substr(0,11) != 'ewt_percent')
			var change = get_amount(i.name);
		else
			var change = get_amount(i.name) * (get_amount('tot_amount'+i.name.substr(11)) / 100);
		
		var update_ewt = 0;
		var change2 = 0;
		var ewt_amount = 0;
		var last_ewt_amount = 0;
		
		
		if (i.name != 'amount' && i.name != 'charge' && i.name != 'discount' && i.name!= 'ewt')
		{
			if (i.name.substr(0,6) == 'amount' )
			{
				change = Math.min(change, get_amount('maxval'+i.name.substr(6), 1))
				change2 = i.getAttribute('_last')
			}
			else if (i.name.substr(0,11) == 'ewt_percent' )
			{
				ewt_amount = get_amount(i.name) * (get_amount('tot_amount'+i.name.substr(11)) / 100) ;
				last_ewt_amount = i.getAttribute('_last') * (get_amount('tot_amount'+i.name.substr(11)) / 100) ;
				
				
				price_format(i.name, get_amount(i.name), user.pdec);
				change = (last_ewt_amount - ewt_amount);
				
				ewt_ewt = Math.abs(get_amount('ewt')-change);
				
				price_format('ewt', ewt_ewt, user.pdec, 0);
			}
		}
		
		if (i.name.substr(0,11) != 'ewt_percent')
			price_format(i.name, change, user.pdec);
		
		if (i.name != 'amount' && i.name != 'charge') 
		{
			if (i.name.substr(0,11) == 'ewt')
			{
				if (change<0) change = 0;
					change = change-i.getAttribute('_last');
			}
			
			if (i.name == 'discount') change = -change;
			
			if (i.name == 'ewt') change = -change;

			var total = get_amount('amount')+change-change2;
			
			price_format('amount', total, user.pdec, 0);
		}
}

function allocate_all(doc) {
	var amount = get_amount('amount'+doc);
	var unallocated = get_amount('un_allocated'+doc);
	var total = get_amount('amount');
	var left = 0;
	
	total -=  (amount-unallocated);
	left -= (amount-unallocated);
	amount = unallocated;
	if(left<0) {
		total  += left;
		amount += left;
		left = 0;
	}
	price_format('amount'+doc, amount, user.pdec);
	price_format('amount', total, user.pdec);
}

function allocate_none(doc) {
	amount = get_amount('amount'+doc);
	total = get_amount('amount');
	ewt_amount = get_amount('ewt_percent'+doc) * (get_amount('tot_amount'+doc) / 100);
	
	price_format('amount'+doc, 0, user.pdec);
	price_format('ewt_percent'+doc, 0, user.pdec);
	price_format('amount', total-amount+ewt_amount, user.pdec);
	price_format('ewt', get_amount('ewt')-ewt_amount, user.pdec);
}

var allocations = {
	'.amount': function(e) {
		e.onblur = function() {
			blur_alloc(this);
		  };
		e.onfocus = function() {
			focus_alloc(this);
		};
	}
}

Behaviour.register(allocations);
