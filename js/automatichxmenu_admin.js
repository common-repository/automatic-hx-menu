jQuery( document ).ready(function() {
    jQuery('.ahxm-color').wpColorPicker();

    var tab_open = jQuery('input[name="automatichxmenu_options[open_tab]"]').val();
    if(tab_open !== 'ahxm-settings-info' && tab_open !==''){
        jQuery('.ahxm-tab').removeClass('ahxm-tab-active');
        jQuery('#'+tab_open).addClass('ahxm-tab-active');
        jQuery('.ahxm-nav').removeClass('ahxm-nav-active');
        jQuery('#'+tab_open+'-nav').addClass('ahxm-nav-active');
    }
    jQuery('.ahxm-menu a').click(function(e){
        e.preventDefault();
        var tab = jQuery(this).data('tab');
        jQuery('.ahxm-tab').removeClass('ahxm-tab-active');
        jQuery('#'+tab).addClass('ahxm-tab-active');
        jQuery('.ahxm-nav').removeClass('ahxm-nav-active');
        jQuery('#'+tab+'-nav').addClass('ahxm-nav-active');
        jQuery('input[name="automatichxmenu_options[open_tab]"]').attr('value', tab);
    });
    
});