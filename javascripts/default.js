// Main JavaScript file
// Copyright 2006-2008 Dimitry Bentsionov <dimitryb@gmail.com>


// ------------------------------------

// Browser detection and ToolTip script

// ------------------------------------

var curDetails = 0;

function findPosX(obj)

{

  var curleft = 0;

  if (obj.offsetParent)

  {

    while (obj.offsetParent)

    {

      curleft += obj.offsetLeft

      obj = obj.offsetParent;

    }

  }

  else if (obj.x)

    curleft += obj.x;

  return curleft;

}



function findPosY(obj)

{

  var curtop = 0;

  if (obj.offsetParent)

  {

    while (obj.offsetParent)

    {

      curtop += obj.offsetTop;

      obj = obj.offsetParent;

    }

  }

  else if (obj.y)

    curtop += obj.y;

  return curtop;

}

var detect = navigator.userAgent.toLowerCase();
var OS,browser,version,total,thestring;

if (checkIt('konqueror')) {

  browser = "Konqueror";

  OS = "Linux";

}

else if (checkIt('safari')) browser = "Safari"

else if (checkIt('omniweb')) browser = "OmniWeb"

else if (checkIt('opera')) browser = "Opera"

else if (checkIt('webtv')) browser = "WebTV";

else if (checkIt('icab')) browser = "iCab"

else if (checkIt('msie')) browser = "Internet Explorer"

else if (!checkIt('compatible'))

{

  browser = "Netscape Navigator"

  version = detect.charAt(8);

}

else browser = "An unknown browser";



if (!version) version = detect.charAt(place + thestring.length);



if (!OS)

{

  if (checkIt('linux')) OS = "Linux";

  else if (checkIt('x11')) OS = "Unix";

  else if (checkIt('mac')) OS = "Mac"

  else if (checkIt('win')) OS = "Windows"

  else OS = "an unknown operating system";

}



function checkIt(string)

{

  place = detect.indexOf(string) + 1;

  thestring = string;

  return place;

}



function tooltip_init(posx,posy,updown,id,name,location,confirmed) {
  var objTT = document.getElementById('tooltip');
  //if (browser == "Internet Explorer") { posy += 12; posx += 19; } // Additional IE Adjustments - For some reason sometimes this needs to be used!
  if (browser == "Internet Explorer") { posy += 2; posx += 2; } // Additional IE Adjustments

  // Adjustments: up or down
  if (updown == 'down') { posy += 30; posx += 0; }
  else if (updown == 'up') { posy -= 45; posx += 0; }

  if (confirmed)
    objTT.className = "tt1";
  else
    objTT.className = "tt2";

  if(!location){ location = ""; }
  if(!name){ name = ""; } 

  objTT.innerHTML = id+"<br />"+location+"<br />"+name;
  objTT.style.top = posy + 'px';
  objTT.style.left = posx + 'px';
  objTT.style.display = 'block';
}



function tooltip_kill() {
  var objTT = document.getElementById('tooltip');
  objTT.style.display = 'none';
}



// ------------------------------------
// Shows and hides Details appropriatly
// ------------------------------------

