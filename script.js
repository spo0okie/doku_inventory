/* DOKUWIKI:include tooltipster.main.min.js */
/* DOKUWIKI:include tooltipster.bundle.min.js */

function attach_qTip(el){
    if (el.hasClass('tooltipstered')) return;
    var $url=el.attr('qtip_ajxhrf');
    //var $title=el.attr('qtip_title');
    //var $classes=el.attr('qtip_class');
    var $text=el.attr('qtip_ttip');
    //console.log(typeof $text);
    if (typeof $text == 'undefined') {
        $text='Подгрузка...';
    }
    if (typeof $url != 'undefined') {
        $load=function(instance, helper) {
            var $origin = jQuery(helper.origin);
            if ($origin.data('loaded') !== true) {
                jQuery.get($url, function(data) {
                    instance.content(data);
                    $origin.data('loaded', true);
                });
            }
        }

    } else {
        $load=undefined;
    }
    el.tooltipster({
        animationDuration: 100,
        content: $text,
        contentAsHTML: true,
        delay: 50,
        interactive: true,
        theme: 'tooltipster-shadow tooltipster-shadow-yellow',
        updateAnimation: 'fade',
        side: ['bottom', 'top', 'right', 'left'],
        functionBefore: $load,
    });
}


function attachAllTTips(){
    jQuery('[qtip_ajxhrf]').not(".tooltipstered").each(function(){attach_qTip(jQuery(this));});
    jQuery('[qtip_ttip]').not(".tooltipstered").each(function(){attach_qTip(jQuery(this));});
}

jQuery(document).ready(function(){
    //setTimeout(attachAllTTips,500);
    setInterval(attachAllTTips,500);
});
