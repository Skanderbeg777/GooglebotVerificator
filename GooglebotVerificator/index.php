<?php
/*
 * Plugin Name: Googlebot Verificator
 * Description: Верификатор Гуглбота. Скрывает указанные в настройках домены от пользователей и оставляет их для Гуглбота.
 * Version:     1.0.1
 */

function GV_plugin_settings_link($links) { 
    $settings_link = '<a href="options-general.php?page=googlebotverificator">Настройки</a>'; 
    array_unshift($links, $settings_link); 
    return $links; 
  }

  function GV_register_settings() {
    add_option('GV_option_domains', 'tutors.gioschool.com*cashback-aliexpress.top');

    register_setting('GV_options_group', 'GV_option_domains', 'GV_callback');
  }

  add_action('admin_init', 'GV_register_settings');
  $plugin = plugin_basename(__FILE__);
  add_filter("plugin_action_links_$plugin", 'GV_plugin_settings_link');
  
  function GV_register_options_page() {
      add_options_page('Googlebot Verificator', 'Googlebot Verificator', 'manage_options', 'googlebotverificator', 'GV_options_page');
  }

  add_action('admin_menu', 'GV_register_options_page');

  function GV_on_activation() {
      global $wpdb;
      $table_name = $wpdb->get_blog_prefix().'GV';
      $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

      $sql = "CREATE TABLE {$table_name} (
        id int(11) unsigned NOT NULL auto_increment,
        urls text NOT NULL,
        post_url text NOT NULL,
        swua longtext NOT NULL,
        PRIMARY KEY  (id)
    ) {$charset_collate};";

    dbDelta($sql);
  }

  function GV_on_deactivation() {
    remove_filter('the_content', 'main_filter', 0);
  }

  function GV_on_uninstall() {
      global $wpdb;
      $table_name = $wpdb->get_blog_prefix().'GV';
      $sql = "DROP TABLE IF EXISTS ".$table_name;
      $wpdb->query($sql);

      delete_option('GV_option_domains');
  }

  register_activation_hook(__FILE__, 'GV_on_activation');
  register_deactivation_hook(__FILE__, 'GV_on_deactivation');
  register_uninstall_hook(__FILE__, 'GV_on_uninstall');

  function GV_options_page() {
      ?>
      <form method="post" action="options.php">
        <?php settings_fields('GV_options_group') ?>
        <h3>Googlebot Verificator Settings</h3>
        <table>
            <tr valign="top">
                <td>
                    <label for="GV_option_domains">Domains to hide separated with *:</label>
                    <input type="text" id="GV_option_domains" name="GV_option_domains" value="<?php echo get_option('GV_option_domains'); ?>">
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
      </form>
      <?php
  }

  if (!is_admin()) {
    add_action('wp_loaded', 'buffer_start');    function buffer_start() { ob_start("hideLinks"); }
    add_action('shutdown', 'buffer_end');       function buffer_end()   { ob_end_flush(); }
  }

function hideLinks($content) {
    if ( isGooglebot($_SERVER['REMOTE_ADDR']) || is_admin() ) {
        return $content;
    } else {
        $url = parse_url($wp->request);
        $url = urldecode($url['path']);

        $dom = new DOMDocument(null, 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $links = $dom->getElementsByTagName('a');

        $parseServer = parse_url($_SERVER['SERVER_NAME']);

        $domains = get_option('GV_option_domains');
        $domains = explode("*", $domains);

        for ($i = $links->length; --$i >= 0; ) {
            $current_link = $links->item($i)->getAttribute('href');
            $parseLink = parse_url($current_link);

            if($parseServer['host'] !== $parseLink['host']) { //если не равны то ссылка внешняя
                if (isHiddenDomain($current_link, $domains)) {
                    $links->item($i)->parentNode->removeChild($links->item($i));
                }
            }
        }
        return utf8_encode( $dom->saveHTML());
    }
}

  function isHiddenDomain($url, $domains) {
      foreach ($domains as $domain) {
          if (stripos($url, $domain) !== false) return true;
      }
      return false;
  }

  function isGooglebot($ip) {
    $hostname = gethostbyaddr($ip);
    $googlebot = 'googlebot.com';
    $google = 'google.com';
    if (strpos($hostname, $google) !== false || strpos($hostname, $googlebot) !== false) {
        return true;
    }
    return false;
  }

?>