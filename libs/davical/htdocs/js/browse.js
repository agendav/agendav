/**
* Simple function to send the browser to a given URL
*/
function Go( url ) {
  window.location=url;
  return true;
}

/**
* Make this tag into a Link to a given URL
*/
function LinkTo( tag, url ) {
  tag.style.cursor = "pointer";
  tag.setAttribute('onClick', "Go('" + url.replace(/&amp;/g,'&') + "')");
  tag.setAttribute('onMouseOut', "window.status='';return true;");
  window.status = window.location.protocol + '//' + document.domain + url;
  tag.setAttribute('onMouseover', "window.status = window.location.protocol + '//' + document.domain + '" + url + "';return true;");
  tag.setAttribute('href', url);
  return true;
}

/**
* Make this tag and all of it's contents into a clickable link, using the link target from an
* existing link target somewhere within the tag.  Setting 'which1' to '1' will make the target
* match the 1st href target within the HTML of the tag.
* @param objectref tag A reference to the object which will become clickable.
* @param int which1 A one-based index to select which internal href attribute will become the target.
*/
function LinkHref( tag, which1 ) {
  var urls = tag.innerHTML.match( / href="([^"]*)"/ig );
//  alert(show_props(urls,'urls', 1));
  try {
    var url = urls[which1 - 1];
    urls = url.match( /="([^"]*)"/ );
  }
  catch (e) {
    //alert("Here are the URLs found:\nYou appear to need to choose a different index for your LinkHref call (the second parameter).  Add 1 to the index below for the correct URL shown and use that.\n\n" + show_props(urls,'urls', 0));
    return false;
  }
//  alert(show_props(urls,'urls', 1));
  url = urls[1];
//  alert("Linking to >>>" + url + "<<<");
  LinkTo(tag,url);
  return true;
}

