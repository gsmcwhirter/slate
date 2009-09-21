var allMsgs = "";
var emptystuff = "";
var autorefreshtimer = 300;

function windowload(){
  initPanelHeight(false);
  resetfields();
  starttimer(autorefreshtimer);
}

function windowload2(){
    initPanelHeight(true);
    resetfields();
    starttimer(autorefreshtimer);
}

function doLoading(type){
  if(type == "topbar") // For the top, fixes loading image being out of bounds
    document.getElementById(type+"_div").innerHTML = "<img src=\"/images/loading2.gif\" width=\"16\" height=\"16\" alt=\"\" />"; // Uses loading2.gif (vs. loading.gif for other DIVs)
  else
    document.getElementById(type+"_div").innerHTML = loadingContent();
}

function doLoading2(type){
  (type) ? document.getElementById('boxload').style.display = 'block' : document.getElementById('boxload').style.display = 'none';
}

//onClick and/or link in calendar view (calendar_helper::schedulerDayOutput)
function blockClicked(time, consultant, block){
  var st = document.getElementById("starttime");
  var pt = document.getElementById("stoptime");
  var rid = document.getElementById("consultant");
  var cell = document.getElementById(block);
  var check = document.getElementById("check");

  if(st.value == "0"){
    document.getElementById("starttime").value = time;
    document.getElementById("consultant").value = consultant;

    //change the clicked block's color and stuff, and update the label of directions
    cell.style.backgroundColor='#c0c0c0'; //this should change to explicit calling in order to make className work
    cell.className = "timeslot selected";

    //save the row id of the block just clicked
    var i1 = block.indexOf('_');
    var i2 = block.substr(4, block.length).indexOf('_');
    //alert(i1 + " " + i2);
    var numlen = 4 + i2 - i1 - 1;
    //alert(numlen);
    document.getElementById("startrow").value = block.substr(4, numlen) * 1;
  }
  else{
    if(pt.value == "0" && time >= st.value && consultant == rid.value){
      //set the field
      document.getElementById("stoptime").value = time;

      //highlight the intermediate blocks
      //numlen = (block.indexOf('C') - block.indexOf('R')) - 1;
      var i1 = block.indexOf('_');
      var i2 = block.substr(4, block.length).indexOf('_');
      var numlen = 4 + i2 - i1 - 1;
      var startrow = document.getElementById("startrow").value * 1;
      var stoprow = block.substr(4, numlen) * 1;

      for(i = startrow; i < (stoprow + 1); i++){
        document.getElementById("col_"+i+block.substr(4 + i2, block.length)).style.backgroundColor='#c0c0c0'; //this should change to className later
        document.getElementById("col_"+i+block.substr(4 + i2, block.length)).className = "timeslot selected";
      }

      //submit the form
      subajaxform(document.getElementById("formajax").value);
    }
    else{
      //Error - reset everything
      resetfields();
    }
  }

}

//convert a number to a string with at least 2 characters of length
function doubledigit(number){
  if((number+'').length != 2){
    number = '0'+number;
  }
  number += '';

  return number;
}

//clears the standard fields
function resetfields(){
  var st = document.getElementById("starttime");
  var pt = document.getElementById("stoptime");
  var rid = document.getElementById("consultant");

  st.value = "0";
  pt.value = "0";
  rid.value = "0";
}

//changes the highlighting on the calendar selector (calendar_helper::selectorOutput)
function dayHighlighter(changedto, ajaxurl){
  var weareon = document.getElementById("date").value;
  var todayis = document.getElementById("tddate").value;

  if(changedto.substr(0,7) != weareon.substr(0,7)){
    //call an Ajax thinger
    new Ajax.Updater('selector_div', '/c:calendar/select?date='+changedto, {asynchronous:true, evalScripts:true});
  }
  else{
    //change the highlighting
    document.getElementById("CD"+weareon.substr(5,2)+weareon.substr(8,2)).className='day';
    if(todayis.substr(5,2) == changedto.substr(5,2)){
      document.getElementById("CD"+todayis.substr(5,2)+todayis.substr(8,2)).className='day today';
    }
    document.getElementById("CD"+changedto.substr(5,2)+changedto.substr(8,2)).className='day viewing';
  }

  return;
}

