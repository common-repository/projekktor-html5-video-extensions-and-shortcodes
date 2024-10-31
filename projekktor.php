<?php
/*
Plugin Name: Projekktor Video Tag Extension
Plugin URI: http://www.projekktor.com/cms.php#wordpress
Description: Adds shortcodes to embed HTML5 based media with automatic flash-fallback based on the skinnable, free (GPL) Projekktor Media Player. Additionally generates a blogwide playlist with permalinks to the original posts for an optional webTV experience. | <a href="http://www.projekktor.com/docs/wp/" title="Documentation">Online Documentation</a>.
Author: Sascha Kluger
Version: 0.9.8
Author URI: http://www.spinningairwhale.com

Copyright 2010, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
under GNU General Public License
http://www.projekktor.com/license/
*/

if(defined('ABSPATH') && defined('WPINC')) {
	add_action("init",array("ProjekktorWP","Enable"),1000,0);  
    // Register for activation       
    register_activation_hook( ProjekktorWP::get_plugin_file(),array(ProjekktorWP::get_plugin_instance(),"install"));       
    // register_update_hook( ProjekktorWP::get_plugin_file(),array(ProjekktorWP::get_plugin_instance(),"install"));
   // register_deactivation_hook( ProjekktorWP::get_plugin_file(),array(ProjekktorWP::get_plugin_instance(),"uninstall"));     
}

class ProjekktorWP {
    
    var $dbVersion = "2";
    var $jQueryVersion = "1.4.2";    
    var $widgetBase = 'tv';
    var $scheduleBase = 'schedule';
    
    
    function enable(){
        
    
    	$plpath = WP_PLUGIN_URL . '/'.basename((dirname(__FILE__)));
    
	// load JS mess (but not while in admin menu)
	if (@WP_ADMIN!==true)
	{
		ProjekktorWP::enable_frontend();	
	} else
	{
		error_reporting(0);
		ProjekktorWP::enable_backend();
	}        
    }
    
    /* stuff this plugin will (more or less) dynamically configure  */
    function enable_frontend() {


        add_action( 'init', array(ProjekktorWP::get_plugin_instance(), 'flush_rewrite_rules') );
        add_action( 'generate_rewrite_rules', array(ProjekktorWP::get_plugin_instance(), 'set_rewrites') );
        add_filter( 'query_vars', array(ProjekktorWP::get_plugin_instance(), 'add_custom_page_variables') );
        add_action( 'template_redirect', array(ProjekktorWP::get_plugin_instance(), 'redirect_intercept'));

        // enable (or skip) JS inclusion


	// jquery
	wp_deregister_script( 'jquery' );
	wp_register_script( 'jquery', ProjekktorWP::get_plugin_url() . 'js/jquery.js', false, '1.4.2');
	wp_enqueue_script('jquery');

	// projekktor.js
	wp_enqueue_script('projekktor', ProjekktorWP::get_plugin_url(). 'js/projekktor.min.js' , array('jquery'), ProjekktorWP::get_player_version());

	// theme CSS
	add_action('wp_head', array(ProjekktorWP::get_plugin_instance(), 'register_frontend_header'));            
    
	// RSS manipulation 
	add_filter('the_content_feed', array(ProjekktorWP::get_plugin_instance(), 'remove_js_from_rss'));
        

        if (ProjekktorWP::get_option_value('enableMediaRSS')===true) {        
            add_filter('rss_enclosure', array("ProjekktorWP","delete_enclosure"));
            add_action('rss2_ns', array(ProjekktorWP::get_plugin_instance(), 'set_rss_namespace'));
            add_action('rss2_item', array(ProjekktorWP::get_plugin_instance(), 'set_rss_enclosures'));
        }   
             
         
        add_shortcode('video', array("ProjekktorWP","parse_shortcodes"));
        add_shortcode('audio', array("ProjekktorWP","parse_shortcodes"));
        // add_shortcode('media', array("ProjekktorWP","parse_shortcodes"));        
    }
        
    
    /* stuff this plugin will (more or less) dynamically configure  */
    function enable_backend() {        
	add_action('admin_menu', array("ProjekktorWP","register_page_backend"));  
	add_action('save_post', array("ProjekktorWP","item_save"));

        // later
        // add_filter('media_send_to_editor', array("ProjekktorWP","register_media_send"));
        
        // avoi
        if (@$_GET['page']==ProjekktorWP::get_base_name()) {
            wp_enqueue_style('thickbox');         
            wp_enqueue_script('jquery'); 
            wp_enqueue_script('thickbox');        
            wp_enqueue_script('projekktor', ProjekktorWP::get_plugin_url(). 'js/medialib2projekktor.js' , array('jquery'), '0.1');          
        }          
    }
    
    function delete_enclosure() {
        return;
    }
    
