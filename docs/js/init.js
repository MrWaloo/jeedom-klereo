var docMenu = [
  {
    divider : true
  },{
    fr_FR : "Présentation",
    en_US : "Presentation",
    de_DE : "Präsentation",
    link : "/#LANG#/#VERSION#/index"
  },{
    fr_FR : "Changelog",
    en_US : "Changelog",
    de_DE : "Changelog",
    link : "/#LANG#/#VERSION#/changelog"
  },{
    divider : true
  }
]

if(getUrlVars('theme') == 'light' || getUrlVars('theme') == 'dark'){
  setCookie('theme',getUrlVars('theme'),7)
}

setTheme();

$('#ul_menu').empty();
var html = '';
var lang = 'fr_FR' ;
if(window.location.href.indexOf('fr_FR') != -1){
  lang = 'fr_FR'
}else if(window.location.href.indexOf('en_US') != -1){
  lang = 'en_US'
}else if(window.location.href.indexOf('de_DE') != -1){
  lang = 'de_DE'
}else if(getCookie('lang') != ''){
  lang = getCookie('lang');
}else{
  var userLang = navigator.language || navigator.userLanguage;
  userLang = userLang.toLowerCase();
  if(userLang.indexOf('en') !== -1){
    lang = 'en_US'
  }else if(userLang.indexOf('de') !== -1){
    lang = 'de_DE'
  }
}
if(getCookie('lang') != lang){
  setCookie('lang',lang,7)
}

$('#meta-lang').attr('content',lang)
var klereoVersion = 'beta'
if(window.location.href.indexOf('beta') != -1){
  klereoVersion = 'beta'
}else if(window.location.href.indexOf('stable') != -1){
  klereoVersion = 'stable'
}else if(getCookie('klereoVersion') != ''){
  klereoVersion = getCookie('klereoVersion');
}
if(getCookie('klereoVersion') != klereoVersion){
  setCookie('klereoVersion',klereoVersion,7)
}
if($('#sel_klereoVersion').val() != klereoVersion){
  $('#sel_klereoVersion').val(klereoVersion);
}
$('#sel_lang').val(lang);
for(var i in docMenu){
  var menu = docMenu[i]
  if(menu.divider){
    html += '<li><div class="divider"></div></li>';
  }else if(menu.submenu){
    html += '<li>';
    html += '<div class="collapsible-header">'+genText(menu,lang)+'</div>';
    html += '<div class="collapsible-body">';
    html += '<ul>';
    for(var j in menu.submenu){
      var submenu = menu.submenu[j];
      if(submenu.link == ''){
        continue;
      }
      if(submenu.version && submenu.version.indexOf(klereoVersion) == -1){
        continue;
      }
      html += '<li><a href="'+submenu.link.replace('#LANG#',lang).replace('#VERSION#',klereoVersion)+'">'+genText(submenu,lang)+'</a></li>';
    }
    html += '</ul>';
    html += '</div>';
    html += '</li>';
  }else{
    if(menu.link){
      if(menu.link == ''){
        continue;
      }
      html += '<li><a href="'+menu.link.replace('#LANG#',lang).replace('#VERSION#',klereoVersion)+'">'+genText(menu,lang)+'</a></li>';
    }else{
      html += '<li><strong style="margin-left:5px;" href="#!">'+genText(menu,lang)+'</strong></li>';
    }
  }
}
html += '<li class="small"><small>Jeedom: Free, Opened, Cloudless, Multiprotocol solution since 2014</small></li>'
$('#ul_menu').empty().html(html);

function genText(_menu,_lang){
  if(_menu.icon){
    return '<i class="'+_menu.icon+'"></i>'+_menu[lang];
  }else{
    return _menu[lang];
  }
}

