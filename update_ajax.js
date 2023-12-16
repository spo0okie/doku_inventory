
function update_inventory_item(el)
{
    if (!el.hasClass('muted')) {
        return;
    }
    let data=el.attr('data-update');
    let url='/lib/exe/ajax.php?call=inventpry&action=parse&data='+data;
    jQuery.get(url, function(data) {
        el.removeClass("muted").html(data);
        //el.children("span.object-item").each(function(){update_inventory_item(jQuery(this));});
        el.find('[qtip_ajxhrf]').not(".tooltipstered").each(function(){attach_qTip(jQuery(this));});
        el.find('[qtip_ttip]').not(".tooltipstered").each(function(){attach_qTip(jQuery(this));});
        el.find('[qtip_b64ttip]').not(".tooltipstered").each(function(){attach_qTip(jQuery(this));});
    });
};


function update_all_inventory_items()
{
    jQuery('.inventory_plugin_item.muted[data-update]').each(function(){update_inventory_item(jQuery(this));});
};

jQuery(document).ready(function(){
    setTimeout(update_all_inventory_items(),500);
    console.log("inventory async initialized");
});
