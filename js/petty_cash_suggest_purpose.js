function getXmlHttpRequestObject() {
	if (window.XMLHttpRequest) {
		return new XMLHttpRequest();
	} else if(window.ActiveXObject) {
		return new ActiveXObject("Microsoft.XMLHTTP");
	} else {
		alert("Your Browser Sucks!");
	}
}

//Our XmlHttpRequest object to get the auto suggest
var searchReq = getXmlHttpRequestObject();

//Called from keyup on the search textbox.
//Starts the AJAX request.
function SuggestPurpose() {
	if (searchReq.readyState == 4 || searchReq.readyState == 0) {
		var str = escape(document.getElementById('purpose').value);
		searchReq.open("GET", '../gl/petty_cash_suggest_purpose.php?search_purpose=' + str, true);
		searchReq.onreadystatechange = handlePurposeSuggest; 
		searchReq.send(null);
	}		
}

//Called when the AJAX response is returned.
function handlePurposeSuggest() {
	if (searchReq.readyState == 4) {
	        var ss = document.getElementById('layer2');
		var str1 = document.getElementById('purpose');
		var curLeft=0;
		if (str1.offsetParent){
		    while (str1.offsetParent){
			curLeft += str1.offsetLeft;
			str1 = str1.offsetParent;
		    }
		}
		var str2 = document.getElementById('purpose');
		var curTop=20;
		if (str2.offsetParent){
		    while (str2.offsetParent){
			curTop += str2.offsetTop;
			str2 = str2.offsetParent;
		    }
		}
		var str =searchReq.responseText.split("\n");
		if(str.length==1)
		    document.getElementById('layer2').style.visibility = "hidden";
		else
		    ss.setAttribute('style','position:absolute;top:'+curTop+';left:'+curLeft+';width:250;z-index:1;padding:5px;border: 1px dashed #555555; overflow:auto; height:105; background-color:#FFFFFF; color:#555555; text-align: left;');
		ss.innerHTML = '';
		for(i=0; i < str.length - 1; i++) {
			//Build our element string.  This is cleaner using the DOM, but
			//IE doesn't support dynamically added attributes.
			var suggest = '<div onmouseover="javascript:suggestOver(this);"';
			suggest += 'onmouseout="javascript:suggestOut(this);" ';
			suggest += 'onclick="javascript:setSearchPurpose(this.innerHTML);" ';
			suggest += 'class="small">' + str[i] + '</div>';
			ss.innerHTML += suggest;
		}
	}
}

//Mouse over function
function suggestOver(div_value) {
	div_value.className = 'suggest_link_over';
}
//Mouse out function
function suggestOut(div_value) {
	div_value.className = 'suggest_link';
}

//decoding function ex: &amp;
function htmlDecode(input){
  var e = document.createElement('div');
  e.innerHTML = input;
  return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
}

//Click function
function setSearchPurpose(value) {
	document.getElementById('purpose').value = htmlDecode(value);
	document.getElementById('layer2').innerHTML = '';
	document.getElementById('layer2').style.visibility = "hidden";
}