function loadDetails() {
  // Arguments:
  // IF TYPE = 1 (TICKET):
  // [0]  = type,
  // [1]  = id,
  // [2]  = consultant,
  // [3]  = date,
  // [4]  = when,
  // [5]  = withwho,
  // [6]  = where,
  // [7]  = phone,
  // [8]  = desc,
  // [9]  = rc,
  // [10] = ap,
  // [11] = tk,
  // [12] = tktype
  //
  // --------------------------------------
  //
  // IF TYPE = 2 (MEETING):
  // [0]  = type,
  // [1]  = id,
  // [2]  = consultant,
  // [3]  = date,
  // [4]  = when,
  // [5]  = where,
  // [6]  = desc,
  // [7]  = subject,
  // [8]  = rc,
  // [9]  = ap,
  // [10] = tk,
  // [11] = tktype

  var div_sysmsg = document.getElementById("sysmsg");
  var div_details = document.getElementById("details");
  var div_grid3a = document.getElementById("grid-3a");
  var adate = document.getElementById("date").value;

  if (arguments[0] == 1) {
    var inHTML = ""
    inHTML += "<div id=\"fulldetails\" onmouseover=\"this.style.backgroundColor='#fffcd1'\" onmouseout=\"this.style.backgroundColor='#ebebeb'\" onclick=\"linkViewFullDetails("+arguments[9]+", "+arguments[10]+",'"+adate+"')\">View Full Details</div>";
    inHTML += "<div id=\"details-msg\"><span class=\"toptitle\">"+arguments[1]+"<br />"+arguments[2]+"</span><br /><br />";
//    inHTML += "<div id=\"fulldetails\" onmouseover=\"this.style.backgroundColor='#fffcd1'\" onmouseout=\"this.style.backgroundColor='#ebebeb'\" onclick=\"linkViewFullDetails("+arguments[9]+", "+arguments[10]+",'"+adate+"')\">View Full Details</div>"
    inHTML += "<span class=\"title\">Date:</span><br />"+arguments[3]+"<br /><br />";
    inHTML += "<span class=\"title\">When:</span><br />"+arguments[4]+"<br /><br />";
    inHTML += "<span class=\"title\">With:</span><br />"+arguments[5]+"<br /><br />";
    inHTML += "<span class=\"title\">Where:</span><br />"+arguments[6]+"<br /><br />";
    inHTML += "<span class=\"title\">Phone:</span><br />"+arguments[7]+"<br /><br />";
    inHTML += "<span class=\"title\">Description:</span><br />"+arguments[8];
    inHTML += "<form id='this_form'><input type='hidden' name='this_aid' id='this_aid' value='"+arguments[10]+"' /><input type='hidden' id='this_rid' name='this_rid' value='"+arguments[9]+"' /><input type='hidden' name='this_tid' id='this_tid' value='"+arguments[11]+"' /><input type='hidden' name='this_ttype' id='this_ttype' value='"+arguments[12]+"' /></form>";
    inHTML += "</div>";
//    inHTML += "</div><div id=\"fulldetails\" onmouseover=\"this.style.backgroundColor='#fffcd1'\" onmouseout=\"this.style.backgroundColor='#ebebeb'\" onclick=\"linkViewFullDetails("+arguments[9]+", "+arguments[10]+",'"+adate+"')\">View Full Details</div>";
  } else if (arguments[0] == 2) {
    var inHTML = ""
    inHTML += "<div id=\"fulldetails\" onmouseover=\"this.style.backgroundColor='#fffcd1'\" onmouseout=\"this.style.backgroundColor='#ebebeb'\" onclick=\"linkViewFullDetails("+arguments[8]+", "+arguments[9]+",'"+adate+"')\">View Full Details</div>";
     inHTML += "<div id=\"details-msg\"><span class=\"toptitle\"><strong>Meeting:</strong><br />"+arguments[2]+"</span><br /><br />";
    inHTML += "<span class=\"title\">Subject:</span><br />"+arguments[7]+"<br /><br />";
    inHTML += "<span class=\"title\">Date:</span><br />"+arguments[3]+"<br /><br />";
    inHTML += "<span class=\"title\">When:</span><br />"+arguments[4]+"<br /><br />";
    inHTML += "<span class=\"title\">Where:</span><br />"+arguments[5]+"<br /><br />";
    inHTML += "<span class=\"title\">Description:</span><br />"+arguments[6];
    inHTML += "<input type='hidden' name='this_aid' id='this_aid' value='"+arguments[9]+"' /><input type='hidden' id='this_rid' name='this_rid' value='"+arguments[8]+"' /><input type='hidden' name='this_tid' id='this_tid' value='"+arguments[10]+"' /><input type='hidden' name='this_ttype' id='this_ttype' value='"+arguments[11]+"' />";
    inHTML += "</div>";
//    inHTML += "</div><div id=\"fulldetails\" onmouseover=\"this.style.backgroundColor='#fffcd1'\" onmouseout=\"this.style.backgroundColor='#ebebeb'\" onclick=\"linkViewFullDetails("+arguments[8]+", "+arguments[9]+",'"+adate+"')\">View Full Details</div>";
  }

  document.getElementById('dets').innerHTML = inHTML;
  div_sysmsg.style.display = 'none';
  div_details.style.display = 'block';
  div_grid3a.style.display = 'block';

  curDetails = arguments[1];
}

function clearDetails() {
  document.getElementById("sysmsg").style.display = 'block';
  document.getElementById("details").style.display = 'none';
  document.getElementById("grid-3a").style.display = 'none';
  curDetails = 0;
  return;
}

// ------------------------------------
// Initiates Right panel to be the right height
// ------------------------------------



