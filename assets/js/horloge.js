
function horloge(){
       var div = document.getElementById("horloge");
       var heure = new Date();
       div.firstChild.nodeValue = heure.getHours()+"h : "+heure.getMinutes()+"mn : "+heure.getSeconds()+"s" ;
       window.setTimeout("horloge()",1000);
}
horloge();