$(function(){
  document.title = 'Documentation plugin Klereo'
  $('.sidenav').sidenav();
  setTimeout(function(){
    if ($('#slide-out').length > 0 && window.matchMedia("only screen and (max-width: 760px)").matches) {
      $('.sidenav').sidenav();
    }
  }, 100);
  setTimeout(function(){
    if ($('#slide-out').length > 0 && window.matchMedia("only screen and (max-width: 760px)").matches) {
      $('.sidenav').sidenav();
    }
  }, 250);
  setTimeout(function(){
    if ($('#slide-out').length > 0 && window.matchMedia("only screen and (max-width: 760px)").matches) {
      $('.sidenav').sidenav();
    }
  }, 500);
  $('.parallax').parallax();
  $('.dropdown-trigger').dropdown();
  $('.collapsible').collapsible();

  $('#div_summary').empty().append('<ul></ul>');
  $('#div_content h1,h2,h3').each(function(){
    var id = encodeURIComponent($(this).text());
    $(this).attr('id',id)
    if($(this).is('h1')){
      $('#div_summary ul').append('<li><a href="#'+id+'" class="tocAnchor">'+$(this).text()+'</a></li>')
    }
    if($(this).is('h2')){
      $('#div_summary ul').append('<li><a href="#'+id+'" class="tocAnchor" style="margin-left:10px;">'+$(this).text()+'</a></li>')
    }
    if($(this).is('h3')){
      $('#div_summary ul').append('<li><a href="#'+id+'" class="tocAnchor" style="margin-left:20px;">'+$(this).text()+'</a></li>')
    }
    $(this).addClass('scrollspy');
  });
  $('.scrollspy').scrollSpy();

  $('.tocAnchor').off('click').on('click',function(){
    window.location.hash = $(this).attr('href')
  });

  setTimeout(function() {
    $('#div_summary').pushpin({top:$('nav').height(),offset:$('nav').height()+10})
  }, 100);

  $('#sel_lang').on('change',function(){
    setCookie('lang',$(this).val(),7)
    var url = window.location.href.replace('fr_FR',$(this).val()).replace('en_US',$(this).val()).replace('de_DE',$(this).val());
    window.location.href = url;
  })

  $('#sel_theme').on('change',function(){
    setCookie('theme',$(this).val(),7)
    setTheme();
  })

  $('#sel_klereoVersion').on('change',function(){
    setCookie('klereoVersion',$(this).val(),7)
    var url = window.location.href;
    if(url.indexOf(getCookie('klereoVersion')) == -1){
      window.location.href = url.replace('beta',getCookie('klereoVersion')).replace('stable',getCookie('klereoVersion'))
      return;
    }
    window.location.reload();
  })

  $('select').formSelect();
  $('#div_content :not(td)>img').addClass('responsive-img')
  cookiesPolicyBar();
  setLeftMenu();

  if(window.location.hash) {
    document.getElementById(window.location.hash.replace('#','')).scrollIntoView()
    window.scrollBy(0, -200);
  }
});

function setTheme(){
  if (getCookie('theme') == 'dark') {
    $('html').addClass('dark')
    if ($('#sel_theme').val() != 'dark') {
      $('#sel_theme').val('dark');
    }
  } else {
    $('html').removeClass('dark')
  }
}

function getCookie(name) {
  var cookies = document.cookie.split(';');
  for(var i in cookies){
    var csplit = cookies[i].split('=');
    if(name.trim() == csplit[0].trim()){
      return csplit[1];
    }
  }
  return '';
}

function setCookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  var expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/;samesite=Lax;secure";
}

function cookiesPolicyBar(){
  if (getCookie('cookiePolicyAccept') != "active") $('#cookieAcceptBar').show();
  $('#cookieAcceptBarConfirm').on('click',function(){
    setCookie('cookiePolicyAccept', 'active', 30);
    $('#cookieAcceptBar').fadeOut();
  });
}

function setLeftMenu(){
  var url = window.location.href
  $('#ul_menu a').each(function(){
    if ($(this).attr('href') && url.indexOf($(this).attr('href')) != -1) {
      $(this).closest('li').addClass('menu_active')
      if ($(this).closest('li').closest('ul').closest('li')) {
        $(this).closest('li').closest('ul').closest('li').find('.collapsible-header').click()
        scrollMenu()
        return false
      }
    }
  })
}

function scrollMenu() {
  setTimeout(function(){
    var container = $('#slide-out')
    var pos = container.find('li.menu_active').offset().top
    var winHeight = $(window).height()
    if (pos > winHeight-100) {
      container.animate({scrollTop: pos-(winHeight/4)})
    }
  }, 500)
}

function getUrlVars(_key) {
  var vars = [], hash, nbVars = 0;
  var hashes = window.location.search.replace('?','').split('&');
  for (var i = 0; i < hashes.length; i++) {
    if (hashes[i] !== "" && hashes[i] !== "?") {
      hash = hashes[i].split('=');
      nbVars++;
      vars[hash[0]] = hash[1];
      if (_key && _key == hash[0]) {
        return hash[1];
      }
    }
  }
  if (!_key) {
    return false;
  }
  vars.length = nbVars;
  return vars;
}
