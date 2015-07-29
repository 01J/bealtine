jQuery(function() {
  jQuery('dl.tabs dt').click(function() {
    jQuery(this).siblings().removeClass('selected').end().next('dd').andSelf().addClass('selected');
  });
});

jQuery(function($) {
  var url = location.href;
  if( url.indexOf("#tabs") == -1 ) return;
  if( url.indexOf("#tabs") !== -1 ) {
    url_arr = url.split("#")[1].split("-");
    $('#'+url_arr[0]+' #'+url_arr[1]).click();
  }
});

jQuery(document).ready(function() {
  if (elle_sliders_nfa == 1) {
    jQuery(".sliders .desc").hide();    
  } else {
    jQuery(".sliders .title:first").addClass("active");
    jQuery(".sliders .desc:not(:first)").hide();
  }
  jQuery(".sliders .title").click(function() {
    jQuery(this).next("div.desc").slideToggle("slow").siblings("div.desc:visible").slideUp();
    jQuery(this).toggleClass("active");
    jQuery(this).siblings(".title").removeClass("active");
  });

  jQuery(".spoilers .title").click(function() {
    jQuery(this).next("div.desc").slideToggle("slow");
    jQuery(this).toggleClass("active");
  });

});