//changes the highlighting on the calendar selector (calendar_helper::selectorOutput)
function weekHighlighter(changedto, ajaxurl){
  var weareon = document.getElementById("date").value;
  var todayis = document.getElementById("tddate").value;

  if(changedto.substr(0,7) != weareon.substr(0,7)) {
  //call an Ajax thinger
  new Ajax.Updater('selector_div', '/c:display/select?date='+changedto, {asynchronous:true, evalScripts:true});
  } else {

  //Clear all TRs
  var allrows = document.getElementById('tab_cal').getElementsByTagName("tr");
  for(var i=2;i<allrows.length;i++) {allrows[i].className = "clear";}

  //Highlight current one
  var thisparent = document.getElementById("CD"+changedto.substr(5,2)+changedto.substr(8,2)).parentNode;
  thisparent.className = "selrow";

  //Highlight clicked day
  //document.getElementById("CD"+changedto.substr(8,2)).className='day viewing';
  }
  return;
}

//changes the highlighting on the calendar selector (calendar_helper::selectorOutput)
function weekHighlighter2(changedto, ajaxurl){
  var weareon = document.getElementById("date").value;
  var todayis = document.getElementById("tddate").value;

  if(changedto.substr(0,7) != weareon.substr(0,7)) {
  //call an Ajax thinger
  new Ajax.Updater('selector_div', '/scheduler2/c:display_admin/select?date='+changedto+'&rcid='+$("rcid_for_js").value, {asynchronous:true, evalScripts:true});
  } else {

  //Clear all TRs
  var allrows = document.getElementById('tab_cal').getElementsByTagName("tr");
  for(var i=2;i<allrows.length;i++) {allrows[i].className = "clear";}

  //Highlight current one
  var thisparent = document.getElementById("CD"+changedto.substr(5,2)+changedto.substr(8,2)).parentNode;
  thisparent.className = "selrow";

  //Highlight clicked day
  //document.getElementById("CD"+changedto.substr(8,2)).className='day viewing';
  }
  return;
}

//used in the calendar view to link silently to a new day
function linktodate(date, ajaxurl){
  var div = 'calendar_div';
  var resch_field = document.getElementById('resch');
  new Ajax.Updater(div, ajaxurl+'?date='+date+(((resch_field.value + "") == "0") ? '' : '&reschedule=' + resch_field.value), {asynchronous:true, evalScripts:true, onLoading:function(request){doLoading("calendar");}, onComplete:function(request){reloadmessages('msg','calendar'); starttimer(autorefreshtimer);}});
  dayHighlighter(date, ajaxurl);
}

function jumptodate(ajaxurl){
  var date = prompt('Date:', ' ');
  if(date){
    var resch_field = document.getElementById('resch');
    var div = 'calendar_div';
    var rfv = "0";
    if(resch_field){rfv = resch_field.value;}
    new Ajax.Updater(div, ajaxurl+'?date='+date+(((rfv + "") == "0") ? '' : '&reschedule=' + rfv), {asynchronous:true, evalScripts:true, onLoading:function(request){doLoading("calendar");}, onComplete:function(request){reloadmessages('msg','calendar'); starttimer(autorefreshtimer);}});
    new Ajax.Updater('selector_div', '/c:calendar/select?date='+date, {asynchronous:true, evalScripts:true});
  }
}

//used in the display view to link silently to a new week
function linktoweek(date, ajaxurl){
  var div = 'calendar_div';
  new Ajax.Updater(div, ajaxurl+'&date='+date, {asynchronous:true, evalScripts:true, onLoading:function(request){doLoading("calendar");}, onComplete:function(request){reloadmessages('msg','display');}});
  weekHighlighter(date, ajaxurl);
}

function linktoweek2(date, ajaxurl){
  var div = 'calendar2_div';
  new Ajax.Updater(div, ajaxurl+'&date='+date, {asynchronous:true, evalScripts:true, onLoading:function(request){doLoading("calendar2");}, onComplete:function(request){reloadmessages('msg','display');}});
  weekHighlighter2(date, ajaxurl);
}

