function getPageSize(){

	

	var xScroll, yScroll;

	

	if (window.innerHeight && window.scrollMaxY) {	

		xScroll = document.body.scrollWidth;

		yScroll = window.innerHeight + window.scrollMaxY;

	} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac

		xScroll = document.body.scrollWidth;

		yScroll = document.body.scrollHeight;

	} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari

		xScroll = document.body.offsetWidth;

		yScroll = document.body.offsetHeight;

	}

	

	var windowWidth, windowHeight;

	if (self.innerHeight) {	// all except Explorer

		windowWidth = self.innerWidth;

		windowHeight = self.innerHeight;

	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode

		windowWidth = document.documentElement.clientWidth;

		windowHeight = document.documentElement.clientHeight;

	} else if (document.body) { // other Explorers

		windowWidth = document.body.clientWidth;

		windowHeight = document.body.clientHeight;

	}	

	

	// for small pages with total height less then height of the viewport

	if(yScroll < windowHeight){

		pageHeight = windowHeight;

	} else { 

		pageHeight = yScroll;

	}



	// for small pages with total width less then width of the viewport

	if(xScroll < windowWidth){	

		pageWidth = windowWidth;

	} else {

		pageWidth = xScroll;

	}





	arrayPageSize = new Array(pageWidth,pageHeight,windowWidth,windowHeight) 

	return arrayPageSize;

}







//

// getKey(key)

// Gets keycode. If 'x' is pressed then it hides the lightbox.

//



function getKey(e){
	if (e == null) { // ie
		keycode = event.keyCode;
	} else { // mozilla
		keycode = e.which;
	}
	key = String.fromCharCode(keycode).toLowerCase();
	//if ((keycode == 27) || (key == 'x')) { hideBox(); }
}

function listenKey () {	document.onkeypress = getKey; }

function showBox(objLink) {
	var objOverlay = document.getElementById('overlay');
	var objBoxLoad = document.getElementById('boxload');
	var objBox = document.getElementById('box');
	objBox.style.width = "auto";
	var arrayPageSize = getPageSize();

	// set height of Overlay to take up whole page and show
	var posx = Math.floor((arrayPageSize[2]-763)/2) + 9;
	var posx2 = Math.floor((arrayPageSize[2]-763)/2) - 25;
	
	objBoxLoad.style.left = posx2 + 'px';
	objBoxLoad.style.top = '76px';

	objBox.style.left = posx + 'px';
	objBox.style.top = '70px';

	objOverlay.style.height = (arrayPageSize[1] + 'px');
	objOverlay.style.width = (arrayPageSize[2] + 'px');
	objOverlay.style.display = 'block';
	objBox.style.display = 'block';
	
	//setTimeout("myFadeSize3.toggle('height')",50);
	listenKey();
}



function hideBox(reset_resch) {
	objOverlay = document.getElementById('overlay');
	objBoxLoad = document.getElementById('boxload');
	objBox = document.getElementById('box');
	
	//gregs_reload_underlay();
	if(reset_resch){
		document.getElementById('resch').value = '0'; 
		document.getElementById('formajax').value = '/scheduler/appointment/ajax/create_form';
	}
	clearLockouts();
	reloadmessages('msg', 'calendar');
	
	curDetails = 0;

	objOverlay.style.display = 'none';
	objBoxLoad.style.display = 'none';
	objBox.style.display = 'none';
	objBox.innerHTML = "";
  
  document.getElementById("dets").innerHTML = "";
  clearDetails();
	
	resetfields();

	// disable keypress listener
	document.onkeypress = '';
}

function gregs_reload_underlay(){
	controller_action = 'default/ajax/reload';
	date = document.getElementById('date').value; 
	new Ajax.Updater('main_div', '/scheduler/'+controller_action+'?date='+date, {asynchronous:true, evalScripts:true, onComplete:function(request){windowload(); curDetails = 0;}, onLoading:function(request){doLoading("main");}});
	
	return true;
}

function cancelBox() {
	objOverlay = document.getElementById('overlay');
	objBoxLoad = document.getElementById('boxload');
	objBox = document.getElementById('box');

	objOverlay.style.display = 'none';
	objBoxLoad.style.display = 'none';
	objBox.style.display = 'none';

	// disable keypress listener
	document.onkeypress = '';
}