function initPanelHeight(special) {


  var objLeft = document.getElementById('grid-1');

  var objRight = document.getElementById('wrapper');

  var objInset = document.getElementById('msg');

  var objMsg = document.getElementById('dets');


  var newheight = (special) ? (objLeft.offsetHeight) : (objLeft.offsetHeight - 148);
    //alert(newheight);
  objRight.style.height = newheight + 'px';



  var inheight = (special) ? (newheight) : (newheight - 35);
    //alert(inheight);
  objInset.style.height = inheight + 'px';



  var newheight2 = (special) ? (objLeft.offsetHeight) : (objLeft.offsetHeight - 175);
    //alert(newheight2);
  objMsg.style.height = newheight2 + 'px';

}



// ------------------------------------

// Add new system message

// ------------------------------------



function addMsg(the_msg) {

  var objMsg = document.getElementById('msg');

  var randnum = Math.round(1000*Math.random());

  objMsg.innerHTML = "<div id=\"systemalert_" + randnum + "\">" + the_msg + "<hr /></div>" + objMsg.innerHTML;



  eval("myFadeMsg" + randnum + " = new fx.FadeSize('systemalert_"+randnum+"', {duration: 500})");

  eval("myFadeMsg" + randnum + ".hide('height')");

  eval("myFadeMsg" + randnum + ".toggle('height')");



  if (allMsgs != "") allMsgs = allMsgs + "," + randnum;

  else allMsgs = randnum;

}



function clearMsg() {

  allMsgs += '';

  var msgData;

  var comapos = allMsgs.indexOf(",");

  if (comapos == -1) {

    eval("myFadeMsger0 = new fx.FadeSize('systemalert_'+allMsgs, {duration: 400})");

    eval("myFadeMsger0.toggle()");

  } else {

    msgData = allMsgs.split(",");



    for (i = 0; i < msgData.length; i++) {

      eval("myFadeMsger" + i + " = new fx.FadeSize('systemalert_'+msgData[i], {duration: 400})");

      eval("myFadeMsger" + i + ".toggle('height')");

    }

  }



  allMsgs = "";

  setTimeout("document.getElementById('msg').innerHTML = \"\"",410);

}

// ------------------------------------
// Toggle Divs
// ------------------------------------

function toggle_display() {
  var j = arguments.length;
  var typ = ((j >= 1) ? arguments[0] : "");
  for (var i=1; i<arguments.length; i++) {
    if(typ == "meeting_admin"){
      (document.getElementById(arguments[i]).style.display == "none") ?
        (document.getElementById(arguments[i]).style.display = "") :
        (document.getElementById(arguments[i]).style.display = "none");
    } else if(typ == "consultanthours_admin"){
      (document.getElementById("form[htype]").value == "repeat" || document.getElementById("form[htype]").value == "delete") ?
        (document.getElementById(arguments[i]).style.display = "") :
        (document.getElementById(arguments[i]).style.display = "none");
    //} else if(typ == "ophours_admin"){

    }
  }
}

// ------------------------------------
// Table Rollover
// ------------------------------------

function colorCol(colid,total,obj,colspan) {
  if (colid != 0) {
    for(var o=0;o<colspan;o++) {
      for(var i=0;i<total;i++) {
        if (document.getElementById("col_"+(colid+o)+"_"+i))
        if (document.getElementById("col_"+(colid+o)+"_"+i).className == "timeslot") document.getElementById("col_"+(colid+o)+"_"+i).style.backgroundColor = "#ebebeb";
      }
      obj.parentNode.firstChild.style.backgroundColor = "#ebebeb";
    }
  }
}

function resetCol(colid,total,obj,colspan) {
  if (colid != 0) {
    for(var o=0;o<colspan;o++) {
      for(var i=0;i<total;i++) {
        if (document.getElementById("col_"+(colid+o)+"_"+i))
        if (document.getElementById("col_"+(colid+o)+"_"+i).className == "timeslot") document.getElementById("col_"+(colid+o)+"_"+i).style.backgroundColor = "#ffffff";
      }
      obj.parentNode.firstChild.style.backgroundColor = "#ffffff";
    }
  }
}

// ------------------------------------
// Error Handling. Uncomment when done debugging.
// ------------------------------------

function customHandler(desc,page,line,chr)  {
  alert('JavaScript error (debug mode: on):\n'
      +'\nError description: \t'+desc
      +'\nPage address:      \t'+page
      +'\nLine number:       \t'+line
      );
  return true;
}

//window.onerror=customHandler;
