<?php
/*
Plugin Name: Automatic Hx Menu
Plugin URI: http://wordpress.org/plugins/automatic-hx-menu/
Description: Creating a menu automatically from the titles of an article
Version: 2.7.3
Author: Julien Crego
Author URI: http://dev.juliencrego.com/automatic-hx-menu/
Text Domain: automatichxmenu
*/

// Translation
add_action('plugins_loaded', 'automatichxmenu_textdomain' );
function automatichxmenu_textdomain() {
    load_plugin_textdomain('automatichxmenu', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' ); 
}


if(!is_admin()){
    $automatichxmenu = new automatichxmenu();   
} else {
    $automatichxmenu = new automatichxmenuAdmin();
}
class automatichxmenuOptions {
    private $options ;
    private $options_default = array(
        'open_tab' => '',
        
        'hx_begin_level' => 2,
        'hx_max_level' => 3,
        'hx_min_number' => 2,
        'menu_title_on' => 'yes',
        'home' => 'no',
        
        'smooth_scroll_active' => 'yes',
        'smooth_scroll_speed' => '',
        'smooth_scroll_offset' => '',

        'content_type' => 'all',
        'mode' => 'automatic',
        'collapse_on' => 'no', 
        'collapse_button_align' => 'flex-start',
        'collapse_button_bgcolor' => '#727272',
        'collapse_button_txtcolor' => '#ffffff',

        'to_top_active' => 'no',
        'to_top_pages' => 'everywhere',
        'to_top_text' => '&#9650;',
        'to_top_where' => 'menu',
        'to_top_custom_id' => '',
        
        'css_custom' => '',
        'css_position' => 'center',
        'css_width' => '100%',
        'css_width_small' => '768px',
        'css_margin' => '10px auto',
        'css_padding' => '10px',
        'css_margin_ul' => '15px',
        'css_margin_vertical' => '5px',
        'css_background_color'=> '',
        'css_border_color'=> '',
        'css_border_radius'=> '5px',
        'css_border_style'=> 'none',
        'css_border_width'=> '1px',

        'menu_title_on' => 'yes',
        'menu_title_align' => '',
        'menu_title_style' => '',
        'menu_title_color' => '',
        'numbering_active' => 'no',
        'numbering_nav_style' => '',
        'numbering_nav_color' => '',
        'numbering_title_style' => '',
        'numbering_title_color' => ''
    );

    public function __construct(){
        $this->options = get_option('automatichxmenu_options');
    }

    public function get($option){
        if(isset($this->options[$option])){
            return $this->options[$option] ;
        } elseif(isset($this->options_default[$option])){
            return $this->options_default[$option] ;
        } else {
            return false ;
        }
    }
    public function getDefault($option = false){
        if(!$option){
            return $this->options_default;
        }
        elseif(isset($this->options_default[$option])){
            return $this->options_default[$option] ;
        } else {
            return false ;
        }
    }
}
/**
 * Frontend class
 */
class automatichxmenu {
    private $plugin_folder = 'automatic-hx-menu';
    private $options = false ;
    private $menu = false ;
    private $shortcode_on = false ;
    public $content_id_counter = 0 ;
    private $to_top_id = false ;
    private $to_top_link = false ;

    public function __construct(){

        $this->opt = new automatichxmenuOptions();
        add_action('wp_enqueue_scripts', array($this, 'addCss'),15);

        
        if($this->opt->get('smooth_scroll_active') == 'yes' ){
            add_action('wp_enqueue_scripts', array($this,'addJs') );
        }

        if($this->opt->get('to_top_active') == 'yes'){
            $this->to_top_id = "automatichxmenu_top" ;

            switch($this->opt->get('to_top_where')) {
                case 'window' :
                    add_action( 'wp_body_open', array($this,'add_html_to_top_id') );
                    break;
                case 'custom' :
                    if(trim($this->opt->get('to_top_custom_id')) != "") {
                        $this->to_top_id = $this->opt->get('to_top_custom_id') ;
                    }
                    break;
            }

            $this->to_top_link = '<a class="automatichxmenu_to_top" href="#'.$this->to_top_id.'">'.trim($this->opt->get('to_top_text')).'</a>' ;
        }
         
        // Shortcode activation if exist
        if($this->opt->get('mode') == 'all' or $this->opt->get('mode') == 'shortcode' ){
            add_shortcode('automatichxmenu', array($this,'shortcodeLauncher') );
            add_action( 'init', array($this,'contentManager') );
        }
        // If no shortcode is found
        if(!$this->shortcode_on && ($this->opt->get('mode') == 'all' or $this->opt->get('mode') == 'automatic')){
            add_action( 'init', array($this,'contentManager') );
        } 

        if($this->opt->get('mode') == 'disabled' or $this->opt->get('mode') == 'automatic'){
            add_shortcode('automatichxmenu', array($this,'shortcodeEmpty') );
        }

        // If disabled but numbering is on
        if($this->opt->get('mode') == 'disabled' && $this->opt->get('numbering_active') == 'yes'){
            $this->shortcode_on = true ;
            add_action( 'init', array($this,'contentManager') );
        }
        /* Inline script printed out in the header */
        add_action('wp_head', array($this,'add_script_wp_head'),5);

    }

    public function add_script_wp_head() {
        if( $this->opt->get('smooth_scroll_active') == "yes" &&
            (intval($this->opt->get('smooth_scroll_offset')) > 0 or intval($this->opt->get('smooth_scroll_speed')) > 0)) :
            $script = '<script type="text/javascript">' ;
                if(intval($this->opt->get('smooth_scroll_offset')) > 0){
                    $script .= 'var automatichx_smooth_offset = '.intval($this->opt->get('smooth_scroll_offset')).' ;';
                }
                if(intval($this->opt->get('smooth_scroll_speed')) > 0){
                    $script .= 'var automatichx_smooth_speed = '.intval($this->opt->get('smooth_scroll_speed')).' ;';
                }
            $script .= '</script>';
            echo $script ;
        endif;
    }

    public function add_html_to_top_id() {
        echo '<div id="'.$this->to_top_id.'"></div>';
    }
    
    public function shortcodeEmpty() {
        return '';
    }
    
    public function shortcodeLauncher( $atts, $content = null ) {
        $this->shortcode_on = true ;
        
        // ShortCode Attributes
        $this->shortcode_atts = shortcode_atts( array('forced' => false), $atts );

        $content = get_the_content();
        $this->menuGenerator($content);

        ob_start();
        echo $this->menu ;
        $output = ob_get_clean();
        return $output;
    }
    

    public function contentManager() {
            add_filter('the_content', array($this, 'contentGenerator') , 12);
            add_filter('get_the_content', array($this, 'contentGenerator'), 12);
    }
    
    
    public function contentGenerator($content){
        if($this->opt->get('mode') != 'shortcode' ){
            if(!$this->menu){
                $this->menuGenerator($content);
            }
        }

        if(!$this->menu && $this->opt->get('to_top_pages') == 'nav'){
            $this->to_top_link = false ;
        }

        $this->content_id_counter = 0 ;

        $content = preg_replace_callback( '#(\<h['.$this->opt->get('hx_begin_level').'-'.$this->opt->get('hx_max_level').'])(.*?)\>(.*)(<\/h['.$this->opt->get('hx_begin_level').'-'.$this->opt->get('hx_max_level').']>)#i', function( $matches ) {
            if (!stripos( $matches[0], 'id=' ) ) :
                $this->content_id_counter++;
                if(!preg_match('#(class=.)#i',$matches[2])){
                    $matches[2] = $matches[2].' class="ahxm-title"';
                } else {
                    $matches[2] = preg_replace('#(class=.)#i','$1ahxm-title ',$matches[2]);
                }  
                $matches[0] = $matches[1] . $matches[2] . ' id="' . sanitize_title( $matches[3] ) .'-'.$this->content_id_counter. '">' . $matches[3]. $this->to_top_link. $matches[4];
            endif;
            return $matches[0];
        }, $content );
 
        if(!$this->shortcode_on){
            return $this->menu.$content;
        } else {
            return $content;
        }
    }
    
    public function menuGenerator($content) {
        if((is_home() or is_front_page()) && $this->opt->get('home') != 'yes'){
            return $content ;
        }
        if($this->opt->get('content_type') == 'all' && !is_singular()){
            return $content ;
        } elseif($this->opt->get('content_type') == 'posts' && !is_single()) {
            return $content ;
        } elseif($this->opt->get('content_type') == 'pages' && !is_page()) {
            return $content ;
        }

        preg_match_all('/<h(['.$this->opt->get('hx_begin_level').'-'.$this->opt->get('hx_max_level').']).*>(.+)<\/h['.$this->opt->get('hx_begin_level').'-'.$this->opt->get('hx_max_level').']+>/U', $content, $matches, PREG_SET_ORDER);
        
        if(count($matches) < $this->opt->get('hx_min_number')){
            return $content ;
        }

        $this->menu = '<nav id="automatichxmenu">';

        if($this->opt->get('to_top_active') == 'yes' && $this->opt->get('to_top_where') != 'window'){
            ob_start();
            $this->add_html_to_top_id();
            $this->menu .= ob_get_clean();
        }
        
        if($this->opt->get('menu_title_on') == 'yes'){
            $this->menu .= '<header>'.  __( "Table of Contents", 'automatichxmenu') .'</header>';
        }

        $titles = array();
        $j = 0;
        $parents = array();
        foreach($matches as $title) : 
            if($title[1] >= $this->opt->get('hx_begin_level') && $title[1] <= $this->opt->get('hx_max_level')){
                $j++; 
                $titles[$j]['id'] = $j ;
                $titles[$j]['title'] = $title[2] ;
                $titles[$j]['level'] =  $title[1] ;
                $titles[$j]['html_id'] = sanitize_title($title[2]).'-'.$j ;
                $titles[$j]['submenu'] = false ;
                $titles[$j]['parent'] = 0 ;
                
                if($j==1){
                    $parents[$title[1]] = 0;
                }                
                if($j>1 && $titles[$j]['level'] > $titles[$j - 1]['level']){
                    $titles[$j - 1]['submenu'] = true;
                }

                if($j>1 && $titles[$j - 1]['submenu'] == true){
                    $parents[$title[1]] = $titles[$j - 1]['id'];
                }
                $titles[$j]['parent'] = $parents[$title[1]] ;
            }
        endforeach;
        
        $this->menu .= $this->menuRecursive($titles);
        $this->menu .= '</nav>';
    }

    function menuRecursive( $titles, $level = 0) {
        if($level == 0){
            $ul_class = ($this->opt->get('collapse_on') == 'yes')?'ahxm-menu-nav ahxm-collapsible':'ahxm-menu-nav';
            $r = '<ul class="'.$ul_class.'">';
        } else {
            $r = '<ul>';
        }
        // if($this->opt->get('collapse_on') == 'yes'){
        //     $label_style = 'style="' ;
        //     $label_style = 'background-color:#f00';
        //     $label_style .= '"';
        // }
        
        foreach( $titles as $t ) {
            if($t['parent'] == $level ) {
                $li_class = ($this->opt->get('collapse_on') == 'yes')?'ahxm-title'.$t['level'].'-nav ahxm-title-collapsible':'ahxm-title'.$t['level'].'-nav';
                $r .= '<li class="'.$li_class.'" >';
                if($this->opt->get('collapse_on') == 'yes'){}
                $r .= '<a href="#'.$t['html_id'].'" class="link-nav" data-target="'.$t['html_id'].'">'.$t['title'].'</a>' ;
                if($this->opt->get('collapse_on') == 'yes' && $t['submenu']){
                    $r .= '<input type="checkbox" class="ahxm-collapse-btn" id="collapse-'.$t['html_id'].'">';
                    $r .= '<label for="collapse-'.$t['html_id'].'"></label>';
                }
                $r .= $this->menuRecursive( $titles, $t['id'] ) ;
                $r .= '</li>';
            }
        }
        $r = $r . "</ul>";
        return $r;
     }

   
    function menuGeneratorList(&$titles, $level=0) {
        if(count($titles)>0){
            $this->menu .= '<ul>' ;
        
            foreach($titles as $key => $t){
                echo $t['title'].'#'.$t['level'].'#'.$level.'<br>';
                if($t['level'] <= $level){
                    $this->menu .= '</ul>' ;
                }
                if(count($titles)>0){
                    $this->menu .= '<li class="ahxm-title'.$t['level'].'-nav">';
                    $this->menu .= '<a href="#'.$t['id'].'" class="link-nav" data-target="'.$t['id'].'">'.$t['title'].'</a>' ;
                    if($t['submenu'] && count($titles)>0) {
                        $this->menuGeneratorList($titles, $t['level']);
                    }
                }
                
                $this->menu .= '</li>' ;
            }
            $this->menu .= '</ul>' ;
        }
    }

    function AddJs(){
        
        wp_register_script('automatichxmenu-js', plugin_dir_url( __FILE__ ).'js/automatichxmenu.js', array( 'jquery'));
        wp_enqueue_script( 'automatichxmenu-js' );
    }

    function addCss() {
        wp_register_style('automatichxmenu', plugins_url( $this->plugin_folder.'/css/automatichxmenu.min.css') );
	    wp_enqueue_style('automatichxmenu');  
        $this->addCssCustom();
    }
    
    function addCssCustom() {
        //All the user input CSS settings as set in the plugin settings
        $custom_css = "#automatichxmenu { ";
		$custom_css .= $this->addCssProperty('css_margin','margin');
        $custom_css .= $this->addCssProperty('css_padding','padding');
		switch($this->opt->get('css_position')){
            case 'center':
                $custom_css .= "margin-left:auto;margin-right:auto;";
                break;
            default:
                $custom_css .= $this->addCssProperty('css_position','float');
                break;
        }
        $custom_css .= $this->addCssProperty('css_margin','margin');
        $custom_css .= $this->addCssProperty('css_padding','padding');
        $custom_css .= $this->addCssProperty('css_background_color','background-color');
        $custom_css .= $this->addCssProperty('css_width','width');
        $custom_css .= $this->addCssProperty('css_border_color','border-color');
        $custom_css .= $this->addCssProperty('css_border_style','border-style');
        $custom_css .= $this->addCssProperty('css_border_radius','border-radius');
        $custom_css .= $this->addCssProperty('css_border_width','border-width');
        $custom_css .= "}";
        $custom_css .= "#automatichxmenu ul { ";
        $custom_css .= "margin: 0px;";
        $custom_css .= $this->addCssProperty('css_margin_ul','margin-left');
        $custom_css .= "}";
        $custom_css .= "#automatichxmenu li { ";
        $custom_css .= "margin: 0px;";
        $custom_css .= $this->addCssProperty('css_margin_vertical','margin-top');
        $custom_css .= "}";

        // To top links -------------------------------------------------------
        // if($this->opt->get('to_top_pages') != 'everywhere'){
        //     $custom_css .= ".automatichxmenu_to_top {
        //                         display: none ;
        //                     }
        //                     #automatichxmenu ~ h2 .automatichxmenu_to_top,
        //                     #automatichxmenu ~ h3 .automatichxmenu_to_top,
        //                     #automatichxmenu ~ h4 .automatichxmenu_to_top,
        //                     #automatichxmenu ~ h5 .automatichxmenu_to_top,
        //                     #automatichxmenu ~ h6 .automatichxmenu_to_top {
        //                         display: inline-block ;
        //                     }";
        // }

        // Menu title styles --------------------------------------------------
        $custom_css .= "#automatichxmenu header { ";
        if($this->opt->get('numbering_nav_style') != ""){ $custom_css .= $this->opt->get('menu_title_style');}
        $custom_css .= $this->addCssProperty('menu_title_color','color');
        $custom_css .= $this->addCssProperty('menu_title_align','text-align');
        $custom_css .= "}";
        
        // Media queries for small screen --------------------------------------
        $custom_css .= "@media screen and (max-width: ".$this->opt->get('css_width_small').") {
                            #automatichxmenu {
                                width:100%;
                            }
                        }";

        // Collapse -----------------------------------------------------------
        if($this->opt->get('collapse_on') == 'yes'){
            $custom_css .= "#automatichxmenu .ahxm-title-collapsible {";
            $custom_css .= "justify-content:".$this->opt->get('collapse_button_align').";";
            $custom_css .= "}";        

            $custom_css .= "#automatichxmenu .ahxm-collapsible label {";
            $custom_css .= "background-color:".$this->opt->get('collapse_button_bgcolor').";";
            $custom_css .= "color:".$this->opt->get('collapse_button_txtcolor').";";
            $custom_css .= "}";
        }
        
        // Custom CSS ---------------------------------------------------------
        $custom_css .= $this->opt->get('css_custom');
        
        if($this->opt->get('numbering_active') == 'yes'){
            ob_start();
						
            ?>			
            #automatichxmenu ul { list-style: none;} 
            body { counter-reset: ahxmtitle1 ahxmtitle1nav 
                                  ahxmtitle2 ahxmtitle2nav 
                                  ahxmtitle3 ahxmtitle3nav 
                                  ahxmtitle4 ahxmtitle4nav 
                                  ahxmtitle5 ahxmtitle5nav 
                                  ahxmtitle6 ahxmtitle6nav  ;}
            <?php                 
                for($i = $this->opt->get('hx_begin_level') ; $i < ($this->opt->get('hx_max_level') + 1); $i++): 
                    $content = $content_nav = '';
                    $j = $i; //2  - 3 
                    $k = $this->opt->get('hx_begin_level') ;
                    do {
                        $content .= ' counter(ahxmtitle'.$k.') ".  "' ; 
                        $content_nav .= ' counter(ahxmtitle'.$k.'nav) ".  "' ;
                        $k++;
                        $j-- ;
                    } while ($j >= $this->opt->get('hx_begin_level') ); // 
                    ?>
                    h<?php echo $i ; ?>.ahxm-title { 
                        counter-increment: ahxmtitle<?php echo $i ;?> ;
                    }
                    h<?php echo $i ; ?>.ahxm-title::before { 
                        content: <?php echo $content ; ?> ; 
                    }
                    li.ahxm-title<?php echo $i ; ?>-nav  {
                        counter-increment: ahxmtitle<?php echo $i ;?>nav ;
                    }
                    li.ahxm-title<?php echo $i ; ?>-nav a::before {
                        content: <?php echo $content_nav ; ?> ;
                    }
                    <?php
                    $reset = $reset_nav = '';
                    for($l = $i+1 ; $l < $this->opt->get('hx_max_level') + 1 ; $l++ ) {
                        $reset .= ' ahxmtitle'.$l ; 
                        $reset_nav .= ' ahxmtitle'.$l.'nav' ; 
                        $j++;
                    }
                    ?>
                    h<?php echo $i ; ?>.ahxm-title {
                        counter-reset: <?php echo $reset; ?>;
                    }
                    li.ahxm-title<?php echo $i ; ?>-nav  {
                        counter-reset: <?php echo $reset_nav; ?>;	
                    }
            <?php endfor; ?>
            .ahxm-title::before {
                <?php if($this->opt->get('numbering_title_style')){ echo $this->opt->get('numbering_title_style');}?>
                <?php if($this->opt->get('numbering_title_color')){ echo "color: ".$this->opt->get('numbering_title_color');}?>
            }
            li.ahxm-title1-nav a::before, 
            li.ahxm-title2-nav a::before, 
            li.ahxm-title3-nav a::before, 
            li.ahxm-title4-nav a::before, 
            li.ahxm-title5-nav a::before, 
            li.ahxm-title6-nav a::before {
                <?php if($this->opt->get('numbering_nav_style')){ echo $this->opt->get('numbering_nav_style');}?>
                <?php if($this->opt->get('numbering_nav_color')){ echo "color: ".$this->opt->get('numbering_nav_color');}?>
            }
	          
            <?php $custom_css .= ob_get_clean();
        }
        // clean CSS spaces
        $custom_css = preg_replace('/\s+/', ' ', $custom_css);

        //Add the above custom CSS via wp_add_inline_style
        wp_add_inline_style( 'automatichxmenu', $custom_css ); //Pass the variable into the main style sheet ID
    }
    function addCssProperty($option, $property){
        if($this->opt->get($option) != ""){
            return $property.":".$this->opt->get($option).";"; 
        }
        return false ;
    }

    
}


