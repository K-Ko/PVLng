function _log(){if(verbose){var e=new Date;$(arguments).each(function(o,n){console.log(e.toLocaleString()+"."+e.getMilliseconds()),console.log(n)})}}$(function(){});var verbose=!1;String.prototype.repeat=function(e){if(1>e)return"";for(var o="",n=this.valueOf();e>0;)1&e&&(o+=n),e>>=1,n+=n;return o};function presentation(t){this.v=2,this.axis=1,this.type=presentation_defaults.line.type,this.style="Solid",this.width=presentation_defaults.line.width,this.color=presentation_defaults.line.color,this.colorusediff=0,this.colordiff=presentation_defaults.line.color,this.consumption=!1,this.threshold=0,this.min=!1,this.max=!1,this.last=!1,this.all=!1,this.time1="00:00",this.time2="24:00",this.legend=!0,this.position=0,this.hidden=!1;try{t=JSON.parse(t),"undefined"==typeof t.v&&("1"===t.coloruseneg?t.colorusediff=-1:"0"===t.coloruseneg&&(t.colorusediff=0),t.colordiff=t.colorneg),delete t.coloruseneg,delete t.colorneg,$.extend(this,t)}catch(i){}this.toString=function(){return JSON.stringify(this)}}function setMinMax(t,i){if(0==t.data.length)return t;var e={min:t.data[0].x,max:t.data[t.data.length-1].x},r={id:null,x:null,y:Number.MAX_VALUE},o={id:null,x:null,y:-Number.MAX_VALUE},l=1==t.colorDiff?t.negativeColor:t.color;$.each(t.data,function(t,e){i.min&&e.y<r.y&&(r={id:t,x:e.x,y:e.y}),i.max&&e.y>o.y&&(o={id:t,x:e.x,y:e.y})}),null!=r.id&&(t.data[r.id].marker={enabled:!0,symbol:"triangle-down",fillColor:l},t.data[r.id].dataLabels={enabled:!0,formatter:function(){return Highcharts.numberFormat(+this.y,t.decimals)},color:l,style:{fontWeight:"bold"},borderRadius:3,backgroundColor:"rgba(252, 255, 197, 0.7)",borderWidth:1,borderColor:"#AAA",align:e.min+(e.max-e.min)/2-r.x>0?"left":"right",y:26},$("#min"+t.id).html(Highcharts.numberFormat(r.y,t.decimals)+" "+t.unit)),null!=o.id&&(t.data[o.id].marker={enabled:!0,symbol:"triangle",fillColor:l},t.data[o.id].dataLabels={enabled:!0,formatter:function(){return Highcharts.numberFormat(+this.y,t.decimals)},color:l,style:{fontWeight:"bold"},borderRadius:3,backgroundColor:"rgba(252, 255, 197, 0.7)",borderWidth:1,borderColor:"#AAA",align:e.min+(e.max-e.min)/2-o.x>0?"left":"right",y:-7},$("#max"+t.id).html(Highcharts.numberFormat(o.y,t.decimals)+" "+t.unit));var a=t.data.length-1;return i.last&&a!=o.id&&a!=r.id&&(t.data[a].marker={enabled:!0,symbol:"circle",fillColor:l},t.data[a].dataLabels={enabled:!0,formatter:function(){return Highcharts.numberFormat(+this.y,t.decimals)},color:l,style:{fontWeight:"bold"},borderRadius:3,backgroundColor:"rgba(252, 255, 197, 0.7)",borderWidth:1,borderColor:"#AAA",align:"right",overflow:!0,crop:!1,y:-7}),t}var presentation_defaults={HintDecimals:2,line:{type:"spline",width:2,color:"#404040"}};/* Type Rendering Mix JS - (c) 2013 Tim Brown, Bram Stein. License: new BSD */(function(){'use strict';var c=window;function d(a){var b=e,g;a:{g=b.className.split(/\s+/);for(var m=0,H=g.length;m<H;m+=1)if(g[m]===a){g=!0;break a}g=!1}g||(b.className+=(""===b.className?"":" ")+a)};function f(a,b,g){this.b=null!=a?a:null;this.c=null!=b?b:null;this.e=null!=g?g:null}var h=/^([0-9]+)(?:[\._-]([0-9]+))?(?:[\._-]([0-9]+))?(?:[\._+-]?(.*))?$/;function k(a,b){return a.b>b.b||a.b===b.b&&a.c>b.c||a.b===b.b&&a.c===b.c&&a.e>b.e?1:a.b<b.b||a.b===b.b&&a.c<b.c||a.b===b.b&&a.c===b.c&&a.e<b.e?-1:0}function l(a,b){return 0===k(a,b)||1===k(a,b)}
function n(){var a=h.exec(p[1]),b=null,g=null,m=null;a&&(null!==a[1]&&a[1]&&(b=parseInt(a[1],10)),null!==a[2]&&a[2]&&(g=parseInt(a[2],10)),null!==a[3]&&a[3]&&(m=parseInt(a[3],10)));return new f(b,g,m)};function q(){var a=r;return 3===a.a||7===a.a||6===a.a||9===a.a||8===a.a||5===a.a?"grayscale":1===a.a&&l(a.f,new f(6,2))&&1===a.d?"grayscale":"unknown"};var r,s=c.navigator.userAgent,t=0,u=new f,v=0,w=new f,p=null;if(p=/(?:iPod|iPad|iPhone).*? OS ([\d_]+)/.exec(s))v=3,w=n();else if(p=/(?:BB\d{2}|BlackBerry).*?Version\/([^\s]*)/.exec(s))v=9,w=n();else if(p=/Android ([^;)]+)|Android/.exec(s))v=5,w=n();else if(p=/Windows Phone(?: OS)? ([^;)]+)/.exec(s))v=8,w=n();else if(p=/Linux ([^;)]+)|Linux/.exec(s))v=4,w=n();else if(p=/OS X ([^;)]+)/.exec(s))v=2,w=n();else if(p=/Windows NT ([^;)]+)/.exec(s))v=1,w=n();else if(p=/CrOS ([^;)]+)/.exec(s))v=6,w=n();
if(p=/MSIE ([\d\w\.]+)/.exec(s))t=1,u=n();else if(p=/Trident.*rv:([\d\w\.]+)/.exec(s))t=1,u=n();else if(p=/OPR\/([\d.]+)/.exec(s))t=4,u=n();else if(p=/Opera Mini.*Version\/([\d\.]+)/.exec(s))t=4,u=n();else if(p=/Opera(?: |.*Version\/|\/)([\d\.]+)/.exec(s))t=4,u=n();else if(p=/Firefox\/([\d\w\.]+)|Firefox/.exec(s))t=3,u=n();else if(p=/(?:Chrome|CrMo|CriOS)\/([\d\.]+)/.exec(s))t=2,u=n();else if(p=/Silk\/([\d\._]+)/.exec(s))t=7,u=n();else if(5===v||9===v)t=6;else if(p=/Version\/([\d\.\w]+).*Safari/.exec(s))t=
5,u=n();r=new function(a,b,g,m){this.d=a;this.g=b;this.a=g;this.f=m}(t,u,v,w);var x=q(),y,z=q();y="unknown"!==z?z:2===r.a||4===r.a?"subpixel":1===r.a?l(r.f,new f(6,0))?"subpixel":1===r.d?l(r.g,new f(7,0))?"subpixel":"grayscale":"subpixel":"unknown";var e=c.document.documentElement,A;
if(1===r.a){var B,C;if(!(C=2===r.d)){var D;(D=4===r.d)||(D=-1===k(r.f,new f(6,0)));C=D}if(C)B="gdi";else{var E;if(l(r.f,new f(6,0))){var F;if(F=1===r.d){var G=r.g,I=new f(8,0);F=0===k(G,I)||-1===k(G,I)}E=F?"gdi":"directwrite"}else E="unknown";B=E}A=B}else A=8===r.a?"directwrite":2===r.a||3===r.a?"coretext":5===r.a||4===r.a||6===r.a||7===r.a||9===r.a?"freetype":"unknown";d("tr-"+A);"unknown"===x&&"unknown"!==y&&(x+="-"+y);d("tr-aa-"+x);}());