//specifies the text and everything else that will go in the loading "screen"
function loadingContent(){
  return "<div id=\"load\"><img src=\"/images/loading.gif\" width=\"16\" height=\"16\" alt=\"\" /></div>";
}

//submits the form from the calendar view
function subajaxform(ajaxurl){
  var resch_field = document.getElementById('resch');
  new Ajax.Updater('box', ajaxurl+(((resch_field.value + "") == "0") ? '' : '?reschedule=' + resch_field.value), {asynchronous:true, evalScripts:true, parameters:Form.serialize(document.getElementById('calendar_form')), onComplete:function(request){if(request.status != 302){showBox();}}, on302:function(request){hideBox(false);}});
}

//submits an arbitrary "box" form to ajax
function ajaxform(ajaxurl, fname){
  new Ajax.Updater('box', ajaxurl, {asynchronous:true, evalScripts:true, parameters:Form.serialize(document.getElementById(fname)), on302:function(request){hideBox(true);}, onComplete:function(request){doLoading2(false);}, onLoading:function(request){doLoading2(true);}});
}

//this will clear lockouts for this user
function clearLockouts(){
  var date = document.getElementById("date").value;
  var resch_field = document.getElementById('resch');
  new Ajax.Request('/clear_lockouts', {asynchronous:true, evalScripts:true, on200:function(request){addMsg('Success: Lockouts Cleared')}, onFailure:function(request){addMsg('Error: Lockouts NOT Cleared')}, onLoading:function(request){new Ajax.Updater('calendar_div', '/c:calendar/yes'+'?date='+date+(((resch_field.value + "") == "0") ? '' : '&reschedule=' + resch_field.value), {asynchronous:true, evalScripts:true, onLoading:function(request){doLoading("calendar");}, onComplete:function(request){starttimer(autorefreshtimer);}});}});
}

//this function is for the view full details link
function linkViewFullDetails(rc, ap, d){
  var controller = document.getElementById('controller_name').value
  new Ajax.Updater('box', '/c:'+controller+'/details/:id:'+ap+'/:rid:'+rc+'/:adate:'+d+"/", {asynchronous:true, evalScripts:true, onComplete:function(request){if(request.status != 302){showBox();}}, on302:function(request){hideBox(false);}});
}

function ajax_aid_rid_link(url){
  var rid2 = document.getElementById("this_rid").value;
  var aid = document.getElementById("this_aid").value;
  var date = document.getElementById("date").value;
  new Ajax.Updater('box', url+'?rid='+rid2+'&aid='+aid+'&adate='+date, {asynchronous:true, evalScripts:true, onComplete:function(request){if(request.status != 302){showBox();}}, on302:function(request){hideBox(false);}});
}

function ajax_tid_link(url){
  var tid = document.getElementById("this_tid").value;
  var ttype = document.getElementById("this_ttype").value;
  new Ajax.Updater('box', url+'?tid='+tid+'&ttype='+ttype, {asynchronous:true, evalScripts:true, onComplete:function(request){if(request.status != 302){showBox();}}, on302:function(request){hideBox(false);}});
}

function rescheduleLink(){
  var date = document.getElementById("date").value;
  var aid = document.getElementById("this_aid").value;
  new Ajax.Updater('main_div', '/c:appointment/reschedule?aid='+aid+'&date='+date, {asynchronous:true, evalScripts:true, onComplete:function(request){windowload();}, onFailure:function(request){alert("failed");}, onLoading:function(request){doLoading("calendar");}});
}

// CREDITS:
// Derived from Automatic Page Refresher by Peter Gehrig and Urs Dudli

var startdate;
var starttime;
var nowtime;
var reloadseconds=0;
var secondssinceloaded=0;

function starttimer(nextload) {
  startdate=new Date();
  starttime=startdate.getTime();
  countdown_update(starttime, nextload);
}

