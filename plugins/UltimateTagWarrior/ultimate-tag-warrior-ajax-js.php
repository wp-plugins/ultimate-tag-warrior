<?php $ajaxurl = $_GET['ajaxurl'] ?>
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

var http = createRequestObject();

function sndReq(action, tag, post, format) {
    http.open('get', '<?= $ajaxurl ?>?action='+action+'&tag='+tag+'&post='+post+'&format='+format);
    http.onreadystatechange = handleResponse;
    http.send(null);
}

function handleResponse() {
    if(http.readyState == 4){
        var response = http.responseText;
        var update = new Array();

        if(response.indexOf('|' != -1)) {
            update = response.split('|');
            document.getElementById("tags-" + update[0]).innerHTML = update[1];
        }
    }
}

function askYahooForKeywords() {
	http.open('POST','<?= $ajaxurl ?>?action=requestKeywords');
	http.onreadystatechange = listYahooKeywords;
	http.send(escape(document.getElementById('content').value));
}

function listYahooKeywords() {
    if(http.readyState == 4){
    	document.getElementById("suggestedTags").innerHTML = http.responseText;
	}
}