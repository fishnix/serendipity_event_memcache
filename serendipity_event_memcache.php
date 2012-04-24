<?php 

/*

    Memcache Plugin for Serendipity
    E. Camden Fisher <fishnix@gmail.com>
    
*/

if (IN_serendipity != true) {
    die ("Don't hack!"); 
}
    
$time_start = microtime(true);
//require_once 'sdk-1.3.5/sdk.class.php';

// Probe for a language include with constants. Still include defines later on, if some constants were missing
$probelang = dirname(__FILE__) . '/' . $serendipity['charset'] . 'lang_' . $serendipity['lang'] . '.inc.php';

if (file_exists($probelang)) {
    include $probelang;
}

include_once dirname(__FILE__) . '/lang_en.inc.php';

class serendipity_event_memcache extends serendipity_event
{

    function example() 
    {
      echo PLUGIN_MEMCACHE_INSTALL;
    }

    function introspect(&$propbag)
    {
        global $serendipity;

        $propbag->add('name',         PLUGIN_MEMCACHE_NAME);
        $propbag->add('description',  PLUGIN_MEMCACHE_DESC);
        $propbag->add('stackable',    false);
        $propbag->add('groups',       array('Frontend: External Services'));
        $propbag->add('author',       'E Camden Fisher <fish@fishnix.net>');
        $propbag->add('version',      '0.0.2');
        $propbag->add('requirements', array(
            'serendipity' => '1.5.0',
            'smarty'      => '2.6.7',
            'php'         => '5.2.0'
        ));

      // make it cacheable
      $propbag->add('cachable_events', array(
            'frontend_display' => true));
            
      $propbag->add('event_hooks',   array(
        /*'entries_header' => true,
        'entry_display' => true,
        'backend_entry_presave' => true,
        'backend_publish' => true,
        'backend_save' => true,
        'frontend_image_add_unknown' => true,
        'frontend_image_add_filenameonly' => true,
        'frontend_image_selector_submit' => true,
        'frontend_image_selector_more' => true,
        'frontend_image_selector_imagecomment' => true,
        'frontend_image_selector_imagelink' => true,
        'frontend_image_selector_imagealign' => true,
        'frontend_image_selector_imagesize' => true,
        'frontend_image_selector_hiddenfields' => true,
        'frontend_image_selector' => true,
        'backend_image_add' => true,
        'backend_image_addHotlink' => true,
        'backend_image_addform' => true,
        'frontend_display' => true,
        'backend_preview' => true */
        'frontend_fetchentry' => true
        ));

      $this->markup_elements = array(
          array(
            'name'     => 'ENTRY_BODY',
            'element'  => 'body',
          ),
          array(
            'name'     => 'EXTENDED_BODY',
            'element'  => 'extended',
          ),
          array(
            'name'     => 'HTML_NUGGET',
            'element'  => 'html_nugget',
          )
      );

        $conf_array = array();

        foreach($this->markup_elements as $element) {
            $conf_array[] = $element['name'];
        }

        $conf_array[] = 'memcache_on';
        $conf_array[] = 'memcache_host';
        $conf_array[] = 'memcache_port';
        $conf_array[] = 'memcache_opts';

        $propbag->add('configuration', $conf_array);
    }

    function generate_content(&$title) {
      $title = $this->title;
    }

    function introspect_config_item($name, &$propbag) {
      switch($name) {
        case 'memcache_on':
          $propbag->add('name',           PLUGIN_MEMCACHE_ON);
          $propbag->add('description',    PLUGIN_MEMCACHE_ON_DESC);
          $propbag->add('default',        'true');
          $propbag->add('type',           'boolean');
        break;
        case 'memcache_host':
          $propbag->add('name',           PLUGIN_MEMCACHE_HOST);
          $propbag->add('description',    PLUGIN_MEMCACHE_HOST_DESC);
          $propbag->add('default',        'localhost');
          $propbag->add('type',           'string');
        break;
        case 'memcache_port':
          $propbag->add('name',           PLUGIN_MEMCACHE_PORT);
          $propbag->add('description',    PLUGIN_MEMCACHE_PORT_DESC);
          $propbag->add('default', '11211');
          $propbag->add('type', 'string');
        break;
        case 'memcache_opts':
          $propbag->add('name',           PLUGIN_MEMCACHE_OPTS);
          $propbag->add('description',    PLUGIN_MEMCACHE_OPTS_DESC);
          $propbag->add('default', '');
          $propbag->add('type', 'string');
        break;
        default:
          return false;
        break;
        
      }
      
      return true;
    }
    
    /*
     *
     * install plugin
     *
     */
    function install() {
        serendipity_plugin_api::hook_event('backend_cache_entries', $this->title);
        
        $m = new Memcached();
        $m->addServer($memcache_host, $memcache_port);
        print_r($m->getStats());
        
    }

    /*
     *
     * uninstall plugin
     *
     */
    function uninstall() {
        serendipity_plugin_api::hook_event('backend_cache_purge', $this->title);
        serendipity_plugin_api::hook_event('backend_cache_entries', $this->title);
    }


    function cleanup() {
        global $serendipity;
    
        // kill entries in memcached
        $m = new Memcached();
        $m->addServer($memcache_host, $memcache_port);
        print_r($m->getStats());
        $m->flush(10);
        print_r($m->getStats());
        
        // we should rebuild the cache if we change configs
        serendipity_plugin_api::hook_event('backend_cache_purge', $this->title);
        serendipity_plugin_api::hook_event('backend_cache_entries', $this->title);
    }

		/*
		 *
		 * Purge the entries from memcache
		 *
		 */
		function purgeCache()
		{
				global $serendipity;	
        // purge the cache
		}
		
		/*
		 *
		 * Build the object list cache
		 * We should only build the mechanism we're using!
		 * 
		 */
		function buildCache()
		{
				global $serendipity;
        // build the cache
		}

    function event_hook($event, &$bag, &$eventData) {
        global $serendipity;
        
        $hooks = &$bag->get('event_hooks');
        
        if (isset($hooks[$event])) {
          switch($event) {
            case 'frontend_fetchentry':
                return true;
                break;

            default:
              return false;
            } 
        } else {
        return false;
      }
    }

}

?>