function countdown_update(last, nextload) {
  var inHTML;
  nowdate= new Date();
  nowtime=nowdate.getTime();
  secondssinceloaded=(nowtime-starttime)/1000;
  reloadseconds=Math.round(nextload-secondssinceloaded);
  if (reloadseconds>=0) {
    reloadtext = ""
    reloadtext += Math.floor(reloadseconds / 60) + " minutes "
    reloadtext += (reloadseconds % 60) + " seconds"

  curr_hour = startdate.getHours();
  curr_min = startdate.getMinutes();
  curr_sec = startdate.getSeconds();

  //Simple time formatting
  var a_p = "";
  if (curr_hour < 12) {a_p = "AM";}
  else {a_p = "PM";}

  if (curr_hour == 0) {curr_hour = 12;}
  if (curr_hour > 12) {curr_hour = curr_hour - 12;}

  curr_min = curr_min + ""; // Convert to string
  if (curr_min.length == 1) {curr_min = "0" + curr_min;}
  //End time formatting

    inHTML = "";
    inHTML += "Last refreshed at "+curr_hour+":"+curr_min+":"+curr_sec+" "+a_p+" | ";
    inHTML += "Automatic refresh in "+reloadtext;
    if (document.getElementById("countdown_div")) document.getElementById("countdown_div").innerHTML = inHTML; // Avoids error when reloading main_div b/c countdown_div doesn't exist.

    setTimeout("countdown_update('"+last+"',"+nextload+")",1000);
  } else {
  if (document.getElementById("countdown_div")) {
      date = document.getElementById('date').value;
      var div = 'calendar_div';
      inHTML = "Reloading...";
      document.getElementById("countdown_div").innerHTML = inHTML;
      new Ajax.Request('/c:calendar/yes/?date='+date, {asynchronous:true, evalScripts:true, onComplete:function(request){document.getElementById('countdown_div').innerHTML = ""; document.getElementById(div).innerHTML = request.responseText; resetfields(); starttimer(nextload);}, onFailure:function(request){update_failed();}});
  }
  }

}  //showBox(); //This shows the popup box for testing purposes

function update_failed(last){
  var inHTML = "";
  inHTML += "Last Refresh at "+last+"<br />";
  inHTML += "Automatic Refresh failed.";
  document.getElementById("countdown_div").innerHTML = inHTML;
}

function reloadmessages(div, controller){
  new Ajax.Updater(div, '/c:application/msgerr_output', {asynchronous:true, evalScripts:true, insertion:Insertion.Top, onComplete:function(request){document.getElementById('tooltip').innerHTML = "";}});
}

function blockClicked2(time, wday, block){
  var st = document.getElementById("starttime");
  var pt = document.getElementById("stoptime");
  var rid = document.getElementById("wday");
  var cell = document.getElementById(block);
  var check = document.getElementById("check");
  var meth = document.getElementById("method");

  if(meth.value == "" || meth.value == "add"){
    if(st.value == "0"){
      document.getElementById("method").value = "add";
      document.getElementById("starttime").value = time;
      document.getElementById("wday").value = wday;

      //change the clicked block's color and stuff, and update the label of directions
      cell.style.backgroundColor='#c0c0c0'; //this should change to explicit calling in order to make className work
      cell.className = "timeslot selected";

      //save the row id of the block just clicked
      var i1 = block.indexOf('_');
      var i2 = block.substr(4, block.length).indexOf('_');
      //alert(i1 + " " + i2);
      var numlen = 4 + i2 - i1 - 1;
      //alert(numlen);
      document.getElementById("startrow").value = block.substr(4, numlen) * 1;
    }
    else{
      if(pt.value == "0" && time >= st.value && wday == rid.value){
        //set the field
        document.getElementById("stoptime").value = time;

        //highlight the intermediate blocks
        //numlen = (block.indexOf('C') - block.indexOf('R')) - 1;
        var i1 = block.indexOf('_');
        var i2 = block.substr(4, block.length).indexOf('_');
        var numlen = 4 + i2 - i1 - 1;
        var startrow = document.getElementById("startrow").value * 1;
        var stoprow = block.substr(4, numlen) * 1;

        for(i = startrow; i < (stoprow + 1); i++){
          document.getElementById("col_"+i+block.substr(4 + i2, block.length)).style.backgroundColor='#c0c0c0'; //this should change to className later
          document.getElementById("col_"+i+block.substr(4 + i2, block.length)).className = "timeslot selected";
        }

        //submit the form
        subajaxform2(document.getElementById("formajax").value);
      }
      else{
        //Error - reset everything
        resetfields2();
      }
    }
    } else {

    }

}