/**
 * Administration class
 */

class automatichxmenuAdmin {
    private $options = false ;
    private $paypal_url = "https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HQGVXXY4XYXG2&source=url";
    private $plugin_folder = 'automatic-hx-menu';
    private $plugin_name = 'automatichxmenu'; 
    private $wpdb = false ;
    
    public function __construct(){
        global $wpdb;
        $this->wpdb = $wpdb ;
        
        add_action('admin_init', array( $this,'register_settings_and_fields'));
        add_action('admin_menu', array( $this,'menu_page_init'));
                
        // Activation hook
        register_activation_hook( __FILE__, array( $this,'plugin_activate'));
        
        // Getting the plugin options
        $this->opt = new automatichxmenuOptions();
        
        // Manage settings version
        if(!function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugin_data = get_plugin_data( __FILE__ ); 
        $this->plugin_version = $plugin_data['Version'];
        
        // Loading Js && CSS
        if( isset($_GET['page']) && $_GET['page'] == 'automatichxmenu-options'){
            add_action( 'admin_enqueue_scripts', array($this,'load_admin_style') );
            add_action( 'admin_enqueue_scripts', array($this,'load_admin_js') );
        }
    }
    
    /***************************************************************************
     * HTML
     **************************************************************************/
    
    function options_page_html() { 
        ?>
        <div id="automatichxmenu">
            <header>
                <h1>Automatic Hx Menu</h1>
                <span id="logo"></span>
            </header>

            <div>
                <div class="ahxm-menu">
                    <ul>
                        <li><a href="#ahxm-settings-info" id="ahxm-settings-info-nav" data-tab="ahxm-settings-info" class="ahxm-nav ahxm-nav-active">
                                <span class="dashicons dashicons-info"></span> <?php echo __( "Presentation", 'automatichxmenu'); ?>
                            </a>
                        </li>
                        <li><a href="#ahxm-settings-main" id="ahxm-settings-main-nav" data-tab="ahxm-settings-main" class="ahxm-nav">
                                <span class="dashicons dashicons-admin-settings"></span> <?php echo __( "Main settings", 'automatichxmenu'); ?>
                            </a>
                        </li>
                        
                        <li>
                            <a href="#ahxm-settings-collapse" id="ahxm-settings-collapse-nav"  data-tab="ahxm-settings-collapse" class="ahxm-nav">
                                <span class="dashicons dashicons-plus-alt"></span> <?php echo __( "Collapse", 'automatichxmenu'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#ahxm-settings-number" id="ahxm-settings-number-nav"  data-tab="ahxm-settings-number" class="ahxm-nav">
                                <span class="dashicons dashicons-editor-ol"></span> <?php echo __( "Numbering", 'automatichxmenu'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#ahxm-settings-css" id="ahxm-settings-css-nav"  data-tab="ahxm-settings-css" class="ahxm-nav">
                                <span class="dashicons dashicons-art"></span> <?php echo __( "Display", 'automatichxmenu'); ?>
                            </a>
                        </li>
                    </ul>

                    <div class="ahxm-donate">
                        <p>Version <?php echo $this->plugin_version; ?></p>
                        <p><strong><?php echo __( "Support the project", 'automatichxmenu'); ?></strong></p>
                        <a class="button button-primary" href="<?php echo $this->paypal_url;?>">
                            <?php echo __( "Donate", 'automatichxmenu'); ?>
                        </a>                        
                    </div>  
                    
                </div>

                <form method="post" action="options.php" enctype="multipart/form-data">
                    <?php settings_fields( 'automatichxmenu_options' ); ?>
                    <div id="ahxm-settings-info" class="ahxm-tab ahxm-tab-active">
                        <h2><?php echo __( "What is this plugin for ?", 'automatichxmenu'); ?></h2>
                        <p><?php echo __( "This extension allows to integrate a menu of navigation inside your articles/pages. It is based on the titles found in the article/page.", 'automatichxmenu'); ?></p>
                        <p><?php echo __( "This means that you have to tag your articles/pages with heading tags <code>&lt;h1&gt;&lt;/h1&gt;</code>, <code>&lt;h2&gt;&lt;/h2&gt;</code>, and so on.", 'automatichxmenu'); ?></p>
                        
                        <h2><?php echo __( "Choose your mode of operation", 'automatichxmenu'); ?></h2>
                        <p><?php echo __( "This extension allows to integrate a menu of navigation inside your articles/pages :", 'automatichxmenu'); ?> 
                        <ul style="list-style: circle;margin-left: 30px;">
                                <li><?php echo __( "Automatically for all your articles according to the defined settings.", 'automatichxmenu'); ?></li>
                                <li><?php echo __( "Manually by inserting the shortcode <code>[automatichxmenu]</code> into your articles.", 'automatichxmenu'); ?></li>
                                <li><?php echo __( "Automatically & manually. The shortcode will always have priority over the automatic menu.", 'automatichxmenu'); ?></li>
                        </ul>
                        <h2><?php echo __( "Numbering titles", 'automatichxmenu'); ?></h2>
                        <p><?php echo __( "This extension can also allow you to automatically numbering your titles by adding the corresponding CSS. You can use automatic numbering independently of the navigation menu integration.", 'automatichxmenu'); ?></p>
						
                        <h2><?php echo __( "Help for translation", 'automatichxmenu'); ?></h2>
                        <p><a href="https://wordpress.org/support/plugin/automatic-hx-menu/"><?php echo __( "If you are interested to help me to correct this english translation, go to plugin page.", 'automatichxmenu'); ?> </a></p>
			
                    </div>
                    
                    <div id="ahxm-settings-main" class="ahxm-tab">
                        <h2><?php echo __( "Main settings", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_main'); ?>
                        </table>

                        <h2><?php echo __( "Smooth scroll", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_smooth_scroll'); ?>
                        </table>

                        <h2><?php echo __( "To the top", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_to_top'); ?>
                        </table>
                        <?php submit_button(); ?>
                    </div>

                    <div id="ahxm-settings-collapse" class="ahxm-tab">
                        <h2><?php echo __( "Collapse", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_collapse'); ?>
                        </table>

                        <h2><?php echo __( "Button display", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_display_collapse'); ?>
                        </table>
                        <?php submit_button(); ?>
                    </div>
                    
                    <div id="ahxm-settings-number" class="ahxm-tab">
                        <h2><?php echo __( "Numbering", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_number'); ?>
                        </table>
						<h2><?php echo __( "Numbers in menu", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_number_nav'); ?>
                        </table>
						<h2><?php echo __( "Numbers in article", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_number_title'); ?>
                        </table>
                        <?php submit_button(); ?>
                    </div>

                    <div id="ahxm-settings-css" class="ahxm-tab">
                        <h2><?php echo __( "Display", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_display'); ?>
                        </table>
						<h2><?php echo __( "Menu title", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_display_title'); ?>
                        </table>
						<h2><?php echo __( "Borders", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_display_borders'); ?>
                        </table>
						<h2><?php echo __( "Custom CSS", 'automatichxmenu'); ?></h2>
                        <table class="form-table">
                            <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_display_custom'); ?>
                        </table>
                        <?php submit_button(); ?>
                    </div>

                    <div style="display: none;">
                        <?php do_settings_fields( __FILE__ ,'ahxm_settings_section_hidden'); ?>
                    </div>
                </form>   
            </div> 
        </div>
        <?php
    }

    function html_section_callback() {
        echo "<hr/>";
    }
    function html_generic_yesno_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <input type="radio" name="automatichxmenu_options[<?php echo $name; ?>]" value="yes" checked="checked"/> <?php _e( "yes", 'automatichxmenu'); ?>
        <input type="radio" name="automatichxmenu_options[<?php echo $name; ?>]" value="no" <?php if($value=='no'){ echo ' checked="checked"';}?> /> <?php _e( "no", 'automatichxmenu'); ?>
        <?php if(isset($option['description'])) : ?>
            <p class="description"><?php echo $option['description'].' '.$this->html_default_value_txt($name) ; ?></p>
        <?php	endif;	
    }
    function html_generic_text_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <input type="text" name="automatichxmenu_options[<?php echo $name; ?>]" value="<?php echo $value; ?>"/>
        <?php if(isset($option['description'])) : ?>
            <p class="description"><?php echo $option['description'].' '.$this->html_default_value_txt($name) ; ?></p>
        <?php	endif; 
    }
    function html_generic_color_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <input type="text" name="automatichxmenu_options[<?php echo $name; ?>]" class="ahxm-color" value="<?php echo $value; ?>"/>
        <?php if(isset($option['description'])) : ?>
            <p class="description"><?php echo $option['description'].' '.$this->html_default_value_txt($name) ; ?></p>
        <?php	endif; 
    }
    function html_generic_longtext_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <textarea name="automatichxmenu_options[<?php echo $name; ?>]"><?php echo $value; ?></textarea>
        <?php if(isset($option['description'])) : ?>
            <p class="description"><?php echo $option['description'].' '.$this->html_default_value_txt($name) ; ?></p>
        <?php	endif; 
    }
    function html_generic_integer_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <input type="number" name="automatichxmenu_options[<?php echo $name; ?>]" value="<?php echo $value; ?>"/>
        <?php if(isset($option['description'])) : ?>
            <p class="description"><?php echo $option['description'].' '.$this->html_default_value_txt($name) ; ?></p>
        <?php	endif; 
    }
    function html_generic_select_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <select name="automatichxmenu_options[<?php echo $name; ?>]">
            <?php foreach($option['options'] as $option_txt => $option_value) :?>
                <option value="<?php echo $option_value ;?>"<?php if($value==$option_value){ echo ' selected="selected"';}?>>
					<?php echo $option_txt; ?>
				</option>
            <?php endforeach; ?>    
        </select>
		<?php if(isset($option['description'])) : ?>
            <p class="description"><?php echo $option['description'].' '.$this->html_default_value_txt($name) ; ?></p>
        <?php	endif;	
		
    }
    function html_generic_hidden_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <input type="hidden" name="automatichxmenu_options[<?php echo $name; ?>]" value="<?php echo $value; ?>"/>
        <?php
    }
    function html_content_type_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <input type="radio" name="automatichxmenu_options[<?php echo $name; ?>]" value="all" checked="checked"/> <?php _e( "All", 'automatichxmenu'); ?>
        <input type="radio" name="automatichxmenu_options[<?php echo $name; ?>]" value="pages" <?php if($value=='pages'){ echo ' checked="checked"';}?> /> <?php _e( "Pages", 'automatichxmenu'); ?>
        <input type="radio" name="automatichxmenu_options[<?php echo $name; ?>]" value="posts" <?php if($value=='posts'){ echo ' checked="checked"';}?> /> <?php _e( "Posts", 'automatichxmenu'); ?>
        <?php if(isset($option['description'])) : ?>
            <p class="description"><?php echo $option['description'].' '.$this->html_default_value_txt($name) ; ?></p>
        <?php	endif;	
    }

    function html_css_position_callback($option) {
        $name = $option['name'];
        $value = "{$this->opt->get($name)}";
        ?>
        <input type="radio" name="automatichxmenu_options[<?php echo $name; ?>]" value="center" checked="checked"/> <span class="dashicons dashicons-align-center"></span> <?php _e( "Center", 'automatichxmenu'); ?>
        <input type="radio" name="automatichxmenu_options[<?php echo $name; ?>]" value="left" <?php if($value=='left'){ echo ' checked="checked"';}?> /> <span class="dashicons dashicons-align-left"></span> <?php _e( "Left", 'automatichxmenu'); ?>
        <input type="radio" name="automatichxmenu_options[<?php echo $name; ?>]" value="right" <?php if($value=='right'){ echo ' checked="checked"';}?> /> <span class="dashicons dashicons-align-right"></span> <?php _e( "Right", 'automatichxmenu'); ?>
        <?php if(isset($option['description'])) : ?>
            <p class="description"><?php echo $option['description'].' '.$this->html_default_value_txt($name) ; ?></p>
        <?php	endif;	
    }
    
    function html_default_value_txt($option){
        if($this->opt->getDefault($option)) {
            return "(".__( "default value", 'automatichxmenu').' : '.$this->opt->getDefault($option).")" ;
        }
        return false ;
    }

    /***************************************************************************
     * OPTIONS
     **************************************************************************/
    
    function plugin_activate() {
        if(get_option('automatichxmenu_options')) return;
        add_option('automatichxmenu_options', $this->opt->getDefault());
    }
    
    // Tous les paramètres et la configuration des champs utilisé dans wordpress
    function register_settings_and_fields() {

        register_setting('automatichxmenu_options',
                         'automatichxmenu_options');
        
        // SETTINGS SECTION : Main #############################################
        add_settings_section('ahxm_settings_section_main', 
                             __( "Main settings", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('mode', 
                            __( "Mode", 'automatichxmenu'), 
                            array( $this,'html_generic_select_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_main',
                            array('name'=>'mode',
                                  'description' => __( "If you choose \"Automatic & Shortcode\", shortcode will take precedence over the automatic menu", 'automatichxmenu'),
                                  'options'=>array( __( "Disabled", 'automatichxmenu') => 'disabled',
                                                    __( "Automatic", 'automatichxmenu') => 'automatic',
                                                    __( "Shortcode only", 'automatichxmenu') => 'shortcode',
                                                    __( "Automatic & Shortcode", 'automatichxmenu') => 'all')));
        add_settings_field('hx_begin_level', 
                   __( "Titles beginning", 'automatichxmenu'), 
                   array( $this,'html_generic_select_callback'), 
                   __FILE__, 
                   'ahxm_settings_section_main',
                   array('name'=>'hx_begin_level',
                         'description' => __( "First title level to be displayed in the menu", 'automatichxmenu'),
                         'options'=>array( __( "&lt;h1&gt;&lt;/h1&gt;", 'automatichxmenu') => '1',
                                           __( "&lt;h2&gt;&lt;/h2&gt;", 'automatichxmenu') => '2',
                                           __( "&lt;h3&gt;&lt;/h3&gt;", 'automatichxmenu') => '3',
                                           __( "&lt;h4&gt;&lt;/h4&gt;", 'automatichxmenu') => '4',
                                           __( "&lt;h5&gt;&lt;/h5&gt;", 'automatichxmenu') => '5',
                                           __( "&lt;h6&gt;&lt;/h6&gt;", 'automatichxmenu') => '6')));
        add_settings_field('hx_max_level', 
                   __( "Titles level", 'automatichxmenu'), 
                   array( $this,'html_generic_select_callback'), 
                   __FILE__, 
                   'ahxm_settings_section_main',
                   array('name'=>'hx_max_level',
                         'description' => __( "Maximum level of &lt;hx&gt; tags to be displayed in the menu", 'automatichxmenu'),
                         'options'=>array( __( "&lt;h1&gt;&lt;/h1&gt;", 'automatichxmenu') => '1',
                                           __( "&lt;h2&gt;&lt;/h2&gt;", 'automatichxmenu') => '2',
                                           __( "&lt;h3&gt;&lt;/h3&gt;", 'automatichxmenu') => '3',
                                           __( "&lt;h4&gt;&lt;/h4&gt;", 'automatichxmenu') => '4',
                                           __( "&lt;h5&gt;&lt;/h5&gt;", 'automatichxmenu') => '5',
                                           __( "&lt;h6&gt;&lt;/h6&gt;", 'automatichxmenu') => '6')));
        add_settings_field('hx_min_number', 
                           __( "Minimum number of titles", 'automatichxmenu'), 
                           array( $this,'html_generic_integer_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_main',
                           array('name'=>'hx_min_number',
                                 'description' => __( "Minimum number of titles the article should contain before displaying the menu", 'automatichxmenu')
                               ));
        add_settings_field('content_type', 
                           __( "Where to show", 'automatichxmenu'), 
                           array( $this,'html_content_type_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_main',
                           array('name'=>'content_type',
                                 'description' => __('Choose where to show the menu: on post, on page or both', 'automatichxmenu') ));
        add_settings_field('home', 
                           __( "Show on homepage", 'automatichxmenu'), 
                           array( $this,'html_generic_yesno_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_main',
                           array('name'=>'home',
                                 'description' => __('Show the menu on the homepage', 'automatichxmenu') ));

        // SETTINGS SECTION : Smooth scroll ################################
        add_settings_section('ahxm_settings_section_smooth_scroll', 
                             __( "Smooth scroll", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('smooth_scroll_active', 
                            __( "Smooth scroll effect", 'automatichxmenu'), 
                            array( $this,'html_generic_yesno_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_smooth_scroll',
                            array('name'=>'smooth_scroll_active',
                                'description' => __('Activate an animated scrolling effect', 'automatichxmenu') ));
        add_settings_field('smooth_scroll_speed', 
                            __( "Speed", 'automatichxmenu'), 
                            array( $this,'html_generic_integer_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_smooth_scroll',
                            array('name'=>'smooth_scroll_speed',
                                'description'=> "Speed of the scroll effect. Set a value in milliseconds."));
        add_settings_field('smooth_scroll_offset', 
                            __( "Offset", 'automatichxmenu'), 
                            array( $this,'html_generic_integer_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_smooth_scroll',
                            array('name'=>'smooth_scroll_offset',
                                'description'=> "Vertical offset for the scroll. Set a value in pixels."));

        // SETTINGS SECTION : To the top ################################
        add_settings_section('ahxm_settings_section_to_top', 
                             __( "To the top", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        
        add_settings_field('to_top_active', 
                             __('Add "to the top" links', 'automatichxmenu'), 
                             array( $this,'html_generic_yesno_callback'), 
                             __FILE__, 
                             'ahxm_settings_section_to_top',
                             array('name'=>'to_top_active',
                                 'description' => __('Add links near titles to go back to the top of the page', 'automatichxmenu') ));
        add_settings_field('to_top_pages', 
                            __( "Which pages", 'automatichxmenu'), 
                            array( $this,'html_generic_select_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_to_top',
                            array('name'=>'to_top_pages',
                                'description' => __( "Choose where to add the links", 'automatichxmenu'),
                                'options'=>array( __( "Every pages", 'automatichxmenu') => 'everywhere',
                                                  __( "Only pages with nav menu", 'automatichxmenu') => 'nav') ));
        add_settings_field('to_top_text', 
                            __( "Text of links", 'automatichxmenu'), 
                            array( $this,'html_generic_text_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_to_top',
                            array('name'=>'to_top_text',
                                'description'=> "Text for the links "));
        add_settings_field('to_top_where', 
                            __( "Where to scroll", 'automatichxmenu'), 
                            array( $this,'html_generic_select_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_to_top',
                            array('name'=>'to_top_where',
                                'description' => __( "Choose where to scroll", 'automatichxmenu'),
                                'options'=>array( __( "To the top of the window", 'automatichxmenu') => 'window',
                                                  __( "To the navigation menu", 'automatichxmenu') => 'menu',
                                                  __( "To a custom ID", 'automatichxmenu') => 'custom') ));
        add_settings_field('to_top_custom_id', 
                            __( "Custom ID", 'automatichxmenu'), 
                            array( $this,'html_generic_text_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_to_top',
                            array('name'=>'to_top_custom_id',
                                'description'=> "ID of a custom element to scrool to"));	
            
        // SETTINGS SECTION : Display ##########################################
        add_settings_section('ahxm_settings_section_display', 
                             __( "Display", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('css_position', 
                           __( "Alignment", 'automatichxmenu'), 
                           array( $this,'html_css_position_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display',
                           array('name'=>'css_position',
                                 'description'=> __( "You need a width < 100% to see the alignement in action", 'automatichxmenu')));
        add_settings_field('css_width', 
                           __( "Width", 'automatichxmenu'), 
                           array( $this,'html_generic_text_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display',
                           array('name'=>'css_width',
                                 'description'=> " "));	
        add_settings_field('css_width_small', 
                           __( "Screen width / full width", 'automatichxmenu'), 
                           array( $this,'html_generic_text_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display',
                           array('name'=>'css_width_small',
                                 'description'=> __( "Max width of the windows before menu width swap to 100%.", 'automatichxmenu')));
        add_settings_field('css_margin', 
                           __( "Margin", 'automatichxmenu'), 
                           array( $this,'html_generic_text_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display',
                           array('name'=>'css_margin',
                                 'description'=> " "));	
        add_settings_field('css_padding', 
                           __( "Padding", 'automatichxmenu'), 
                           array( $this,'html_generic_text_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display',
                           array('name'=>'css_padding',
                                 'description'=> " "));			
        add_settings_field('css_background_color', 
                           __( "Background", 'automatichxmenu'), 
                           array( $this,'html_generic_color_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display',
                           array('name'=>'css_background_color'));
        add_settings_field('css_margin_ul', 
                           __( "Shift between levels", 'automatichxmenu'), 
                           array( $this,'html_generic_text_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display',
                           array('name'=>'css_margin_ul', 
                                 'description'=> " "));
        add_settings_field('css_margin_vertical', 
                           __( "Margin between lines", 'automatichxmenu'), 
                           array( $this,'html_generic_text_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display',
                           array('name'=>'css_margin_vertical', 
                                 'description'=> " "));
		
        // SETTINGS SECTION : Display - Menu title #############################
        add_settings_section('ahxm_settings_section_display_title', 
                             __( "Menu title", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('menu_title_on', 
                           __( "Display the title", 'automatichxmenu'), 
                           array( $this,'html_generic_yesno_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_title',
                           array('name'=>'menu_title_on',
                                 'description' => false ));
        add_settings_field('menu_title_align', 
                           __( "Alignment", 'automatichxmenu'), 
                           array( $this,'html_css_position_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_title',
                           array('name'=>'menu_title_align')); 
        add_settings_field('menu_title_style', 
                           __( "Style", 'automatichxmenu'), 
                           array( $this,'html_generic_select_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_title',
                           array('name'=>'menu_title_style',
                                 'options'=>array( __( "Disabled", 'automatichxmenu') => '',
                                                   __( "Normal", 'automatichxmenu') => 'font-weight:normal;font-style: normal;',
                                                   __( "Bold", 'automatichxmenu') => 'font-weight:bold;font-style: normal;',
                                                   __( "Italic", 'automatichxmenu') => 'font-weight:normal;font-style:italic;',
                                                   __( "Bold & Italic", 'automatichxmenu') => 'font-weight:bold;font-style:italic;')
						   ));
        add_settings_field('menu_title_color', 
                           __( "Color", 'automatichxmenu'), 
                           array( $this,'html_generic_color_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_title',
                           array('name'=>'menu_title_color'));
		
        // SETTINGS SECTION : Display - Borders ################################
        add_settings_section('ahxm_settings_section_display_borders', 
                             __( "Menu borders", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('css_border_color', 
                           __( "Border color", 'automatichxmenu'), 
                           array( $this,'html_generic_color_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_borders',
                           array('name'=>'css_border_color'));
        add_settings_field('css_border_style', 
                           __( "Border style", 'automatichxmenu'), 
                           array( $this,'html_generic_select_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_borders',
                           array('name'=>'css_border_style',
                                 'options'=>array( __( "Disabled", 'automatichxmenu') => 'none',
                                                   __( "Solid", 'automatichxmenu') => 'solid',
                                                   __( "Dashed", 'automatichxmenu') => 'dashed',
                                                   __( "Dotted", 'automatichxmenu') => 'dotted',
                                                   __( "Double", 'automatichxmenu') => 'double')
                            ));
        add_settings_field('css_border_width', 
                           __( "Border width", 'automatichxmenu'), 
                           array( $this,'html_generic_text_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_borders',
                           array('name'=>'css_border_width',
                                 'description'=> " "));
        add_settings_field('css_border_radius', 
                           __( "Border radius", 'automatichxmenu'), 
                           array( $this,'html_generic_text_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_borders',
                           array('name'=>'css_border_radius',
                                 'description'=> " "));

        // SETTINGS SECTION : Display - Collapse #################################
        add_settings_section('ahxm_settings_section_collapse', 
                             __( "Collapse CSS", 'automatichxmenu'), 
                             '', 
                             __FILE__);

        add_settings_field('collapse_on', 
                            __( "Collapse menu", 'automatichxmenu'), 
                            array( $this,'html_generic_yesno_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_collapse',
                            array('name'=>'collapse_on',
                                'description' => __('Collapse menu items', 'automatichxmenu') )); 

        add_settings_section('ahxm_settings_section_display_collapse', 
                             __( "Collapse CSS", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('collapse_button_align', 
                           __( "Button align", 'automatichxmenu'), 
                           array( $this,'html_generic_select_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_collapse',
                           array('name'=>'collapse_button_align',
                                 'options'=>array( __( "Align to the title", 'automatichxmenu') => 'flex-start',
                                                   __( "Opposite of the title", 'automatichxmenu') => 'space-between')
                            ));
        add_settings_field('collapse_button_bgcolor', 
                            __( "Button background color", 'automatichxmenu'), 
                            array( $this,'html_generic_color_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_display_collapse',
                            array('name'=>'collapse_button_bgcolor',
                                  'description'=> " "));

        add_settings_field('collapse_button_txtcolor', 
                            __( "Button text color", 'automatichxmenu'), 
                            array( $this,'html_generic_color_callback'), 
                            __FILE__, 
                            'ahxm_settings_section_display_collapse',
                            array('name'=>'collapse_button_txtcolor',
                                'description'=> " "));                

        // SETTINGS SECTION : Display - Custom #################################
        add_settings_section('ahxm_settings_section_display_custom', 
                             __( "Custom CSS", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('css_custom', 
                           __( "Custom CSS", 'automatichxmenu'), 
                           array( $this,'html_generic_longtext_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_display_custom',
                           array('name'=>'css_custom'));
        
        // SETTINGS SECTION : Numbering ########################################
        add_settings_section('ahxm_settings_section_number', 
                             __( "Numbering", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('numbering_active', 
                           __( "Active numbering", 'automatichxmenu'), 
                           array( $this,'html_generic_yesno_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_number',
                           array('name'=>'numbering_active',
                                 'description' => __('Enable automatic numbering of titles', 'automatichxmenu') ));
        add_settings_section('ahxm_settings_section_number_nav', 
                             __( "Numbering", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('numbering_nav_style', 
                           __( "Style", 'automatichxmenu'), 
                           array( $this,'html_generic_select_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_number_nav',
                           array('name'=>'numbering_nav_style',
                                 'options'=>array( __( "Disabled", 'automatichxmenu') => '',
                                                   __( "Normal", 'automatichxmenu') => 'font-weight:normal;font-style: normal;',
                                                   __( "Bold", 'automatichxmenu') => 'font-weight:bold;font-style: normal;',
                                                   __( "Italic", 'automatichxmenu') => 'font-weight:normal;font-style:italic;',
                                                   __( "Bold & Italic", 'automatichxmenu') => 'font-weight:bold;font-style:italic;')
						   ));
        add_settings_field('numbering_nav_color', 
                           __( "Color", 'automatichxmenu'), 
                           array( $this,'html_generic_color_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_number_nav',
                           array('name'=>'numbering_nav_color'));
        
        // SETTINGS SECTION : Numbering - Title ################################
        add_settings_section('ahxm_settings_section_number_title', 
                             __( "Numbering", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('numbering_title_style', 
                           __( "Style", 'automatichxmenu'), 
                           array( $this,'html_generic_select_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_number_title',
                           array('name'=>'numbering_title_style',
                                 'options'=>array( __( "Disabled", 'automatichxmenu') => '',
                                                   __( "Normal", 'automatichxmenu') => 'font-weight:normal;font-style: normal;',
                                                   __( "Bold", 'automatichxmenu') => 'font-weight:bold;',
                                                   __( "Italic", 'automatichxmenu') => 'font-style:italic;',
                                                   __( "Bold & Italic", 'automatichxmenu') => 'font-weight:bold;font-style:italic;')
                            ));
        add_settings_field('numbering_title_color', 
                           __( "Color", 'automatichxmenu'), 
                           array( $this,'html_generic_color_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_number_title',
                           array('name'=>'numbering_title_color'));
        
        // SETTINGS SECTION : Hidden ###########################################
        add_settings_section('ahxm_settings_section_hidden', 
                             __( "Hidden", 'automatichxmenu'), 
                             '', 
                             __FILE__);
        add_settings_field('version', 
                           __( "Version", 'automatichxmenu'), 
                           array( $this,'html_generic_hidden_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_hidden',
                           array('name'=>'version'));
        add_settings_field('open_tab',
                           'open_tab', 
                           array( $this,'html_generic_hidden_callback'), 
                           __FILE__, 
                           'ahxm_settings_section_hidden',
                           array('name'=>'open_tab'));
    }
    
    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ){
        $new_input = array();
        if( isset( $input['id_number'] ) )
            $new_input['id_number'] = absint( $input['id_number'] );

        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );

        return $new_input;
    }
    
    
    /***************************************************************************
     * INITIALIZE
     **************************************************************************/
    
    function menu_page_init() {
        add_menu_page( 'Automatic Hx Menu', 
                'Auto. Hx Menu', 
                'administrator', 
                'automatichxmenu-options', 
                array( $this,'options_page_html'), 
                plugin_dir_url( __FILE__ ).'/img/logo_small.png' );
        /* add_submenu_page( 'options-general.php', 
                          'Automatic Hx Menu - Options', 
                          'Automatic Hx Menu',
                          'manage_options', 
                          'automatichxmenu-options',
                          array( $this,'options_page_html')
        );
         */
    }
    

   
    
    public function load_admin_style() {
        wp_enqueue_style( 'automatichxmenu', plugin_dir_url( __FILE__ ).'css/automatichxmenu_admin.css');
    }
        
    function load_admin_js(){
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker');
        wp_enqueue_script( 'wp-color-picker-script-handle', plugins_url('wp-color-picker-script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );

    wp_register_script( 
            'automatichxmenu-js', 
            plugin_dir_url( __FILE__ ).'js/automatichxmenu_admin.js', 
            array( 'jquery', 'wp-color-picker' )
        );
        wp_enqueue_script( 'automatichxmenu-js' );
    }
}

