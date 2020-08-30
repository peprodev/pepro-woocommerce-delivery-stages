<?php
defined("ABSPATH") or die("Pepro WC Delivery Stages :: Unauthorized Access!");
add_filter("woocommerce_get_settings_pages", "wccs__wc_get_settings_pages");
/**
 * make woocommerce based setting pages and sections
 *
 * @method  wccs__wc_get_settings_pages
 * @return  string
 * @access  public
 * @version 1.0.0
 * @since   1.0.0
 * @license https://pepro.dev/license Pepro.dev License
 */
function wccs__wc_get_settings_pages()
{
  if (!class_exists("Pepro_WC_ShipingStages_Settings_Page")) {
    /**
     * Hook into woocommerce setting page
     */
    class Pepro_WC_ShipingStages_Settings_Page extends WC_Settings_Page
    {
      public $td;
      public function __construct()
      {
        global $WCCSS;
        $this->td     = "ppwcss";
        $this->id     = "wc_shipping_stages";
        $this->label  = _x("Delivery Stages", "wc-setting", $this->td);
        add_action("woocommerce_settings_{$this->id}",       array( $this, 'output' ));
        add_action("woocommerce_settings_save_{$this->id}",  array( $this, 'save' ));
        add_action("woocommerce_sections_{$this->id}",       array( $this, 'output_sections' ));
        parent::__construct();
      }
      public function get_sections()
      {
        $sections = array(
          ''          =>  _x("Delivery Stages", "wc-setting", $this->td),
          'setting'   =>  _x("Design", "wc-setting", $this->td),
          'i18n'      =>  _x("Internationalization", "wc-setting", $this->td),
          'docs'     =>  _x("Documentations", "wc-setting", $this->td),
        );
        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
      }
      public function wp_enqueue_scripts()
      {
        global $WCCSS;
        // wp_enqueue_media();
        // add_thickbox();
        wp_enqueue_style( "fontawesome", "//use.fontawesome.com/releases/v5.13.1/css/all.css", array(), '5.13.1', 'all');

        wp_enqueue_style("jquery-confirm", plugins_url("/assets/backend/css/jquery-confirm.css", dirname(__FILE__)) );
        wp_enqueue_script("jquery-confirm", plugins_url("/assets/backend/js/jquery-confirm.js", dirname(__FILE__)), array("jquery"));

        wp_enqueue_script("wc-backbone-modal", WC()->plugin_url() . '/assets/js/admin/backbone-modal.js', array( 'underscore', 'backbone', 'wp-util' ), current_time( "timestamp" ) );

        wp_enqueue_style("wc_shipingstages_setting", plugins_url("/assets/backend/css/wc-setting.css", dirname(__FILE__)) );
        wp_register_script("wc_shipingstages_setting", plugins_url("/assets/backend/js/backend-core.js", dirname(__FILE__)), array("jquery","wp-color-picker"));
        wp_localize_script("wc_shipingstages_setting", "_l10n", array(
            "td"=> $this->td,
            "json_data"=> get_option("{$this->td}-data"),
            "json_data_default"=> $WCCSS->get_default_delivery_stages(),
            "ajaxurl"=> admin_url( "admin-ajax.php" ),
            "url"=> plugins_url("/assets/backend/", dirname(__FILE__)),
            "title"=> _x("Select image file", "wc-setting-js", $this->td),
            "btntext"=> _x("Use this image", "wc-setting-js", $this->td),
            "clear"=> _x("Clear", "wc-setting-js", $this->td),
            "okTxt"=> _x("Ok", "wc-setting-js", $this->td),
            "yesTxt"=> _x("Yes", "wc-setting-js", $this->td),
            "noTxt"=> _x("No", "wc-setting-js", $this->td),
            "closeTxt"=> _x("Close", "wc-setting-js", $this->td),
            "errorTxt"=> _x("Error", "wc-setting-js", $this->td),
            "successTxt"=> _x("Successfully Done", "wc-setting-js", $this->td),
            "cancelbTn"=> _x("Cancel", "wc-setting-js", $this->td),
            "sample_name"=> _x("Sample Name", "wc-setting-js", $this->td),
            "sample_description"=> _x("Sample Description", "wc-setting-js", $this->td),
            "currentlogo"=> _x("Current preview", "wc-setting-js", $this->td),
            "selectbtn"=> _x("Select image", "wc-setting-js", $this->td),
            "enabled"=> '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled">' . esc_attr__( 'Yes', $this->td ) . '</span>',
            "disabled"=> '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled">' . esc_attr__( 'No', $this->td ) . '</span>',
          )
        );
        wp_enqueue_script("wc_shipingstages_setting");

      }
      public function get_settings($current_section="")
      {
        /**
        *	For settings types, see:
        *	https://github.com/woocommerce/woocommerce/blob/fb8d959c587ee95f543e682e065192553b3cc7ec/includes/admin/class-wc-admin-settings.php#L246
        */
        switch ($current_section) {
          case "setting":
            $section_data = apply_filters( "wccs_setting_section_stages_{$current_section}", array(
                'wccs_stages_begin'   => array(
                  'name'                  => _x("Design Setting", "wc-setting", $this->td),
                  'type'                  => 'title',
                  'id'                    => 'wccs_stages',
                ),
                'wccs_stages_style'   => array(
                  'name' => _x("Current Delivery Status", "wc-setting", $this->td),
                  'type' => 'radio',
                  'default' => 'progress',
                  'options' => array(
                      "plaintext" =>  _x("Show as Plain Text", "wc-setting", $this->td),
                      "richtext" =>   _x("Show as Plain Text with Description", "wc-setting", $this->td),
                      "progress" =>   _x("Show as Horizontal Progress-bar", "wc-setting", $this->td),
                      "bars" =>       _x("Show as Horizontal Seprated-bars", "wc-setting", $this->td),
                      "images" =>     _x("Show as Horizontal Images", "wc-setting", $this->td),
                      "none" =>       _x("Don't show at all", "wc-setting", $this->td),
                  ),
                  'id'   => 'wccs_stages_style',
                ),
                'wccs_stages_esdd'    => array(
                  'name'     => _x("Estimated Delivery Date", "wc-setting", $this->td),
                  'desc'     => _x("Check to show or leave unchecked to hide", "wc-setting", $this->td),
                  'type'     => 'checkbox',
                  'id'       => 'wccs_stages_esdd',
                ),
                'wccs_stages_label'   => array(
                  'name'     => _x("Show Labels", "wc-setting", $this->td),
                  'desc'     => _x("Check to show or leave unchecked to hide", "wc-setting", $this->td),
                  'type'     => 'checkbox',
                  'id'       => 'wccs_stages_label',
                ),
                'wccs_stages_end'     => array(
                  'type'                  => 'sectionend',
                  'id'                    => 'wccs_stages',
                ),));
            break;
          case "i18n":
            $section_data = apply_filters( "wccs_setting_section_stages_{$current_section}", array(
                'wccs_stages_begin'           => array(
                  'name'                  => _x("Internationalization Setting", "wc-setting", $this->td),
                  'type'                  => 'title',
                  'id'                    => 'wccs_stages',
                ),
                'wccs_caption_delivery_date'  => array(
                  'name'     => _x("Estimated Delivery Date", "wc-setting", $this->td),
                  'desc'     => _x("Customize 'Estimated Delivery Date' label", "wc-setting", $this->td),
                  'type'     => 'text',
                  'default'  => __("Estimated Delivery Date: ",$this->td),
                  'id'       => 'wccs_caption_delivery_date',
                ),
                'wccs_caption_delivery_state' => array(
                  'name'     => _x("Current Delivery State", "wc-setting", $this->td),
                  'desc'     => _x("Customize 'Current Delivery State' label", "wc-setting", $this->td),
                  'type'     => 'text',
                  'default'  => __("Current Delivery State: ",$this->td),
                  'id'       => 'wccs_caption_delivery_state',
                ),
                'wccs_stages_end'             => array(
                  'type'                  => 'sectionend',
                  'id'                    => 'wccs_stages',
                ),));
            break;
          default:
            $section_data = apply_filters( "wccs_setting_section_stages", array() );
            break;
          }
          return apply_filters("woocommerce_get_settings_{$this->id}", $section_data, $current_section);
      }
      public function output()
      {
        global $current_section, $hide_save_button, $WCCSS;
        $WCCSS->update_footer_info();
        wp_enqueue_style("wc_shipingstages_setting", plugins_url("/assets/backend/css/wc-setting.css", dirname(__FILE__)) );

        if ( '' === $current_section ) {
          $hide_save_button = true;
          $this->wp_enqueue_scripts();
          $this->output_zones_screen();
        }elseif ('docs' == $current_section){
          $hide_save_button = true;
          ?>
          <h2><?=_x("Documentations", "wc-setting", $this->td);?></h2>
            <p style="direction: ltr;">
              <strong>Action Hook to executes on "Current Delivery Status Cleared"</strong><br>
              <?php
              highlight_string('<?php'.PHP_EOL.'add_action( "pepro-wc-delivery-status-cleared", function($order_id){ /*execute your code here*/ }, 10, 1);');
              ?>
            </p><hr>
            <p style="direction: ltr;">
              <strong>Action Hook to executes on "Current Delivery Status Changed"</strong><br>
              <?php
              highlight_string('<?php'.PHP_EOL.'add_action( "pepro-wc-delivery-status-changed", function($order_id, $prev_data, $new_data){ /*execute your code here*/ }, 10, 3);');
              ?>
            </p><hr>
            <p style="direction: ltr;">
              <strong>Action Hook to executes on "Estimated Delivery Date Cleared"</strong><br>
              <?php
              highlight_string('<?php'.PHP_EOL.'add_action( "pepro-wc-delivery-date-cleared", function($order_id){ /*execute your code here*/ }, 10, 1);');
              ?>
            </p><hr>
            <p style="direction: ltr;">
              <strong>Action Hook to executes on "Estimated Delivery Date Changed"</strong><br>
              <?php
              highlight_string('<?php'.PHP_EOL.'add_action( "pepro-wc-delivery-date-changed", function($order_id, $prev_data, $new_data){ /*execute your code here*/ }, 10, 3);');
              ?>
            </p><hr>
            <p style="direction: ltr;">
              <strong>Filter Hook to executes on "Show Delivery Details in Orders"</strong><br>
              <?php
              highlight_string('<?php'.PHP_EOL.'add_action( "pepro-wc-delivery-status-frontend-output", function($order_id, $estimated_date, $current_state, $details_style, $html_output){'.PHP_EOL.'    /*execute your code here and always return $html_output*/ '.PHP_EOL.'    return $html_output; }'.PHP_EOL.', 10, 5);');
              ?>
            </p><hr>
            <p style="direction: ltr;">
              <strong>Handful functions</strong><br>
              <?php
              highlight_string('<?php'.PHP_EOL.'global $WCCSS; $WCCSS->get_delivery_status($id); /*This function will return details of given delivery stage */');
              ?>
            </p><hr>
            <p style="direction: ltr;">
              <strong>Example Using Hooks</strong><br>
              <?php

              highlight_string('<?php'.PHP_EOL.
              'add_action( "pepro-wc-delivery-status-changed", "wc_delivery_status_changed",10,3);'.PHP_EOL.
              'function wc_delivery_status_changed ($order_id, $prev_data, $new_data){'.PHP_EOL.
              '   global $WCCSS;'.PHP_EOL.
              '   $text = sprintf("ORDER ID %s Status changed from \'%s\' <ID %s> to \'%s\' <ID %s>",'.PHP_EOL.
              '     $order_id,'.PHP_EOL.
              '     $prev_data,'.PHP_EOL.
              '     $WCCSS->get_delivery_status($prev_data)["title"],'.PHP_EOL.
              '     $new_data,'.PHP_EOL.
              '     $WCCSS->get_delivery_status($new_data)["title"],'.PHP_EOL.
              '  );'.PHP_EOL.
              '  error_log($text);'.PHP_EOL.
              '}');

              ?>
            </p>
          <?php
        }else{
          $settings = $this->get_settings($current_section);
          WC_Admin_Settings::output_fields($settings);
        }


      }
      public function output_zones_screen()
      {
        ?>
        <h2><span class="wc-shipping-zone-name"><?=$this->label;?></span></h2>
        <?php do_action( 'woocommerce_shipping_stages_before_table' ); ?>
        <table class="form-table wc-shipping-zone-settings">
          <tbody>
            <tr valign="top">
              <th scope="row" class="titledesc">
                <label><?=$this->label.wc_help_tip( __( 'The following delivery stages apply to orders with shipping.', $this->td ) );?></label></th>
              <td class="">
                <table class="wc-shipping-zone-methods wc-shipping-stages widefat">
                  <thead>
                    <tr>
                      <th class="wc-shipping-zone-method-sort"></th>
                      <th class="wc-shipping-zone-method-title"><?php esc_html_e( 'Title', $this->td ); ?></th>
                      <th class="wc-shipping-zone-method-enabled"><?php esc_html_e( 'Enabled', $this->td ); ?></th>
                      <th class="wc-shipping-zone-method-description"><?php esc_html_e( 'Description', $this->td ); ?></th>
                    </tr>
                  </thead>
                  <tfoot>
                    <tr>
                      <td colspan="4">
                        <button type="submit" class="button wc-shipping-stage-add-method" value="<?php esc_attr_e( 'Add Delivery Stage', $this->td ); ?>"><?php esc_html_e( 'Add Delivery Stage', $this->td ); ?></button>
                        <button type="submit" data-title="<?=esc_attr__("Reset All Delivery Stages?",$this->td);?>" data-content="<?=esc_attr__("Are you sure you want to reset all entries?<br>THIS WILL REMOVE ALL ENTRIES AND CANNOT BE UNDONE.",$this->td);?>"class="button wc-shipping-stage-reset-method" value="<?php esc_attr_e( 'Reset to Default Delivery Stages', $this->td ); ?>"><?php esc_html_e( 'Reset to Default Delivery Stages', $this->td ); ?></button>
                        <button type="submit" data-title="<?=esc_attr__("Delete All Delivery Stages?",$this->td);?>" data-content="<?=esc_attr__("Are you sure you want to delete all entries?<br>THIS CANNOT BE UNDONE.",$this->td);?>" class="button wc-shipping-stage-delete-all" value="<?php esc_attr_e( 'Delete all Delivery Satages', $this->td ); ?>"><?php esc_html_e( 'Delete all Delivery Satages', $this->td ); ?></button>
                      </td>
                    </tr>
                  </tfoot>
                  <tbody class="wc-shipping-zone-method-rows wc-shipping-stage-method-rows"></tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
        <?php do_action( 'woocommerce_shipping_stages_after_table' ); ?>
        <p class="submit">
        	<button type="submit" name="submit" integrity="<?=wp_create_nonce('ppwcss')?>" id="submit" class="button button-primary button-large wc-shipping-zone-method-save" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>" ><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button></p>
        <script type="text/html" id="tmpl-wc-shipping-zone-method-row-blank">
          <tr class="shipping-stage--blank">
            <td class="wc-shipping-zone-method-blank-state" colspan="4">
              <p><?php esc_html_e( 'You can add multiple delivery stages and set for orders so customers could check their delivery status.', $this->td ); ?></p>
            </td>
          </tr>
        </script>
        <script type="text/html" id="tmpl-wc-shipping-zone-method-row">
        	<tr data-id="{{ data.instance_id }}" class="data-stage-row" style="" data-enabled="{{ data.enabled }}">
        		<td width="1%" class="wc-shipping-zone-method-sort"></td>
        		<td class="wc-shipping-zone-method-title">
        			<a data-id="{{ data.instance_id }}" class="wc-shipping-zone-method-settings wc-shipping-stage-method-edit --title" href="#">{{ data.title }}</a>
        			<div class="row-actions">
        				<a data-id="{{ data.instance_id }}" class="wc-shipping-zone-method-settings wc-shipping-stage-method-edit" href="#"><?php esc_html_e( 'Edit', $this->td ); ?></a> | <a href="#" data-title="<?=sprintf(esc_attr__("Delete %s?",$this->td),"'{{ data.title }}'");?>" data-content="<?=esc_attr__("Are you sure you want to delete this entry?<br>THIS CANNOT BE UNDONE.",$this->td);?>" class="wc-shipping-zone-method-delete wc-shipping-stage-method-delete" data-id="{{ data.instance_id }}"><?php esc_html_e( 'Delete', $this->td ); ?></a>
        			</div>
        		</td>
        		<td width="1%" class="wc-shipping-zone-method-enabled"><a href="#" nofocus data-id="{{ data.instance_id }}">{{ data.enabled_icon }}</a></td>
        		<td class="wc-shipping-zone-method-description">
        			<img src="{{ data.img }}" />
        			<strong class="wc-shipping-zone-method-type">{{ data.title }}</strong>
        			<span>{{ data.method_description }}</span>
              <input type="hidden" data-id="{{ data.instance_id }}" class="data_enabled" value="{{ data.enabled }}" />
              <input type="hidden" data-id="{{ data.instance_id }}" class="data_img" value="{{ data.img }}" />
              <input type="hidden" data-id="{{ data.instance_id }}" class="data_title" value="{{ data.title }}" />
              <input type="hidden" data-id="{{ data.instance_id }}" class="data_description" value="{{ data.method_description }}" />
        		</td>
        	</tr>
        </script>
        <script type="text/template" id="tmpl-wc-modal-shipping-method-settings">
          <div class="wc-backbone-modal wc-backbone-modal-shipping-method-settings">
            <div class="wc-backbone-modal-content">
              <section class="wc-backbone-modal-main" role="main">
                <header class="wc-backbone-modal-header">
                  <h1>
                    <?php
                    printf(
                      /* translators: %s: shipping method title */
                      esc_html__( '%s Settings', $this->td ),
                      '{{{ data.method.method_title }}}'
                    );
                    ?>
                  </h1>
                  <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                    <span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', $this->td ); ?></span>
                  </button>
                </header>
                <article class="wc-modal-shipping-method-settings">
                  <form action="" method="post">
                    {{{ data.method.settings_html }}}
                    <input type="hidden" name="instance_id" value="{{{ data.instance_id }}}" />
                  </form>
                </article>
                <footer>
                  <div class="inner">
                    <button id="btn-caneledit" class="button button-large"><?php esc_html_e( 'Cancel', $this->td ); ?></button>
                    <button id="btn-savebutton" class="button button-primary button-large"><?php esc_html_e( 'Save changes', $this->td ); ?></button>
                  </div>
                </footer>
              </section>
            </div>
          </div>
          <div class="wc-backbone-modal-backdrop modal-close"></div>
        </script>
        <script type="text/template" id="tmpl-wc-modal-shipping-method-settings-edit">
          <table class="form-table">
            <tbody>
              <tr valign="top">
                <th scope="row" class="titledesc">
                  <label for="woocommerce_stage_name"><?=__("Stage Name",$this->td).wc_help_tip( __( 'Customers will see this as caption of current stage.', $this->td ) );?></label>
                </th>
                <td class="forminp">
                  <fieldset>
                    <input class="input-text regular-input " type="text" name="woocommerce_stage_name" id="woocommerce_stage_name" value="{{{ data.stage_name }}}" placeholder="<?=esc_attr__( "Enter Stage Name",$this->td );?>">
                  </fieldset>
                </td>
              </tr>
              <tr valign="top">
                <th scope="row" class="titledesc">
                  <label for="woocommerce_stage_description"><?=__("Stage Description",$this->td).wc_help_tip( __( 'Customers will see this as description of current stage.', $this->td ) );?></label>
                </th>
                <td class="forminp">
                  <fieldset>
                    <input class="input-text regular-input " type="text" name="woocommerce_stage_description" id="woocommerce_stage_description" value="{{{ data.stage_description }}}" placeholder="<?=esc_attr__( "Enter Stage Name",$this->td );?>">
                  </fieldset>
                </td>
              </tr>
              <tr valign="top">
                <th scope="row" class="titledesc">
                  <label for="woocommerce_stage_img"><?=__("Stage Icon",$this->td).wc_help_tip( __( 'Customers will see this as icon of current stage.', $this->td ) );?></label>
                </th>
                <td class="forminp">
                  <fieldset>
                    <input class="input-text regular-input " type="text" name="woocommerce_stage_img" id="woocommerce_stage_img" value="{{{ data.stage_img }}}" placeholder="<?=esc_attr__( "Enter Stage Icon URL",$this->td );?>">
                  </fieldset>
                </td>
              </tr>
              <tr valign="top">
                <th scope="row" class="titledesc">
                  <label for="woocommerce_stage_img"><?=__("Sample Icons",$this->td).wc_help_tip( __( 'Click on Icon to set as current stage icon.', $this->td ) );?></label>
                </th>
                <td class="forminp">
                  <fieldset>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_1.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_2.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_3.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_4.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_5.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_6.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_7.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_8.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_9.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_10.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_11.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_12.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_13.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_14.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_15.svg", dirname(__FILE__));?>"/>
                    <img class="img--x" nofocus src="<?=plugins_url("/assets/backend/images/wcss_16.svg", dirname(__FILE__));?>"/>
                  </fieldset>
                </td>
              </tr>
            </tbody>
          </table>
        </script>
        <script type="text/template" id="tmpl-blockUI">
          <div class="blockUI blockOverlay" style="z-index: 1000; border: medium none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; background: rgb(255, 255, 255) none repeat scroll 0% 0%; opacity: 0.6; cursor: wait; position: absolute;"></div>
        </script>
        <?php
      }
      public function save()
      {
        global $current_section;
        switch ( $current_section ) {
          case '':
            break;
          default:
            $settings = $this->get_settings($current_section);
            WC_Admin_Settings::save_fields($settings);
          break;
        }
      }
    }
    return new Pepro_WC_ShipingStages_Settings_Page();
  }
}