function blockClicked3(time, wday, block){
  var st = document.getElementById("starttime");
  var pt = document.getElementById("stoptime");
  var rid = document.getElementById("wday");
  var cell = document.getElementById(block);
  var check = document.getElementById("check");
  var meth = document.getElementById("method");

  if(meth.value == "" || meth.value == "del"){
    if(st.value == "0"){
      document.getElementById("method").value = "del";
      document.getElementById("starttime").value = time;
      document.getElementById("wday").value = wday;

      //change the clicked block's color and stuff, and update the label of directions
      cell.style.backgroundColor='#c0c0c0'; //this should change to explicit calling in order to make className work
      cell.className = "timeslot selected";

      //save the row id of the block just clicked
      var i1 = block.indexOf('_');
      var i2 = block.substr(4, block.length).indexOf('_');
      //alert(i1 + " " + i2);
      var numlen = 4 + i2 - i1 - 1;
      //alert(numlen);
      document.getElementById("startrow").value = block.substr(4, numlen) * 1;
    }
    else{
      if(pt.value == "0" && time >= st.value && wday == rid.value){
        //set the field
        document.getElementById("stoptime").value = time;

        //highlight the intermediate blocks
        //numlen = (block.indexOf('C') - block.indexOf('R')) - 1;
        var i1 = block.indexOf('_');
        var i2 = block.substr(4, block.length).indexOf('_');
        var numlen = 4 + i2 - i1 - 1;
        var startrow = document.getElementById("startrow").value * 1;
        var stoprow = block.substr(4, numlen) * 1;

        for(i = startrow; i < (stoprow + 1); i++){
          document.getElementById("col_"+i+block.substr(4 + i2, block.length)).style.backgroundColor='#c0c0c0'; //this should change to className later
          document.getElementById("col_"+i+block.substr(4 + i2, block.length)).className = "timeslot selected";
        }

        //submit the form
        subajaxform2(document.getElementById("formajax").value);
      }
      else{
        //Error - reset everything
        resetfields2();
      }
    }
    } else {

    }

}

function resetfields2(){
  var st = document.getElementById("starttime");
  var pt = document.getElementById("stoptime");
  var rid = document.getElementById("wday");
  var meth = document.getElementById("method");

  st.value = "0";
  pt.value = "0";
  rid.value = "8";
  meth.value = "";
}

function subajaxform2(ajaxurl){
  new Ajax.Updater('main_div', ajaxurl, {asynchronous:true, evalScripts:true, parameters:Form.serialize(document.getElementById('calendar_form')), onComplete:function(request){windowload2();}, onLoading:function(request){doLoading('main');}});
}

function username_lookup(user_name, targets_text){
  var all_targets_text_names = new Array();
  var temp;

  for(i = 0; i < targets_text.length; i++){
    temp = $(targets_text[i]);
    if(temp){
      all_targets_text_names[i] = targets_text[i];
    }
  }

  if(user_name == ''){
    for(i = 0; i < all_targets_text_names.length; i++){
      Element.update(all_targets_text_names[i], '');
    }
    return false;
  }
  var res;

  new Ajax.Request('/c:application/username_lookup?username='+user_name, {
    method: 'get',
    asynchronous:false,
    evalScripts:true,
    onComplete:function(request){
      res = eval('('+request.responseText+')');
      if(res.status == 0){
        for(i = 0; i < all_targets_text_names.length; i++){
          temp = document.getElementById(all_targets_text_names[i]);
          if(temp){
            document.getElementById(all_targets_text_names[i]).innerHTML = res.result;
          } else {
          }
        }
      } else {
        for(i = 0; i < all_targets_text_names.length; i++){
          document.getElementById(all_targets_text_names[i]).innerHTML = "";
        }
      }
    }
  }
  );

}

function fill_in_unavailable(fields){
  for(i = 0; i < fields.length; i++){
    document.getElementById(fields[i]).value = "Unavailable";
  }
}
