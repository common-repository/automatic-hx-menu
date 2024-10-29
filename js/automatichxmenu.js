jQuery(document).ready(function() {
    
    jQuery('#automatichxmenu a.link-nav, .automatichxmenu_to_top').on('click', function() { 
        page = jQuery(this).attr('href'); 
        speed = 1000; 
        offset = 0 ;

        if(typeof window.automatichx_smooth_offset !== 'undefined') {
            offset = window.automatichx_smooth_offset ;
        }
        if(typeof window.automatichx_smooth_speed !== 'undefined') {
            speed = window.automatichx_smooth_speed ;
        }
        scrollTop = jQuery(page).offset().top - offset ;
        jQuery('html, body').animate( { scrollTop: scrollTop }, speed );
        
        return false;
    });
});