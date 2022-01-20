<?php
/*
Plugin Name: PeproDev Delivery Stages for WooCommerce
Description: Add multiple and customizable delivery stages for WooCommerce orders
Contributors: amirhosseinhpv,peprodev
Tags: functionality, woocommmerce, shipping, delivery, tracking, order tracking, shipping tracking, delivery tracking, delivery status
Author: Pepro Dev. Group
Developer: Amirhosseinhpv
Author URI: https://pepro.dev/
Developer URI: https://hpv.im/
Plugin URI: https://pepro.dev/wc-delivery-stages
Version: 1.1.0
Stable tag: 1.1.0
Requires at least: 5.0
Tested up to: 5.7
Requires PHP: 5.6
WC requires at least: 4.0
WC tested up to: 5.0.0
Text Domain: pepro-delivery-stages-for-woocommerce
Domain Path: /languages
Copyright: (c) 2020 Pepro Dev. Group, All rights reserved.
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
# @Date:   2020/08/26 08:49:33
# @Email:  its@hpv.im
# @Last modified by:   Amirhosseinhpv
# @Last modified time: 2020/12/14 04:21:59

defined("ABSPATH") or die("Pepro WC Delivery Stages :: Unauthorized Access!");
if (!class_exists("peproWoocommerceShippingStages")) {
    class peproWoocommerceShippingStages
    {
        private static $_instance = null;
        public $td;
        public $url;
        public $version;
        public $title;
        public $title_w;
        public $db_slug;
        private $plugin_dir;
        private $plugin_url;
        private $assets_url;
        private $plugin_basename;
        private $plugin_file;
        private $deactivateURI;
        private $deactivateICON;
        private $versionICON;
        private $authorICON;
        private $settingICON;
        private $db_table = null;
        private $manage_links = array();
        private $meta_links = array();
        /**
         * @method __construct
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function __construct()
        {
            global $wpdb;
            $this->td = "pepro-delivery-stages-for-woocommerce";
            self::$_instance = $this;
            $this->db_slug = "ppwcss";
            $this->db_table = $wpdb->prefix . $this->db_slug;
            $this->plugin_dir = plugin_dir_path(__FILE__);
            $this->plugin_url = plugins_url("", __FILE__);
            $this->assets_url = plugins_url("/assets/", __FILE__);
            $this->plugin_basename = plugin_basename(__FILE__);
            $this->url = admin_url("admin.php?page=wc-settings&tab=wc_shipping_stages");
            $this->plugin_file = __FILE__;
            $this->version = "1.0.0";
            $this->deactivateURI = null;
            $this->deactivateICON = '<span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-dismiss" aria-hidden="true"></span> ';
            $this->versionICON = '<span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-admin-plugins" aria-hidden="true"></span> ';
            $this->authorICON = '<span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-admin-users" aria-hidden="true"></span> ';
            $this->settingURL = '<span style="display: inline;float: none;padding: 0;" class="dashicons dashicons-admin-settings dashicons-small" aria-hidden="true"></span> ';
            $this->submitionURL = '<span style="display: inline;float: none;padding: 0;" class="dashicons dashicons-images-alt dashicons-small" aria-hidden="true"></span> ';
            $this->title = __("Pepro Delivery Stages for WooCommerce", $this->td);
            $this->title_s = __("WC Delivery Stages", $this->td);
            $this->title_w = sprintf(__("%2\$s ver. %1\$s", $this->td), $this->version, $this->title);
            add_action( "init", array($this, 'init_plugin'));

            if (isset($_GET["page"]) && isset($_GET["tab"])){
              if ($_GET["page"] == "wc-settings" && $_GET["tab"] == "wc_shipping_stages"){
                add_filter( 'woocommerce_admin_disabled', '__return_true');
                add_filter( 'woocommerce_marketing_menu_items', '__return_empty_array' );
                add_filter( 'woocommerce_helper_suppress_admin_notices', '__return_true' );
              }
            }

        }
        /**
         * Init Plugin
         *
         * @method init_plugin
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function init_plugin()
        {
            // Estimated Delivery Date
            add_filter("plugin_action_links_{$this->plugin_basename}", array($this, 'plugins_row_links'));
            add_action("plugin_row_meta", array( $this, 'plugin_row_meta' ), 10, 2);
            add_action("admin_menu", array($this, 'admin_menu'),1000);
            add_action("admin_init", array($this, 'admin_init'));
            add_action("wp_ajax_nopriv_{$this->db_slug}", array($this, 'handel_ajax_req'));
            add_action("wp_ajax_{$this->db_slug}", array($this, 'handel_ajax_req'));
            add_action("admin_enqueue_scripts", array($this, 'admin_enqueue_scripts'));
            add_action("save_post", array( $this, 'wc_save_shop_order_metabox' ) );
            add_action("woocommerce_order_details_before_order_table", array( $this, 'wc_order_details_before_order_table' ));
            include_once $this->plugin_dir . "include/class-setting.php";
            add_filter("woocommerce_admin_order_data_after_order_details", array($this, 'wc_order_data_after_order_details') );

        }
        /**
         * Wc Order Details Before Order Table Description
         *
         * @method wc_order_details_before_order_table
         * @param WC_Order $order
         * @return string HTML output
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function wc_order_details_before_order_table($order)
        {
          $order_id = $order->get_id();
          $estimated_date = get_post_meta( $order->get_id(), "estimated_delivery_date", true);
          $current_state = get_post_meta( $order->get_id(), "current_delivery_state", true);
          $showlabel = "yes" == get_option( "wccs_stages_label" );
          $caption__current_delivery_state = get_option( "wccs_caption_delivery_state", __("Current Delivery State: ",$this->td) );
          if (empty(trim($caption__current_delivery_state))){
            $caption__current_delivery_state = __("Current Delivery State: ",$this->td);
          }
          $caption__estimated_delivery_date = get_option( "wccs_caption_delivery_date", __("Estimated Delivery Date: ",$this->td) );
          if (empty(trim($caption__estimated_delivery_date))){
            $caption__estimated_delivery_date = __("Estimated Delivery Date: ",$this->td);
          }
          if (get_option( "wccs_stages_esdd" ) == "yes" && !empty(trim($estimated_date))){
            echo sprintf(
              "<p class='pepro_wc_delivery_stages estimated_delivery_date'>%s<span>%s</span></p>",
              ($showlabel?"<strong class='estimated_delivery_date_label'>".
              $caption__estimated_delivery_date."</strong>":""),
              $estimated_date);
          }

          // wccs_stages_style
          $foundedStageTitle = false;
          $foundedStageIcon = false;
          $foundedStageDesc = false;
          $all_stages = 0;
          $cur_stages = 0;

          wp_enqueue_style( "pepro-wc-delivery-stages", $this->assets_url . "frontend/css/pepro-wc-delivery-stages.css");

          $stages = get_option("{$this->db_slug}-data", $this->get_default_delivery_stages());
          if (empty($stages) || !is_array($stages)){ $stages = $this->get_default_delivery_stages(); }
          if (!empty($current_state)){
            foreach ($stages as $key => $value) {
              if ("yes" == $value["enabled"]){
                $all_stages+=1;
                if ($value["id"] == $current_state){
                  $cur_stages = $all_stages;
                  $foundedStageTitle = $value["title"];
                  $foundedStageIcon = $value["icon"];
                  $foundedStageDesc = $value["description"];
                }
              }
            }
          }

          if (get_option( "wccs_stages_style" ) !== "none" && $foundedStageTitle){
            // $foundedStageTitle,
            // $foundedStageIcon,
            // $foundedStageDesc,
            // $cur_stages,
            // $all_stages,
            switch (get_option("wccs_stages_style")) {
              case 'plaintext':
                $html_output = sprintf(
                  "<p class='pepro_wc_delivery_stages estimated_delivery_status plaintext'>%s<span class='wcss--title'>%s</span></p>",
                  ($showlabel?"<strong class='estimated_delivery_date_label'>".$caption__current_delivery_state."</strong>":""),
                  $foundedStageTitle
                );
                break;

              case 'richtext':
                $html_output = sprintf("<p class='pepro_wc_delivery_stages estimated_delivery_status richtext'>%s
                <span class='wcss--title'>%s</span>
                <span class='wcss--description'>%s</span>
                </p>",
                ($showlabel?"<strong class='estimated_delivery_date_label'>".$caption__current_delivery_state."</strong>":""),
                $foundedStageTitle,
                $foundedStageDesc);
                break;

              case 'progress':
                $html_output = sprintf(
                "<div class='pepro_wc_delivery_stages estimated_delivery_status progress'>%s
                  <div class='wcss--progressbar %s' title='%s'><div class='filled' style='width: %s%%;'></div></div>
                </div>",
                ($showlabel?"<strong class='estimated_delivery_date_label'>".$caption__current_delivery_state."</strong>":""),
                ($showlabel?"":"show--exrta"),
                esc_attr( $foundedStageTitle ),
                round( 100 / $all_stages * $cur_stages )
                );
                break;

              case 'bars':
                $showlabel = $showlabel?"<strong class='estimated_delivery_date_label'>".$caption__current_delivery_state."</strong>":"";
                $showExrta = $showlabel?"":"show--exrta";
                $html_output = "<div class='pepro_wc_delivery_stages estimated_delivery_status bars $showExrta'>$showlabel
                <div title='$foundedStageTitle' class='$showExrta'></div>";
                $precent = round(100/$all_stages);
                $reached = false;
                $filled = "filled";
                foreach ($stages as $key => $value) {
                  if ("yes" == $value["enabled"]){

                    if ($reached){ $filled = ""; }
                    if ($value["id"] == $current_state){ $reached = true; }
                    $html_output .= "<div class='wcss--progressbar' data-description='".esc_attr($value["description"])."' title='".esc_attr($value["title"])."'><div class='$filled' style='width: 100%;'></div></div>";
                  }
                }
                $html_output .= "</div>";
                break;

              case 'images':
                $showlabel = $showlabel?"<strong class='estimated_delivery_date_label'>".$caption__current_delivery_state."</strong>":"";
                $showExrta = $showlabel?"":"show--exrta";
                $html_output = "<div class='pepro_wc_delivery_stages estimated_delivery_status images'>$showlabel";
                $precent = round(100/$all_stages);
                $reached = false;
                $filled = "filled";
                foreach ($stages as $key => $value) {
                  if ("yes" == $value["enabled"]){

                    if ($reached){ $filled = ""; }
                    if ($value["id"] == $current_state){ $reached = true; }
                    $html_output .= "<div class='wcss--progress-img' data-description='".esc_attr($value["description"])."' title='".esc_attr($value["title"])."'><img src='".esc_attr( $value["icon"] )."' class='$filled'/></div>";
                  }
                }
                $html_output .= "</div>";
                break;

              default:
                $html_output = "";
                break;
            }
            echo apply_filters( "pepro-wc-delivery-status-frontend-output", $html_output, $order_id, $estimated_date, $current_state, get_option("wccs_stages_style"), $html_output);
          }

        }
        /**
         * Wc Order Data After Order Details Description
         *
         * @method wc_order_data_after_order_details
         * @param WC_Order $order
         * @return string setting for order
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function wc_order_data_after_order_details($order)
        {
          wp_nonce_field('security_nonce',"{$this->db_slug}_nonce");
          $estimated_date = get_post_meta( $order->get_id(), "estimated_delivery_date", true);
          $current_state = get_post_meta( $order->get_id(), "current_delivery_state", true);
          $options = "";
          $stages = get_option("{$this->db_slug}-data", $this->get_default_delivery_stages());

          if (empty($stages) || !is_array($stages)){
            $stages = $this->get_default_delivery_stages();
          }

          $link = "<a href='$this->url' title='".__("Manage Delivery Stages Details",$this->td)."' rel='tooltip' target='_blank' nofocus nounderline ><span class='dashicons dashicons-edit'></span></a>";

          foreach ($stages as $key => $value) {
            if ("yes" == $value["enabled"]){
              $options .= '<option value="'.$value["id"].'" '. selected($value["id"],$current_state, false ) .' >'.$value["title"].'</option>';
            }
          }
          echo '
          <div class="order_data_column"><h4>'.__( 'Delivery Details', $this->td ). " $link" . '</h4></div>
          <p>
            <p class="form-field form-field-wide wc-current_delivery_state">
              <label for="current_delivery_state">'.__( 'Current Delivery Stage', $this->td ).'</label>
              <select id="current_delivery_state" name="current_delivery_state" class="wc-enhanced-select">'.$options.'</select>
            </p>
            <p class="form-field form-field-wide wc-estimated_delivery_date">
              <label for="estimated_delivery_date">'.__( 'Estimated Delivery Date', $this->td ).'</label>
              <input type="text" style="width: 100%;" name="estimated_delivery_date" id="estimated_delivery_date" value="'.$estimated_date.'" placeholder="'.__( 'Enter Estimated Delivery Date', $this->td ).'"/>
            </p>
          </p>';
        }
        /**
         * Get Delivery Status Description
         *
         * @method get_delivery_status
         * @param integer $id
         * @return array full details
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function get_delivery_status($id=0)
        {
          $stages = get_option("{$this->db_slug}-data", $this->get_default_delivery_stages());
          if (empty($stages) || !is_array($stages)){ $stages = $this->get_default_delivery_stages(); }
          foreach ($stages as $key => $value) {
            if ("yes" == $value["enabled"]){
              if ($id == $value["id"]){
                return $value;
                break;
              }
            }
          }
          return false;
        }
        /**
         * Wc Save Shop Order Metabox Description
         *
         * @method wc_save_shop_order_metabox
         * @param int $post_id
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function wc_save_shop_order_metabox($post_id)
        {
            if (
              !isset( $_POST["{$this->db_slug}_nonce"] ) ||
              !wp_verify_nonce( $_POST["{$this->db_slug}_nonce"], 'security_nonce' ) ||
              !current_user_can( 'edit_post', $post_id ) ||
              ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
            ){
                return;
            }
            $prev_state = get_post_meta( $post_id, 'current_delivery_state',true );
            $prev_date = get_post_meta( $post_id, 'estimated_delivery_date',true );

            if ( !isset( $_POST['current_delivery_state'] ) ) {
              update_post_meta( $post_id, 'current_delivery_state', "" );
              do_action( "pepro-wc-delivery-status-cleared", $post_id);
            } else {
              update_post_meta( $post_id, 'current_delivery_state', sanitize_textarea_field($_POST['current_delivery_state']));
              if (trim($prev_state) != trim(sanitize_textarea_field($_POST['current_delivery_state']))){
                do_action( "pepro-wc-delivery-status-changed", $post_id, trim($prev_state), trim(sanitize_textarea_field($_POST['current_delivery_state'])));
              }
            }

            if ( !isset( $_POST['estimated_delivery_date'] ) ) {
              update_post_meta( $post_id, 'estimated_delivery_date', "" );
              do_action( "pepro-wc-delivery-date-cleared", $post_id);
            } else {
              update_post_meta( $post_id, 'estimated_delivery_date', sanitize_textarea_field($_POST['estimated_delivery_date']));
              if (trim($prev_date) != trim(sanitize_textarea_field($_POST['estimated_delivery_date']))){
                do_action( "pepro-wc-delivery-date-changed", $post_id, trim($prev_date), trim(sanitize_textarea_field($_POST['estimated_delivery_date'])));
              }
            }

        }
        /**
         * callback for ajax requesets
         *
         * @method handel_ajax_req
         * @return json ajax response
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function handel_ajax_req()
        {
          check_ajax_referer( $this->db_slug, 'nonce' );
          if ( wp_doing_ajax() && $_POST['action'] == $this->db_slug ) {
            do_action( "{$this->db_slug}__handle_ajaxrequests", $_POST);

            if ($_POST["wparam"] === "$this->db_slug"){
              switch ($_POST["lparam"]) {
                case 'savesettings':
                  if (isset($_POST["dparam"]) && !empty($_POST["dparam"])){
                    $data = array();
                    foreach ($_POST['dparam'] as $key => $value) {
                      $data[sanitize_text_field( $key )] = sanitize_textarea_field( $value );
                    }
                    update_option("{$this->db_slug}-data", $data);
                  }else{
                    update_option("{$this->db_slug}-data", "");
                  }
                  wp_send_json_success( array( "msg"=>__("Settings Successfully Saved.",$this->td), ) );
                  break;
                default:
                  wp_send_json_error(array("msg"=>__("Incorrect Data Supplied.",$this->td)));
                  break;
              }
            }
            die();
          }
        }
        /**
        * wp admin init hook
        *
        * @method admin_init
        * @param string $hook
        * @version 1.0.0
        * @since 1.0.0
        * @license https://pepro.dev/license Pepro.dev License
        */
        public function admin_init($hook)
        {
          if (!$this->_wc_activated()) {
            add_action( 'admin_notices', function () {
              echo "<div class=\"notice error\"><p>".sprintf(
                _x('%1$s needs %2$s in order to function', "required-plugin", "$this->td"),
                "<strong>".$this->title."</strong>", "<a href='".admin_url("plugin-install.php?s=woocommerce&tab=search&type=term")."' style='text-decoration: none;' target='_blank'><strong>".
                _x("WooCommerce", "required-plugin", "$this->td")."</strong> </a>"
                )."</p></div>";
              }
            );
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
            deactivate_plugins(plugin_basename(__FILE__));
          }
          $peproWoocommerceShippingStages_class_options = $this->get_setting_options();
          foreach ($peproWoocommerceShippingStages_class_options as $sections) {
            foreach ($sections["data"] as $id=>$def) {
              add_option($id, $def);
              register_setting($sections["name"], $id);
            }
          }
        }
        /**
         * Get Plugin Setting Options
         *
         * @method get_setting_options
         * @return array plugin settings
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function get_setting_options()
        {
          return array(
            array(
              "name" => "{$this->db_slug}_general",
              "data" => array(
                "{$this->db_slug}-clearunistall" => "no",
                "{$this->db_slug}-data" => $this->get_default_delivery_stages(),
              )
            ),
          );
        }
        public function get_default_delivery_stages()
        {
          return [
            array(
              "id" => 100001,
              "icon" => plugins_url("/assets/backend/images/wcss_4.svg", (__FILE__)),
              "enabled" => "yes",
              "title" => _x("Processing your Order","default-stages",$this->td),
              "description" => _x("Your Order is being prepared","default-stages",$this->td),
            ),
            array(
              "id" => 200002,
              "icon" => plugins_url("/assets/backend/images/wcss_14.svg", (__FILE__)),
              "enabled" => "yes",
              "title" => _x("Packing your Order","default-stages",$this->td),
              "description" => _x("Your Order is being packed","default-stages",$this->td),
            ),
            array(
              "id" => 300003,
              "icon" => plugins_url("/assets/backend/images/wcss_6.svg", (__FILE__)),
              "enabled" => "yes",
              "title" => _x("Delivered to Post","default-stages",$this->td),
              "description" => _x("Your Order has been sent to post office","default-stages",$this->td),
            ),
            array(
              "id" => 400004,
              "icon" => plugins_url("/assets/backend/images/wcss_12.svg", (__FILE__)),
              "enabled" => "yes",
              "title" => _x("Received","default-stages",$this->td),
              "description" => _x("You've successfully received your order","default-stages",$this->td),
            ),
          ];
        }
        /**
         * wp get_meta_link hool
         *
         * @method get_meta_links
         * @return array meta_link
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function get_meta_links()
        {
            if (!empty($this->meta_links)) {return $this->meta_links;}
            $this->meta_links = array(
                  'support'      => array(
                      'title'       => __('Support', $this->td),
                      'description' => __('Support', $this->td),
                      'icon'        => 'dashicons-admin-site',
                      'target'      => '_blank',
                      'url'         => "mailto:support@pepro.dev?subject={$this->title}",
                  ),
              );
            return $this->meta_links;
        }
        /**
         * wp get_manage_links hool
         *
         * @method get_manage_links
         * @return array get_manage_links
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function get_manage_links()
        {
            if (!empty($this->manage_links)) {return $this->manage_links; }
            $this->manage_links = array(
              $this->settingURL . __("Settings", $this->td) => "$this->url",
            );
            return $this->manage_links;
        }
        /**
         * Activation Hook
         *
         * @method activation_hook
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public static function activation_hook()
        {
        }
        /**
         * Deactivation Hook
         *
         * @method deactivation_hook
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public static function deactivation_hook()
        {
        }
        /**
         * Uninstall Hook
         *
         * @method uninstall_hook
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public static function uninstall_hook()
        {
            $ppa = new peproWoocommerceShippingStages;
            if (get_option("{$ppa->db_slug}-clearunistall", "no") === "yes") {
                $peproWoocommerceShippingStages_class_options = $ppa->get_setting_options();
                foreach ($peproWoocommerceShippingStages_class_options as $options) {
                    $opparent = $options["name"];
                    foreach ($options["data"] as $optname => $optvalue) {
                        unregister_setting($opparent, $optname);
                        delete_option($optname);
                    }
                }
            }
        }
        /**
         * Update Footer Info to Developer info
         *
         * @method update_footer_info
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function update_footer_info()
        {
           $f = "pepro_temp_stylesheet.".current_time("timestamp");
           wp_register_style($f, null);
           wp_add_inline_style($f," #footer-left b a::before { content: ''; background: url('{$this->assets_url}backend/images/peprodev.svg') no-repeat; background-position-x: center; background-position-y: center; background-size: contain; width: 60px; height: 40px; display: inline-block; pointer-events: none; position: absolute; -webkit-margin-before: calc(-60px + 1rem); margin-block-start: calc(-60px + 1rem); -webkit-filter: opacity(0.0);
           filter: opacity(0.0); transition: all 0.3s ease-in-out; }#footer-left b a:hover::before { -webkit-filter: opacity(1.0); filter: opacity(1.0); transition: all 0.3s ease-in-out; }[dir=rtl] #footer-left b a::before {margin-inline-start: calc(30px);}");
           wp_enqueue_style($f);
           add_filter( 'admin_footer_text', function () { return sprintf(_x("Thanks for using %s products", "footer-copyright", $this->td), "<b><a href='https://pepro.dev/' target='_blank' >".__("Pepro Dev", $this->td)."</a></b>");}, 11000 );
           add_filter( 'update_footer', function () { return sprintf(_x("%s — Version %s", "footer-copyright", $this->td), $this->title, $this->version); }, 1100 );
         }
        /**
         * Add Admin Menu
         *
         * @method admin_menu
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function admin_menu()
        {
          add_submenu_page("woocommerce", $this->title, __("Delivery Stages",$this->td), "manage_options", $this->url);
        }
        /**
         * localize js script
         *
         * @method localize_script
         * @param string $hrhook hook name
         * @return array i18n array
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function localize_script_data_table()
        {
          $currentTimestamp = current_time( "timestamp");
          $currentDate      = date_i18n( get_option('date_format'),$currentTimestamp);
          $currentTime      = date_i18n( get_option('time_format'),$currentTimestamp);

          return array(
            "td"                  => "{$this->db_slug}",
            "ajax"                => admin_url("admin-ajax.php"),
            "home"                => home_url(),
            "nonce"               => wp_create_nonce($this->db_slug),
            "title"               => _x("Select image file", "wc-setting-js", $this->td),
            "btntext"             => _x("Use this image", "wc-setting-js", $this->td),
            "clear"               => _x("Clear", "wc-setting-js", $this->td),
            "currentlogo"         => _x("Current preview", "wc-setting-js", $this->td),
            "selectbtn"           => _x("Select image", "wc-setting-js", $this->td),
            "tr_submit"           => _x("Submit","js-string",$this->td),
            "tr_today"            => _x("Today","js-string",$this->td),
            "errorTxt"            => _x("Error", "wc-setting-js", $this->td),
            "cancelTtl"           => _x("Canceled", "wc-setting-js", $this->td),
            "confirmTxt"          => _x("Confirm", "wc-setting-js", $this->td),
            "successTtl"          => _x("Success", "wc-setting-js", $this->td),
            "submitTxt"           => _x("Submit", "wc-setting-js", $this->td),
            "okTxt"               => _x("Okay", "wc-setting-js", $this->td),
            "txtYes"              => _x("Yes", "wc-setting-js", $this->td),
            "txtNop"              => _x("No", "wc-setting-js", $this->td),
            "cancelbTn"           => _x("Cancel", "wc-setting-js", $this->td),
            "sendTxt"             => _x("Send to all", "wc-setting-js", $this->td),
            "closeTxt"            => _x("Close", "wc-setting-js", $this->td),
            "deleteConfirmTitle"  => _x("Delete Submition", "wc-setting-js", $this->td),
            "deleteConfirmation"  => _x("Are you sure you want to delete submition ID %s ? This cannot be undone.", "wc-setting-js", $this->td),
            "clearDBConfirmation" => _x("Are you sure you want to clear all data from database? This cannot be undone.", "wc-setting-js", $this->td),
            "clearDBConfirmatio2" => _x("Are you sure you want to clear all Current Contact form data from database? This cannot be undone.", "wc-setting-js", $this->td),
            "clearDBConfTitle"    => _x("Clear Database", "wc-setting-js", $this->td),
            "str1"                => sprintf(_x("Product WC Delivery Stages Data Exported via %s", "wc-setting-js", $this->td),"$this->title_w"),
            "str2"                => sprintf(_x("Product WC Delivery Stages Export", "wc-setting-js", $this->td),$this->title_w),
            "str3"                => sprintf(_x("Exported at %s @ %s", "wc-setting-js", $this->td),$currentDate,$currentTime),
            "str4"                => "Product WC Delivery Stages export-". date_i18n("YmdHis",current_time( "timestamp")),
            "str5"                => sprintf(_x("Exported via %s — Export Date: %s @ %s — Developed by Pepro Dev. Group ( https://pepro.dev/ )", "wc-setting-js", $this->td),$this->title_w,$currentDate,$currentTime),
            "str6"                => "Product WC Delivery Stages",
            "tbl1"                => _x("No data available in table", "data-table", $this->td),
            "tbl2"                => _x("Showing _START_ to _END_ of _TOTAL_ entries", "data-table", $this->td),
            "tbl3"                => _x("Showing 0 to 0 of 0 entries", "data-table", $this->td),
            "tbl4"                => _x("(filtered from _MAX_ total entries)", "data-table", $this->td),
            "tbl5"                => _x("Show _MENU_ entries", "data-table", $this->td),
            "tbl6"                => _x("Loading...", "data-table", $this->td),
            "tbl7"                => _x("Processing...", "data-table", $this->td),
            "tbl8"                => _x("Search:", "data-table", $this->td),
            "tbl9"                => _x("No matching records found", "data-table", $this->td),
            "tbl10"               => _x("First", "data-table", $this->td),
            "tbl11"               => _x("Last", "data-table", $this->td),
            "tbl12"               => _x("Next", "data-table", $this->td),
            "tbl13"               => _x("Previous", "data-table", $this->td),
            "tbl14"               => _x(": activate to sort column ascending", "data-table", $this->td),
            "tbl15"               => _x(": activate to sort column descending", "data-table", $this->td),
            "tbl16"               => _x("Copy to clipboard", "data-table", $this->td),
            "tbl17"               => _x("Print", "data-table", $this->td),
            "tbl177"              => _x("Column visibility", "data-table", $this->td),
            "tbl18"               => _x("Export CSV", "data-table", $this->td),
            "tbl19"               => _x("Export Excel", "data-table", $this->td),
            "tbl20"               => _x("Export PDF", "data-table", $this->td),
            "nostatefound"        => _x("No State Found", "data-table", $this->td),
            "addnew"              => _x("Add New Entry", "data-table", $this->td),
          );
        }
        /**
         * wp admin enqueue scripts hook
         *
         * @method admin_enqueue_scripts
         * @param string $hook
         * @version 1.0.0
         * @since 1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        public function admin_enqueue_scripts($hook)
        {
            /* style to be enqueued on all wordpress backend pages */
            wp_enqueue_style(   "{$this->db_slug}wpbk", "{$this->assets_url}backend/css/backend-wp.css", array(), current_time( "timestamp" ));
        }
        /**
         * check if woocommerce is activated
         *
         * @method  _wc_activated
         * @return  boolean true if installed and activated
         * @version 1.0.0
         * @since   1.0.0
         * @license https://pepro.dev/license Pepro.dev License
         */
        private function _wc_activated()
        {
            if (!function_exists('is_woocommerce')
                || !class_exists('woocommerce')
            ) {
                return false;
            }else{
                return true;
            }
        }
        /**
         * Send Normal SMS HOOK Container
         *
         * @method send_normal_sms_org
         * @param int $MobileNumbers
         * @param string $Messages
         * @version 1.0.0
         * @since 1.0.0
         * @return boolean status
         * @license https://pepro.dev/license Pepro.dev License
         */
        /* common functions */
        public function read_opt($mc, $def="")
        {
            return get_option($mc) <> "" ? get_option($mc) : $def;
        }
        public function plugins_row_links($links)
        {
            if (isset($links["deactivate"])){
              foreach ($this->get_manage_links() as $title => $href) { array_unshift($links, "<a href='$href' target='_self'>$title</a>"); }
              $a = new SimpleXMLElement($links["deactivate"]);
              $this->deactivateURI = "<a href='".$a['href']."'>".$this->deactivateICON.$a[0]."</a>";
              unset($links["deactivate"]);
            }
            return $links;
        }
        public function plugin_row_meta($links, $file)
        {
            if ($this->plugin_basename === $file) {
                // unset($links[1]);
                unset($links[2]);
                $icon_attr = array(
                  'style' => array(
                  'font-size: larger;',
                  'line-height: 1rem;',
                  'display: inline;',
                  'vertical-align: text-top;',
                  ),
                );
                $links[0] = $this->versionICON . $links[0];
                $links[1] = $this->authorICON . $links[1];
                if ( null !== $this->deactivateURI){
                  foreach ($this->get_meta_links() as $id => $link) {
                      $title = (!empty($link['icon'])) ? self::do_icon($link['icon'], $icon_attr) . ' ' . esc_html($link['title']) : esc_html($link['title']);
                      $links[ $id ] = '<a href="' . esc_url($link['url']) . '" title="'.esc_attr($link['description']).'" target="'.(empty($link['target'])?"_blank":$link['target']).'">' . $title . '</a>';
                  }
                  $links["deactivate"] = $this->deactivateURI;
                }
            }
            return $links;
        }
        public static function do_icon($icon, $attr = array(), $content = '')
        {
            $class = '';
            if (false === strpos($icon, '/') && 0 !== strpos($icon, 'data:') && 0 !== strpos($icon, 'http')) {
                // It's an icon class.
                $class .= ' dashicons ' . $icon;
            } else {
                // It's a Base64 encoded string or file URL.
                $class .= ' vaa-icon-image';
                $attr   = self::merge_attr(
                    $attr, array(
                    'style' => array( 'background-image: url("' . $icon . '") !important' ),
                    )
                );
            }

            if (! empty($attr['class'])) {
                $class .= ' ' . (string) $attr['class'];
            }
            $attr['class']       = $class;
            $attr['aria-hidden'] = 'true';

            $attr = self::parse_to_html_attr($attr);
            return '<span ' . $attr . '>' . $content . '</span>';
        }
        public static function parse_to_html_attr($array)
        {
            $str = '';
            if (is_array($array) && ! empty($array)) {
                foreach ($array as $attr => $value) {
                    if (is_array($value)) {
                        $value = implode(' ', $value);
                    }
                    $array[ $attr ] = esc_attr($attr) . '="' . esc_attr($value) . '"';
                }
                $str = implode(' ', $array);
            }
            return $str;
        }
    }
    /**
     * load plugin and load textdomain then set a global varibale to access plugin class!
     *
     * @version 1.0.0
     * @since   1.0.0
     * @license https://pepro.dev/license Pepro.dev License
     */
    add_action(
        "plugins_loaded", function () {
            global $WCCSS;
            load_plugin_textdomain("pepro-delivery-stages-for-woocommerce", false, dirname(plugin_basename(__FILE__))."/languages/");
            $WCCSS = new peproWoocommerceShippingStages;
            register_activation_hook(__FILE__, array("peproWoocommerceShippingStages", "activation_hook"));
            register_deactivation_hook(__FILE__, array("peproWoocommerceShippingStages", "deactivation_hook"));
            register_uninstall_hook(__FILE__, array("peproWoocommerceShippingStages", "uninstall_hook"));
        }
    );
}
/*##################################################
Lead Developer: [amirhosseinhpv](https://hpv.im/)
##################################################*/