    /* stuff this plugin will (more or less) dynamically configure  */
    function get_config_options($optionName=null) {
    	
    	$plpath = WP_PLUGIN_URL . '/'.basename((dirname(__FILE__)));
    	$config = array(
        
            // CUSTOM    		
            
    		'enableMediaRSS' => array(
			'heading' => 'General',  
    			'txt' => __('%s Try to create a clean mediaRSS with media groups and everything.'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#general',
    			'val' => 'false',
    		),	
            
                                                  
    		'enableFlashFallback' => array(
    			'txt' => __('%s Automatically fall back to Flash where required (and possible).'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#general',
    			'val' => 'true',
    		),	
            
            /*
     		'wildcard' => array(
    			'txt' => __('When using wildcard-postfix "{*}" in file names, add these extensions (comma separated):'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#defaults',
    			'val' => 'ogv,webm,mp4',
    			'prk' => false
    			
    		),
            */               
            /*
    		'debug' => array(
    			'txt' => __('%s Enable debug-mode (console.log)'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#general',
    			'val' => 'false',
                'prk' => false  
    		),	            

           */
                       
    		'embedBaseIsTv' => array(
    			'txt' => __('%s Enable fullscreen webTV?'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#general',
    			'val' => 'true',
                'prk' => false,
                'short' => true    
    		),	            
                         
    		'controls' => array(
                'heading' => 'Defaults',
                'help' => 'http://www.projekktor.com/docs/wpsetup#defaults',
    			'txt' => __('%s Enable controlbar'),
    			'val' => 'true'
    		),
                        				                                    		                        
    		'volume' => array(
    			'txt' => __('Initial sound volume: %s %%'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#defaults',
    			'val' => 0.5,
    			'editable' => true
    		),
    		'width' => array(
    			'txt' => __('Default player width: %s px'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#defaults',
    			'val' => 645,
    			'prk' => false
    		),	
    		'height' => array(
			'txt' => __('Default player height: %s px'),
			'help' => 'http://www.projekktor.com/docs/wpsetup#defaults',
			'val' => 359,
			'prk' => false
    		),                
                         
    		'applylogo' => array(
			'heading' => 'Branding',
			'help' => 'http://www.projekktor.com/docs/wpsetup#branding',
			'txt' => __('%s Apply logo to video display.'),
			'val' => 'false',
			'prk' => false
    		),                                    
            
    		'logo' => array(
			'help' => 'http://www.projekktor.com/docs/wpsetup#branding',
			'txt' => __('Full URL to logo-image - preferably a PNG'),
			'val' => ProjekktorWP::get_plugin_url().'default-logo.png',
			'func' => 'get_library_field'
    		),

    		'logoPosition' => array(
			'txt' => __('Logo position: %s'),
			'help' => 'http://www.projekktor.com/docs/wpsetup#branding',
			'val' => 'tl',
			'opts' => array(
				'tl'    => 'top left',
				'tr'    => 'top right',
				'bl'    => 'bottom left',
				'br'    => 'bottom right' 
			)
    		),
               
    		'poster' => array(
			'txt' => __('Full URL to a default poster image'),
			'help' => 'http://www.projekktor.com/docs/wpsetup#branding',
			'val' => ProjekktorWP::get_plugin_url().'default-poster.jpg',
			'func' => 'get_library_field'
    		),

            
    		'theme' => array(
			'help' => 'http://www.projekktor.com/docs/wpsetup#theme',
			'txt' => __('Theme'),
			'val' => 'maccaco',
			'prk' => false,
			'func' => 'get_theme_selector'
    		),
            
    		'enableSocialTwitter' => array(
                'heading' => 'Social Features',
                'help' => 'http://www.projekktor.com/docs/wpsetup#social_features',
    			'txt' => __('%s Enable "tweet this" overlay.'),
    			'val' => 'true',
    		),
    		'enableSocialFacebook' => array(
    			'txt' => __('%s Enable "post to Facebook" overlay.'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#social_features',                
    			'val' => 'true',
    		),		                        
    		'enableSocialEmbed' => array(
    			'txt' => __('%s Enable "embed this" overlay and make player embeddable.'),
                'help' => 'http://www.projekktor.com/docs/wpsetup#social_features',
    			'val' => 'true',
    		),	 
            
     		'socialMessageA' => array(
                'heading' => 'Social Texts',
    			'txt' => __('Copy this'),
    			'val' => 'Copy this',
    		),            
                      
     		'socialMessageB' => array(
    			'txt' => __('Close Window'),
    			'val' => 'Close Window',
    		),            
                     
     		'socialMessageC' => array(
    			'txt' => __('This is the embed code for the current video which supports iPad, iPhone, Flash and native players.'),
    			'val' => 'This is the embed code for the current video which supports iPad, iPhone, Flash and native players.',
    		),            
                     
     		'socialMessageD' => array(
    			'txt' => __('I found a cool HTML5 video powered site. Check this out.'),
    			'val' => 'I found a cool HTML5 video powered site. Check this out.',
    		),      

     		'messageA' => array(
                'heading' => 'Error messages',
    			'txt' => __('An unknown error occurred.'),
    			'val' => 'An unknown error occurred',
    		),

     		'messageB' => array(
    			'txt' => __('You aborted the media playback.'),
    			'val' => 'You aborted the media playback. ',
    		),
            
     		'messageC' => array(
    			'txt' => __('A network error caused the media download to fail part-way.'),
    			'val' => 'A network error caused the media download to fail part-way. ',
    		),
            
     		'messageD' => array(
    			'txt' => __('The media playback was aborted due to a corruption problem.'),
    			'val' => 'The media playback was aborted due to a corruption problem.',
    		),
            
     		'messageE' => array(
    			'txt' => __('The media could not be loaded, either because the server or network failed or because the format is not supported.'),
    			'val' => 'The media could not be loaded, either because the server or network failed or because the format is not supported.',
    		),
            
     		'messageF' => array(
    			'txt' => __('Sorry, your browser does not support the media format of the requested clip.'),
    			'val' => 'Sorry, your browser does not support the media format of the requested clip. ',
    		),
            
     		'messageG' => array(
    			'txt' => __('You need to update your Flash Plugin to a newer version!'),
    			'val' => 'You need to update your Flash Plugin to a newer version!',
    		),
            
     		'messageH' => array(
    			'txt' => __('No media scheduled.'),
    			'val' => 'No media scheduled.',
    		),                                                                                    
   
     		'messageI' => array(
    			'txt' => __('Click display to proceed.'),
    			'val' => 'Click display to proceed.',
    		),                                                                                    
               
    		'themeId' => array(
    			'txt' => __('Player Theme to use:'),
    			'val' => 'tll',
    			'prk' => false,
    			'editable' => false
    		),			
                        
            
    		
    	);
	
        if ($optionName!=null) {
            return $config[$optionName];
        }
    	return $config;
    }    
    
    function get_option_value($optionName) {      
    
        // read user value
        $customValue = get_option('projekktor_'.$optionName);                              
        if ($customValue!==false) {     
            if ($customValue==='true') $customValue = true;
            if ($customValue==='false') $customValue = false;
            if ($customValue!=='') { 
                return $customValue;
            }        
        } 

        // if empty, apply default value:
        $optionSet = ProjekktorWP::get_config_options($optionName);        
        if ($optionSet['val']==='true') $optionSet['val'] = true;
        if ($optionSet['val']==='false') $optionSet['val'] = false;               
        return $optionSet['val'];
        
    }
    
    function get_is_default_value($optionName, $value) {
        $optionSet = ProjekktorWP::get_config_options($optionName);
        return ($value==$optionSet['value']);        
    }
    
    function set_rss_namespace() {
	   echo 'xmlns:media="http://search.yahoo.com/mrss/"';	
    }
    
    function set_rss_enclosures() {
        global $post;
    	$enclosures = array();
    	$content = '';
  
    	if ( ProjekktorWP::get_option_value('rss_use_excerpt') || !strlen( get_the_content() ) ) {
    		$content = the_excerpt_rss();
    	} else {    
    		$content = get_the_content();
    	}

        $data = ProjekktorWP::get_playlist_content(array('id'=>$post->ID));

        if (count($data)==0) return;

        foreach($data as $key => $fileSet) {
   
            $wrap = "\t<media:group>\n%s\t</media:group>\n";
            $tag = '';
            $count = 0;
 
            foreach($fileSet as $fileKey => $fileValues) {

                if ($fileKey!=='config') {
                     
                    $count++;                  
                    $tag .= "\t\t".'<media:content'; 
                    if ($fileKey==0) $tag .= ' isDefault="true"';
                    if ($fileValues['size']>0) $tag .= ' fileSize="'.$fileValues['size'].'"';
                    if ($fileValues['type']!='') $tag .= ' type="'.$fileValues['type'].'"'; 
                    if ($fileValues['src']!='') $tag .= ' url="'.$fileValues['src'].'"'; 
                    if ($valueSet['file']['width']!=0) $tag .= ' width="'.$valueSet['file']['width'].'"'; 
                    if ($valueSet['file']['height']!=0) $tag .= ' width="'.$valueSet['file']['height'].'"';    
                    $tag .= '/>'."\n";
                }             
                        
            }

                 
            if ($count<=1) $wrap = ' %s';
       
            echo sprintf($wrap, $tag);
            
            
        }
  
    }    
    
    function get_playlist_content($options=array()) {
        global $wpdb;
        
        // get what?
        $limit = ($options['offset']+0>0) ? ' LIMIT '.($options['offset']+0).',' : '';
        $limit .= ($options['limit']>0 && $limit!='') ? $options['limit']+0 : '';
        $ID = ($options['id']+0>0) ? 'AND postId='.($options['id']+0) : '';
                
    	$table_name = $wpdb->prefix . "projekktor_playlist";
    	$sql = "SELECT created, postId, title,sources,config FROM " . $table_name . " WHERE status='publish' ".$ID." ORDER BY created DESC ".$limit.";";

        $results = $wpdb->get_results( $sql );
    	
    	$playlist = array();
    	$count = 0;
    	foreach($results as $resultKey => $rowObject) {
    		$playlist[$count]= unserialize($rowObject->sources);
            $playlist[$count]['config'] = array(
                        'postid'       =>	$rowObject->postId,
                        'ID'	       =>	$rowObject->postId,
            			'title'		   => 	$rowObject->title,
            			'created'	   => 	strtotime($rowObject->created),
            			'plugin_share' => array(
                            'link' => get_permalink( $rowObject->postId )
                        )
            );
            $itemConfig = unserialize($rowObject->config);
            if (is_array($itemConfig)) {
                $playlist[$count]['config'] = array_merge($playlist[$count]['config'], $itemConfig);
            }
    		$count++;
    	} 
        
        return $playlist;   
    }
    
    function get_playlist_length() {
        global $wpdb;
        
        // get what?
        $limit = ($options['offset']+0>0) ? ' LIMIT '.($options['offset']+0).',' : '';
        $limit .= ($options['limit']>0 && $limit!='') ? $options['limit']+0 : '';
        $ID = ($options['id']+0>0) ? 'AND postId='.($options['id']+0) : '';
                
    	$table_name = $wpdb->prefix . "projekktor_playlist";
    	$sql = "SELECT COUNT(created) as items FROM " . $table_name . " WHERE status='publish';";

        $results = $wpdb->get_results( $sql );

    	$playlist = array();
    	$count = 0;
    	foreach($results as $resultKey => $rowObject) {
    		return $rowObject->items;
    	} 
        
        return $playlist;   
    }        
    
    function get_player_version() {
        preg_match('/version:[\"|\'](\d{1,}\.\d{1,}.\d{1,})[\"|\']/i', file_get_contents(dirname(__FILE__).'/js/projekktor.min.js'), $matches);
        return $matches[1];        
    }
  
    function get_player_version_remote() {
        $txt = ProjekktorWP::fsock_download(ProjekktorWP::get_plugin_instance()->projekktorDownloadsUrl, 'index.php');
        return $txt;
        preg_match('/version:[\"|\'](\d{1,}\.\d{1,}.\d{1,})[\"|\']/i', file_get_contents(dirname(__FILE__).'/js/projekktor.min.js'), $matches);
        return $matches[1];        
    }  
    
    function get_player_config() {
        // build JS code
        $playerConfig = array(
            // "controlsTemplate" => '<div {fsexit}></div><div {fsenter}></div><div {play}></div><div {pause}></div><div {prev}></div><div {next}></div><div {timeleft}><span {timedur}>{hr_dur}:{min_dur}:{sec_dur}</span><span {timeremaining}>- {hr_rem}:{min_rem}:{sec_rem}</span></div><div {scrubber}><div {loaded}></div><div {playhead}></div></div><div {vslider}><div {vmarker}></div><div {vknob}></div></div><div {mute}></div><div {vmax}></div>',
    	    "messages"         => array(
        		0 => ProjekktorWP::get_option_value('messageA'),
        		1 => ProjekktorWP::get_option_value('messageB'),
        		2 => ProjekktorWP::get_option_value('messageC'),
        		3 => ProjekktorWP::get_option_value('messageD'),
        		4 => ProjekktorWP::get_option_value('messageE'),
        		5 => ProjekktorWP::get_option_value('messageF'),
        		6 => ProjekktorWP::get_option_value('messageG'),
        		7 => ProjekktorWP::get_option_value('messageH'),
        		8 => '! Invalid media model configured !',
			97 => ProjekktorWP::get_option_value('messageH'),
        		98 => 'Invalid or malformed playlist data!',
        		99 => ProjekktorWP::get_option_value('messageI')
    	    ),
            "controls" => ProjekktorWP::get_option_value('controls'),
            "playerFlashMP4" => ProjekktorWP::get_plugin_url().'flash/jarisplayer.swf',
            "playerFlashMP3" => ProjekktorWP::get_plugin_url().'flash/jarisplayer.swf',  
            // "dynamicTypeExtensions" => array(),
            "enableFlashFallback" => ProjekktorWP::get_option_value('enableFlashFallback'),
            "volume" => (ProjekktorWP::get_option_value('volume')/100)+0,
            "plugins" => array('Display', 'Controlbar', 'Share'),
            // "debug" => (ProjekktorWP::get_option_value('debug')) ? 'console' : false,
            "poster" => ProjekktorWP::get_option_value('poster')
        );
        
        
        // apply Logo - if required:
        if (ProjekktorWP::get_option_value('applylogo')!=false) {
            $playerConfig['plugin_display']['logoImage'] =  ProjekktorWP::get_option_value('logo');
            $playerConfig['plugin_display']['logoURL'] = home_url();
            $playerConfig['plugin_display']['logoPosition'] = ProjekktorWP::get_option_value('logoPosition');
        }
        
        // apply dynamicTypes
        /* later
        $option = ProjekktorWP::get_option_value('wildcard');
        if ($option!='') {
            $option = explode(',', $option);
            for($i=0; $i<count($option); $i++) {
                $type = ProjekktorWP::get_media_types($option[$i]);
                if ($type=='') continue;
                $playerConfig['dynamicTypeExtensions'][] = array(
                    'ext' => $option[$i],
                    'type' => $type
                );
            }
        }
        */
        
        // apply theme config stuff
        $option=ProjekktorWP::get_theme_diz(ProjekktorWP::get_option_value('theme'));        
        /*
	if ($option['controlsTemplate']!='') {
             $playerConfig['plugin_controlbar']['controlsTemplate'] = $option['controlsTemplate'];
        }
        if ($option['controlsTemplateFull']!='' && $option['controlsTemplateFull']!='null') {
             $playerConfig['plugin_controlbar']['controlsTemplateFull'] = $option['controlsTemplateFull'];
        }
        if ($option['toggleMute']!='') {
             $playerConfig['plugin_controlbar']['toggleMute'] = $option['toggleMute'];
        }                
	*/


  
        // enable Share plugin if required:
	// TWITTA
        $enableShare = false;        
        $option = ProjekktorWP::get_option_value('enableSocialTwitter');
        if ($option==true) {
            $enableShare = true;
            $playerConfig['plugin_share']['links']['twitter'] = array(
                'buttonText'  	=> 'Twitter',
		'text'  	=> ProjekktorWP::get_option_value('socialMessageD'),
		'code'  	=> 'http://twitter.com/share?url=%{pageurl}&text=%{text}&via=projekktor'
		
            );
        } else {
            $playerConfig['plugin_share']['links']['twitter'] = false;
        }           

        $option = ProjekktorWP::get_option_value('enableSocialFacebook');
        if ($option==true) {
            $enableShare = true;
            $playerConfig['plugin_share']['links']['facebook'] = array(
                'text'  	=>  ProjekktorWP::get_option_value('socialMessageD'),
                'buttonText'  	=> 'Facebook',
		'code'  	=> 'http://www.facebook.com/sharer.php?u=%{pageurl}&t=%{text}'
            );
        } else {
            $playerConfig['plugin_share']['links']['facebook'] = false;
        }           

        $option = ProjekktorWP::get_option_value('enableSocialEmbed');
        if ($option==true) {
            $enableShare = true;
            $playerConfig['plugin_share']['embed'] = array(
		'callback' 	=> 'embedClick',
		'domId' 	=> 'embed',	   
		'code' 		=> '<iframe id="%{embedid}" src="%{playerurl}#%{ID}" width="640" height="385" frameborder="0"></iframe>',
		'enable' 	=> true,
		'buttonText' 	=> 'Embed',
		'headlineText' 	=> 'Copy this:',
		'closeText' 	=> 'Close Window',
		'descText' 	=> 'This is the embed code for the current video which supports iPad, iPhone, Flash and native players.'
            );
        } else {
            $playerConfig['plugin_share']['embed'] = false;
        }           
        
        
        return $playerConfig;
        
    }
        
    
    function install() {

    	global $wpdb;     
                 
    	$table_name = $wpdb->prefix . "projekktor_playlist";
        $currentDbVersion = get_option("projekktor_db_version");
        
    	if($wpdb->get_var("show tables like '$table_name'") != $table_name || $currentDbVersion!=$this->dbVersion) {
    		
    		$sql = "CREATE TABLE " . $table_name . " (
    		postId mediumint(9) NOT NULL ,
    		tagId mediumint(9) NOT NULL,
    		created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    		title tinytext NOT NULL,
    		sources text NOT NULL,
            config text NOT NULL,
    		status VARCHAR(55) NOT NULL
    		);";
    		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    		dbDelta($sql);      
    		add_option("projekktor_db_version", $this->dbVersion);
            return;
    	} 
    }
      
      
    function get_remote_file_size($url){
       $parsed = parse_url($url);
       $host = $parsed["host"];
       $fp = @fsockopen($host, 80, $errno, $errstr, 20);
       if(!$fp) return false;
       else {
           @fputs($fp, "HEAD $url HTTP/1.1\r\n");
           @fputs($fp, "HOST: $host\r\n");
           @fputs($fp, "Connection: close\r\n\r\n");
           $headers = "";
           while(!@feof($fp))$headers .= @fgets ($fp, 128);
       }
       @fclose ($fp);
       $return = false;
       $arr_headers = explode("\n", $headers);
       foreach($arr_headers as $header) {
    			// follow redirect
    			$s = 'Location: ';
    			if(substr(strtolower ($header), 0, strlen($s)) == strtolower($s)) {
    				$url = trim(substr($header, strlen($s)));
    				return ProjekktorWP::get_remote_file_size($url);
    			}
    			
    			// parse for content length
           $s = "Content-Length: ";
           if(substr(strtolower ($header), 0, strlen($s)) == strtolower($s)) {
               $return = trim(substr($header, strlen($s)));
               break;
           }
           
       }
       return $return;

    }    
    
    function fsock_download($remoteSite, $remoteFile, $localFileLoc=false, $headersOnly=false) {
    	
    	$fp = fsockopen($remoteSite, 80, $errno, $errstr, 30);
    	
    	if(!$fp)	
        {	
            return 'Unable to open connection with '.$remoteSite;		
    	}
    	else {
    		$out = "GET /$remoteFile HTTP/1.0\r\n";
    		$out .= "Host: $remoteSite\r\n";
    		$out .= "Connection: Close\r\n\r\n";
    		fwrite($fp, $out);
    		$data = '';
    
    		while (!feof($fp)) {
    			$data .= fgets($fp, 128);
    		}
    
    		//seperate the header and actual content
    		$responseData = explode("\r\n\r\n", $data);
    	
    	
    		if ($localFileLoc===false)
    		{
    			return $responseData[1]; 
    		}
    		
    		if($localFile = fopen($localFileLoc, 'w'))
    		{
    			if(! fwrite($localFile, $responseData[1]))
    			{
    				return 'Could not write to local file';
    			}
    		}
    		
    		@fclose($fp);
    		@fclose($localFile);
    		
    		return true;
    	}
    }
    
    
    
    
    function get_media_types($extension=null) {
    	
		$values = array(
			'ogg'	=>	'video/ogg',
			'ogv'	=>	'video/ogg',
			'mp4'	=> 	'video/mp4',
			'flv'	=>	'video/flv',   
			'mov'	=>	'video/quicktime',
			'webm'  =>  'video/webm',
			'yt'	=>	'video/youtube',
            
			'mp3'	=>	'audio/mp3',
			'oga'	=> 	'audio/oga'
    	);
        
        return ($extension!=null) ? $values[$extension] : $values;
        
    }
    

    function remove_js_from_rss( $content ) {    
        return preg_replace('/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i', '',$content);
    }
    
    function parse_shortcodes( $attributes) {
        return ProjekktorWP::get_media_markup(ProjekktorWP::clean_shortcode_attributes($attributes));
    }
    
    function clean_shortcode_attributes( $attributes) {

        if (count($attributes)==0) return $content;       
        
        global $post, $id;
    
        $playlist = false;
        $playerConfig = array();
        $cleanAttributes = $attributes;
        $cleanAttributes['playerId'] = 'playerBox'.ProjekktorWP::get_id($id).ProjekktorWP::get_id(rand(1,10000));
        
    	// set poster    
        $cleanAttributes['poster'] = '';
        if (@$attributes['poster']!='') {
             $cleanAttributes['poster'] = $attributes['poster'];
        } else {
             $cleanAttributes['poster'] = ProjekktorWP::get_option_value('poster');
        } 
                
        // set width
        $cleanAttributes['width'] = 480;
        if (@$attributes['width']!= '') {
            $cleanAttributes['width'] = $attributes['width']+0;
        } else {
            $cleanAttributes['width'] = ProjekktorWP::get_option_value('width')+0;
        }     
        
        // set height
        $cleanAttributes['height'] = 320;
        if (@$attributes['height']!= '') {
            $cleanAttributes['height'] = $attributes['height']+0;
        } else {
            $cleanAttributes['height'] = ProjekktorWP::get_option_value('height')+0;
        }      
    
    
        // set controld
        $cleanAttributes['controls'] = '';
        if (in_array('controls', $attributes)) {
            $cleanAttributes['controls'] = true;
        } else {
            $cleanAttributes['controls'] = ProjekktorWP::get_option_value('controls');
        }      


        // set loop
        $cleanAttributes['loop'] = '';
       if (in_array('loop', $attributes)) {
            $cleanAttributes['loop'] = true;
        }      
        
        // set autoplay
        $cleanAttributes['autoplay'] = false;
        if (in_array('autoplay', $attributes)) {
            $cleanAttributes['autoplay'] = true;
        } elseif (in_array('autostart', $attributes)) {
            $cleanAttributes['autoplay'] = true;
        }         
    
        if (in_array('playlist', $attributes)) {
            $cleanAttributes['playlist']  = ProjekktorWP::get_playlist_url();
            
            $params = array();
            if ($attributes['id'])  $params[] = 'id='.($attributes['id']+0);
            if ($attributes['limit'])  $params[] = 'limit='.($attributes['limit']+0);
            if ($attributes['offset'])  $params[] = 'offset='.($attributes['offset']+0);
    
            if (count($params)>0) {
                $cleanAttributes['playlist'] .= '&'.join('&amp;', $params);
            }
            
        };
        
        $cleanAttributes['postId'] = $id;
        
        return $cleanAttributes;
    }    
    
    
    function get_media_markup($attributes) {
    
        // build HTML5 media tag
        $tagType = '';
    	$output = '<div id="'.$attributes['playerId'].'" class="projekktor" style="width:'.$attributes['width'].'px;height:'.$attributes['height'].'px;"></div>'."\n";		
           
        $playlist = @$attributes['playlist'];

	if ($playlist==false)
	{
		if (@$attributes['src']!='')
		{
			$playlist[0][] = array('src' => $attributes['src']);
		}
		
		foreach(ProjekktorWP::get_media_types() as $mediaExtension => $mediaType) 
		{
			
			// nothing
			if (@$attributes['src_'.$mediaExtension]=='') continue;
			
			// regular source
			$tagType = explode('/', $mediaType);
			$tagType = $tagType[0];

			$playlist[0][] = array('src' => $attributes['src_'.$mediaExtension], 'type'=>$mediaType);
		}
	}
        

        // build JS code
        $playerConfig = array(    	   
            "autoplay"  => $attributes['autoplay'],    
            "ID"        => $attributes['postId'],
            "width"     => $attributes['width'],
            "height"    => $attributes['height'],
	    "controls"	=> $attributes['controls'],
	    "loop"	=> $attributes['loop'],
	    "poster"	=> $attributes['poster'],
            "playlist"	=> $playlist
        );

	$output .= '<script type="text/javascript">'."\n";
	$output .= 'jQuery(document).ready(function($) {'."\n";        
	$output .= 'projekktor("#'.$attributes['playerId'].'", $.extend(true, {}, PROJEKKTORGLOBAL, '.ProjekktorWp::json_enc($playerConfig).'));'."\n";
	$output .= '})</script>'."\n";    

    	// debugging output:
    	// $output .= '<pre style="width: 500px; overflow: auto;">'.htmlentities($output).'</pre>';
    	
    	return $output;
            
    }

    
    
    
    /* projekktor config panel itself */
    function page_backend() {
    	
    	if ( isset($_POST['submit']) ) {
    		    		
    		foreach(ProjekktorWP::get_config_options() as $configKey => $valueSets) {
    			// if (!isset($_POST['projekktor_'.$configKey])) continue;
                $value = $_POST['projekktor_'.$configKey];
    			if ($valueSets['edt']===false) continue;
                if ($value=='' && ($valueSets['val']=='false' || $valueSets['val']=='true')) {
                    $value = 'false';
                } 
    			update_option('projekktor_'.$configKey, $value);
    		}
    		echo '<div class="updated fade">'.__('Projekktor Settings Saved!').'</div>';
    	}
    	
    	$plpath = WP_PLUGIN_URL . '/'.basename((dirname(__FILE__)));
    	
         $fieldsHTML = '<form action="" method="post" style="margin: auto; ">';         
        $first = true;
        
    	foreach(ProjekktorWP::get_config_options() as $configKey => $valueSets) {
    		
    		if ($valueSets['editable']===false) continue;
    		
    		$value = ProjekktorWP::get_option_value($configKey);
    
    		$size = (is_string($valueSets['val'])) ? 90 : 12;
            
            if ($valueSets['heading']!='') {            
                
                if ($first!==true) {
                    $fieldsHTML .= '<p class="submit"><input type="submit" name="submit" value="'.__('Save Changes &raquo;').'" /></p>';
                    $fieldsHTML .= '</div><!-- close inside -->';
                    $fieldsHTML .= '</div><!-- close postbox -->';
                } 
                    
                $first = false;                            
                $fieldsHTML .= '<div class="postbox">';
                $fieldsHTML .= '<h3 class="hndle"><span>'.__($valueSets['heading']).'</span></h3>';
                $fieldsHTML .= '<div class="inside">';
          
                                 
            } 
            
    		$fieldsHTML .= '<p>';    		
    
            $desc = $valueSets['txt'];
            
    		if ($valueSets['val']==='true' || $valueSets['val']==='false') {
                // BOOL                
                $fieldsHTML .= '<label for="projekktor_'.$configKey.'">';	
                $checked = ($value==true) ? ' checked' : '';
                $fieldsHTML .= sprintf($desc, '<input type="checkbox" id="projekktor_'.$configKey.'" name="projekktor_'.$configKey.'" value="true"'.$checked.' /> ');
                $fieldsHTML .= '</label>';                
    		}
            else if (is_int($valueSets['val'])) {
                // NUMBER
                $fieldsHTML .= '<label for="projekktor_'.$configKey.'">';	
    			$fieldsHTML .= sprintf($desc, '<input id="projekktor_'.$configKey.'" name="projekktor_'.$configKey.'" type="text" size="5" maxlength="5" value="'.$value.'" />');
                $fieldsHTML .= '</label>';                
            }            
            else if (is_float($valueSets['val'])) {
                // FLOAT
                $fieldsHTML .= '<label for="projekktor_'.$configKey.'">';	
                $fieldsHTML .= sprintf($desc,ProjekktorWP::build_select('projekktor_'.$configKey,range(0, 100), $value));
                $fieldsHTML .= '</label>';                						    					               
            }
            elseif ($valueSets['opts']!='') {
                // short options select                
                $fieldsHTML .= '<label for="projekktor_'.$configKey.'">';	
                $fieldsHTML .= sprintf($desc,ProjekktorWP::build_select('projekktor_'.$configKey, $valueSets['opts'], $value));
                $fieldsHTML .= '</label>';                						    					               
            }                
            elseif ($valueSets['short']===true) {
                // short STRING
                $fieldsHTML .= '<label for="projekktor_'.$configKey.'">';	
    			$fieldsHTML .= sprintf($desc, '<br/><input id="projekktor_'.$configKey.'" name="projekktor_'.$configKey.'" type="text" size="10" maxlength="10" value="'.$value.'" />');
                $fieldsHTML .= '</label>';                
            }       
            elseif ($valueSets['func']!='') {
                // stuff
                $fieldsHTML .= ProjekktorWP::$valueSets['func']($value, 'projekktor_'.$configKey, $desc);
                  
            }     
            else {
                // STRING
                $fieldsHTML .= '<label for="projekktor_'.$configKey.'">';	
    			$fieldsHTML .= $desc.'<br/><input id="projekktor_'.$configKey.'" name="projekktor_'.$configKey.'" type="text" size="'.$size.'" maxlength="'.(($size<50) ? '12' : $size).'" value="'.$value.'" />';
                $fieldsHTML .= '</label>';   
            }
                
            if ($valueSets['help']!='') {
                $fieldsHTML .= sprintf('&nbsp;<a target="_blank" href="%s">[?]</a>', $valueSets['help']);
            }            
    		$fieldsHTML .= '</p>';
    	}
        $fieldsHTML .= '<p class="submit"><input type="submit" name="submit" value="'.__('Save Changes &raquo;').'" /></p>';
        $fieldsHTML .= '</div><!-- close inside -->';
        $fieldsHTML .= '</div><!-- close postbox -->';      
        $fieldsHTML .= '</form>';        


   		echo '<div class="wrap"><h2>'.__('Projekktor for WP')." V" . ProjekktorWP::get_plugin_version().'</h2>';
        echo '<div id="poststuff" class="metabox-holder has-right-sidebar">';
        
        echo '<div class="inner-sidebar">';
    
            echo '<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">';
    
     
            echo '<div class="postbox">';
            echo '<h3 class="hndle"><span>General Info:</span></h3>';
            echo '<div class="inside">';
            $localVer = ProjekktorWP::get_player_version();
            // later $remoteVer = ProjekktorWP::get_player_version_remote();
            echo '<ul>';
            echo '<li>Player Core Version: '.$localVer."</li>";            
            echo '<li>Items in Playlist: '.ProjekktorWP::get_playlist_length()."</li>";
            echo '<li>webTV / widget URL: <a target="_blank" href="'.ProjekktorWP::get_player_url().'">click here</a></li>';
            echo '<li>Playlist URL: <a target="_blank" href="'.ProjekktorWP::get_playlist_url().'">click here</a></li>';                 
            echo '</ul>';
            echo '</div>'; 
            echo '</div>';    
            
            
            
            echo '<div class="postbox">';
            echo '<h3 class="hndle"><span>About Projekktor:</span></h3>';
            echo '<div class="inside">';
            echo '<ul>';
            echo '<li><a target="_blank" href="http://www.projekktor.com" title="Projekktor Homepage">Projekktor Homepage</a></li>';
            echo '<li><a target="_blank" href="http://www.projekktor.com/docs/wp" title="ProjekktorWP Docs">Projekktor for WP Documentation</a></li>';
            echo '<li><a target="_blank" href="http://www.projekktor.com/license.php#donate" title="Improve your Qi!">Support this project: Donate</a></li>';            
            echo '</ul>';
            echo '</div>'; 
            echo '</div>';               
        
            /* later:
            echo '<div class="postbox" id="pp_abaout">';
            echo '<h3 class="hndle"><span>Config Reset:</span></h3>';
            echo '<div class="inside">';
            echo '<ul>';
            echo '<li><a href="http://www.projekktor.com" title="homepage">Reset all parameters to defaults.</a></li>';                        
            echo '<ul>';
            echo '</div>'; 
            echo '</div>';      
            */

                                
            echo '</div><!-- close sortables  -->';            
            
        echo '</div><!-- close inner sidebar  -->';
        
        
        echo '<div class="has-sidebar sm-padded" >';        
        echo '<div id="post-body-content" class="has-sidebar-content">';        
        echo '<div class="meta-box-sortabless">';
			
        echo $fieldsHTML;
  
        
        echo '</div><!-- close wpcontentpoststuff -->';
        echo '</div><!-- close wpwrap -->';
        echo '</div><!-- close wpwrap -->';    	
      	
    }
    
    
    /* projekktor the iframed player */
    function page_player() {   
    
                

        $result = '<!DOCTYPE HTML><html><head><title>'.get_bloginfo('name').' TV</title>'."\n";
        
        $result .= '<style type="text/css">body { background-color: #000; margin: 0px; padding: 0px;}</style>'."\n";
        
        $theme = ProjekktorWP::get_option_value('theme');
        $result .= '<link rel="stylesheet" href="'.ProjekktorWP::get_plugin_url().'/themes/'.$theme.'/style.css?ver='.ProjekktorWP::get_theme_diz($theme, 'version').'" type="text/css" media="screen" />'."\n";
        // $result .= '<link rel="stylesheet" href="'.ProjekktorWP::get_plugin_url().'/style/psocial.css?ver='.ProjekktorWP::get_theme_diz($theme, 'version').'" type="text/css" media="screen" />'."\n";
          
        $result .= '<script type="text/javascript" src="'.ProjekktorWP::get_plugin_url().'js/jquery.js"></script>'."\n";        
        $result .= '<script type="text/javascript" src="'.ProjekktorWP::get_plugin_url().'js/projekktor.min.js"></script>'."\n";
                
        $result .= '</head><body>'."\n";
        $result .= '<div class="projekktor" id="player"></div>';    
        $result .= '<script type="text/javascript">'."\n";
        $result .= 'jQuery(document).ready(function($) {'."\n";        
        
        // instantiate player
        $playerConfig = ProjekktorWP::get_player_config();
        $playerConfig['sandBox'] = true;
	$playerConfig['width'] = false;
	$playerConfig['height'] = false;
        // $playerConfig['debug'] = 'console';         
    

        // is webTV?
        if (count(ProjekktorWP::get_playlist_query())==0) 
        {
            if (ProjekktorWP::get_option_value('embedBaseIsTv'))
            { 
                $playerConfig['disableFullscreen'] = true;         
                $playerConfig['loop'] = true;           
                $result .= 'projekktor("#player", '.ProjekktorWp::json_enc($playerConfig).');'."\n";
                $result .= 'projekktor("player").setFile(\''.ProjekktorWp::get_playlist_url().'\', true);'."\n";
            } 
            else {
                $result .= 'projekktor("#player", '.ProjekktorWp::json_enc($playerConfig).');'."\n";
            }            
        } 
        else {                
            $result .= 'projekktor("#player", '.ProjekktorWp::json_enc($playerConfig).');'."\n";
            $result .= 'projekktor("player").setFile('.ProjekktorWp::json_enc( ProjekktorWP::get_playlist_content(ProjekktorWP::get_playlist_query())).');'."\n";                
        }
        
        $result .= '})</script>'."\n";   
        
        $result .= '</body></html>';        
        
     
        
        echo $result;
    }
    
    function build_select($name, $data=array(), $activeValue) {
    
        $result = '<select id="'.$name.'" name="'.$name.'">';
        foreach($data as $key => $value) {
            $checked = ($activeValue==$key) ? ' selected="selected" ' : '';
            $result .= '<option '.$checked.' value="'.$key.'">'.$value.'</option>';
        }
        $result .= '</select>';
        return $result;
    }
    
    
    /* save videoitems in posts to playlist */
    function item_save($postId) {
    	
    	global $wpdb;
    
    	$videoTypes = ProjekktorWP::get_media_types();	
    	$table_name = $wpdb->prefix . "projekktor_playlist";
    	$post = get_post($postId);
  
    
    	if ($post->post_type!='post' && $post->post_type!='revision') return;
    
    	// delete all old playlist entries for this postId
    	$sql = "DELETE FROM " . $table_name . " WHERE postId=".$postId.";";
    	$results = $wpdb->query( $sql );	
    	
    	// parse video tags	
    	$pattern = '(.?)\[(video|audio|media)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)';
    	preg_match_all('/'.$pattern.'/s', $post->post_content, $tagMatches);
    	
    	if (!$tagMatches[3]) return;
    	
    	// gather video entities
    	$videoObjects = array();
    	foreach($tagMatches[3] as $key => $tagEntitiy) {	
    		$attribs = shortcode_parse_atts($tagEntitiy);
    		if (count($attribs)==0) continue;
            $sources = 0;
    		foreach($attribs as $attribName => $attribValue) {
    			if (substr($attribName,0,3)=='src') {
    				$videoObjects[$key]['sources'][$sources]['src'] = $attribValue;
                    if ($videoTypes[substr($attribName,4)]!='') {
                        $videoObjects[$key]['sources'][$sources]['type'] = $videoTypes[substr($attribName,4)];
                    }
                    $videoObjects[$key]['sources'][$sources]['size'] = ProjekktorWP::get_remote_file_size($attribValue);
                    $sources++;
    				continue;
    			}
                if ($attribName!='loop' && $attribName!='autoplay') 
                {
   			          $videoObjects[$key]['config'][$attribName] = $attribValue;
                }
    		}
    	}
  
    	// INSERT entities to DB
    	foreach($videoObjects as $key => $videoData) {
    		$title = ($videoData['title']!='') ? $videoData['title'] : $post->post_title;
    		$sql = "INSERT INTO " . $table_name . " (created, title, postId, tagId, sources, config, status) " .
    		"VALUES ('" . $post->post_date . "','" . $wpdb->escape($title) . "',".$postId.", '".$key."' ,'" . serialize($videoData['sources']) . "','" . serialize($videoData['config']) . "', '".$post->post_status."')";
    		$results = $wpdb->query( $sql );	
    	}
    
    }
        
    /* generate projekktor playlist (webTV mode) */
    function generate_json_playlist($opts) {
    	header("Content-Type: application/json");
        echo ProjekktorWp::json_enc(ProjekktorWP::get_playlist_content($opts));    
    }
    
    function redirect_intercept() {
        global $wp_query;

        switch ($wp_query->get('projekktor')) {
            case 'tv':
            	header('HTTP/1.1 200 OK');
                ProjekktorWP::page_player();
                die();
            case 'schedule':
            	header('HTTP/1.1 200 OK');
                ProjekktorWP::generate_json_playlist(ProjekktorWP::get_playlist_query());
                die();
                                          
        }
 
    }    
    
    
    
	function register_page_backend() {		
		if (function_exists('add_options_page')) {
			add_options_page(__('ProjekktorWP','projekktor'), __('ProjekktorWP','projekktor'), 'level_10', ProjekktorWP::get_base_name(), array('ProjekktorWP','page_backend'));
		}
	}

	function register_frontend_header() {
            
        // projekktor global JS config
        $output = '<script type="text/javascript">'."\n";
        $output .= 'var PROJEKKTORGLOBAL = '.ProjekktorWp::json_enc(ProjekktorWP::get_player_config()).';'."\n";
        $output .= '</script>'."\n";    
        
        // theme CSS
        $theme = ProjekktorWP::get_option_value('theme');
        $output .= '<link rel="stylesheet" href="'.ProjekktorWP::get_plugin_url().'/themes/'.$theme.'/style.css?ver='.ProjekktorWP::get_theme_diz($theme, 'version').'" type="text/css" media="screen" />';
        // $output .= '<link rel="stylesheet" href="'.ProjekktorWP::get_plugin_url().'/style/psocial.css?ver='.ProjekktorWP::get_theme_diz($theme, 'version').'" type="text/css" media="screen" />';
        echo $output;
	}
    
        
	function set_rewrites() {
		global $wp_rewrite;

		// Define custom rewrite tokens
		$rewrite_tag = '%projekktor%';
		
		// Add the rewrite tokens
		$wp_rewrite->add_rewrite_tag( $rewrite_tag, '(.+?)', 'projekktor=' );
			
		// Define the custom permalink structure
		$rewrite_keywords_structure = $wp_rewrite->root . "%pagename%/$rewrite_tag/";
		
		// Generate the rewrite rules
		$new_rule = $wp_rewrite->generate_rewrite_rules( $rewrite_keywords_structure );
	 
		$wp_rewrite->rules = $new_rule + $wp_rewrite->rules;

		return $wp_rewrite->rules;
	} // End create_custom_rewrite_rules()
	
	/**
	* add_custom_page_variables()
	* Add the custom token as an allowed query variable.
	* return array $public_query_vars.
	**/
	
	function add_custom_page_variables( $public_query_vars ) {
		$public_query_vars[] = 'projekktor';
        $public_query_vars[] = 'limit';
        $public_query_vars[] = 'offset';
        $public_query_vars[] = 'id';
		return $public_query_vars;
	} // End add_custom_page_variables()
	
	/**
	* flush_rewrite_rules()
	* Flush the rewrite rules, which forces the regeneration with new rules.
	* return void.
	**/	
	function flush_rewrite_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules(); 	
	} // End flush_rewrite_rules()    
        
	/**
	 * Returns the widget url 
	 */
	function get_player_url() {
		return home_url().'?projekktor=tv';
	}
  
	/**
	 * Returns the playlist "API" url
	 */  
	function get_playlist_url() {
		return home_url().'?projekktor=schedule';
	}  
	
        
	/**
	 * Returns the plugin´s basename
	 */
	function get_base_name() {
		return plugin_basename(__FILE__);
	}

	/**
	 * Returns the name of this script
	 */
	function get_plugin_file() {
		return __FILE__;
	}
	
	/**
	 * Returns the plugin version
	 */
	function get_plugin_version() {
		if(!isset($GLOBALS["projekktor_version"])) {
			if(!function_exists('get_plugin_data')) {
				if(file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) require_once(ABSPATH . 'wp-admin/includes/plugin.php'); //2.3+
				else if(file_exists(ABSPATH . 'wp-admin/admin-functions.php')) require_once(ABSPATH . 'wp-admin/admin-functions.php'); //2.1
				else return "0.ERROR";
			}
			$data = get_plugin_data(__FILE__, false, false);
			$GLOBALS["projekktor_version"] = $data['Version'];
		}
		return $GLOBALS["projekktor_version"];
	}    
    
	/**
	 * Returns the URL to this plugin´s directory
	 */
	function get_plugin_url() {
		
		// WP 2.6
		if (function_exists('plugins_url')) {
            return trailingslashit(plugins_url(basename(dirname(__FILE__))));
        }
		
		$path = dirname(__FILE__);
		$path = str_replace("\\","/",$path);
		$path = trailingslashit(get_bloginfo('wpurl')) . trailingslashit(substr($path,strpos($path,"wp-content/")));
		return $path;
	}
    
    function get_playlist_query() {        
        
        $opts = array('offset', 'limit', 'id');
        
        $result = array();
        
        foreach($opts as $void => $key) 
        {
            if (get_query_var($key)+0>0) 
            {
                $result[$key] = get_query_var($key)+0;
            } 
        }
        return $result;        
    }  
    
	function &get_plugin_instance() {
		if(!isset($GLOBALS["projekktor_instance"])) {
			$GLOBALS["projekktor_instance"]=new ProjekktorWP();
		}
        return $GLOBALS["projekktor_instance"];
	}    
    
    function get_id($number) {
        $number =  strval($number);
        $chars = "ABCDEFGHIOJKLMNOPQRST";
	    $result = '';
	    for ($i=1; $i<=strlen($number); $i++) {
		    $rnum = substr($number, $i,1);
		    $result .= substr($chars, $rnum, 1);
	    }
	    return $result;
      
    }
    
    
    function get_theme_selector($value, $name, $desc) {
        // send directory as JSON file list
    	$result = array();
    	$dir = dirname(__FILE__).'/themes/';
    	$d = @dir($dir);

        $html = '<h4>'.$desc.'</h4>';
    	$html .= '<ul>';
    	while(false !== ($entry = $d->read())) {
            if($entry=='.' || $entry=='..') continue;
            $result = ProjekktorWP::get_theme_diz($entry);
                    	
    		$checked = ($value==$entry) ? ' checked' : '';
    		$style = ($checked!='') ? 'background-color:#FFFFE0;' : '';
    		$html .= '<li style="padding: 3px;text-align:center;float:left;'.$style.'">'; 
    		$html .= $result['name'].' <br/> by ';
    		$html .= '<a href="'.$result['authorURI'].'">'.$result['author'].'</a>';
    		$html .= '<div><img src="'.$result['previewImg'].'" width="150" height="150"/></div>';
    		$html .= '<input type="radio" name="'.$name.'" value="'.$entry.'"'.$checked.' />';
    		$html .= '</li>';
    	}
        	
    	$html .= '</ul><div style="clear:both;"></div>';
        
    	$d->close();
        return $html;
    }
    
    function get_theme_diz($name, $get='') {	
        $result = array();   
        $pattern = array(
		'name' 		                  => 'Theme Name:',
		'URI'		                  => 'Theme URI:',
		'previewImg'	              => 'Preview Image:',
		'desc'		                  => 'Description:',
		'author'	                  => 'Author:',
		'authorURI'	                  => 'Author URI:',
		'license'	                  => 'License:',
		'version'	                  => 'Version:',
		'controlsTemplate'            => 'config.controlsTemplate:',
		'controlsTemplateFull'        => 'config.controlsTemplateFull:',
		'controlsTemplate'            => 'config.controlsTemplate:',
		'toggleMute'                  => 'config.toggleMute:'
        );
    
        if (!$file_handle = fopen(dirname(__FILE__).'/themes/'.$name.'/theme_id.diz', "r"))
	{
		return;
	}
        while (!feof($file_handle)) {
           
            $line = fgets($file_handle);
            
            foreach($pattern as $varName => $patternString)
            {
                if (substr($line, 0, strlen($patternString))==$patternString)
                {
            	  $result[$varName] = trim(str_replace($patternString, '', $line));
                  if ($get!='' && $varName==$get) 
                  {
                    return $result[$varName];
                  }
                }
            }
        }
        fclose($file_handle);
        return $result;
        
    }    
    
    function json_enc($data) {    
        if (!function_exists('json_encode')) 
        {
            // fallback for PHP < 5.2
            require_once 'JSON/JSON.php';        
            $services_json = new Services_JSON();
            return $services_json->encode($data);
        }
        else
        {
            return json_encode($data);    
        }    
    }
    
	function get_library_field($value, $field_id, $desc) { 
		$media_upload_iframe_src = "media-upload.php?post_id=$field_id";
		$image_upload_iframe_src = apply_filters('image_upload_iframe_src', "$media_upload_iframe_src" . "&amp;type=image&amp;projekktorfield=$field_id&amp;tab=library");

		$result .= $desc.'<input id="'.$field_id.'" name="'.$field_id.'" type="text" value="'.$value.'" size="90"/>';
		$result .= '<a href="'.$image_upload_iframe_src.'&amp;TB_iframe=true" id="'.$field_id.'_ml" class="thickbox" title="'.$image_title.'"><img src="images/media-button-image.gif" alt="'.$image_title.'" /></a>';

        return $result;    
    }    
	    
    /**
    *    ____       
    *   /# /_\_     
    *  |  |/o\o\   ,-----------------------------,      
    *  |  \\_/_/   |  sigh - what a stupid mess. | 
    * / |_   |     |-----------------------------/  
    *|  ||\_ ~|  _/ 
    *|  ||| \/     
    *|  |||_       
    * \//  |       
    *  ||  |       
    *  ||_  \      
    *  \_|  o|     
    *  /\___/      
    * /  ||||__    
    *   (___)_)
    */
}

