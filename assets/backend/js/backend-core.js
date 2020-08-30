(function($) {
  $(document).ready(function() {
    var id = 0;
    var _pepro_ajax_request = null;

    check_for_blank_trs();
    check_for_data_import(_l10n.json_data);

    jconfirm.defaults = {
      title: '',
      titleClass: '',
      type: 'blue', // red green orange blue purple dark
      typeAnimated: true,
      draggable: true,
      dragWindowGap: 15,
      dragWindowBorder: true,
      animateFromElement: true,
      smoothContent: true,
      content: '',
      buttons: {},
      defaultButtons: {
        ok: {
          keys: ['enter'],
          text: _l10n.okTxt,
          action: function() {}
        },
        close: {
          keys: ['enter'],
          text: _l10n.closeTxt,
          action: function() {}
        },
        cancel: {
          keys: ['esc'],
          text: _l10n.cancelbTn,
          action: function() {}
        },
      },
      contentLoaded: function(data, status, xhr) {},
      icon: '',
      lazyOpen: false,
      bgOpacity: null,
      theme: 'modern',
      /*light dark supervan material bootstrap modern*/
      animation: 'scale',
      closeAnimation: 'scale',
      animationSpeed: 400,
      animationBounce: 1,
      rtl: $("body").is(".rtl") ? true : false,
      container: 'body',
      containerFluid: false,
      backgroundDismiss: false,
      backgroundDismissAnimation: 'shake',
      autoClose: false,
      closeIcon: null,
      closeIconClass: false,
      watchInterval: 100,
      columnClass: 'm',
      boxWidth: '500px',
      scrollToPreviousElement: true,
      scrollToPreviousElementAnimate: true,
      useBootstrap: false,
      offsetTop: 40,
      offsetBottom: 40,
      bootstrapClasses: {
        container: 'container',
        containerFluid: 'container-fluid',
        row: 'row',
      },
      onContentReady: function() {},
      onOpenBefore: function() {},
      onOpen: function() {},
      onClose: function() {},
      onDestroy: function() {},
      onAction: function() {},
      escapeKey: true,
    };

    /* Prioritizeable UI for Entries */
    $('.wc-shipping-stage-method-rows').sortable({
      items: 'tr',
      cursor: 'move',
      axis: 'y',
      containment: ".wc-shipping-stages",
      handle: 'td.wc-shipping-zone-method-sort',
      scrollSensitivity: 40,
      update: function(event, ui) {
        check_for_data_export();
      },
    });

    /* WooCommerce Toggle Btn */
    $(document).on("click tap", ".woocommerce-input-toggle", function(e) {
      e.preventDefault();

      var me = $(this),
        vall = "no";

      if (me.is(".woocommerce-input-toggle--enabled")) {
        me.removeClass("woocommerce-input-toggle--enabled").addClass("woocommerce-input-toggle--disabled");
      } else {
        me.removeClass("woocommerce-input-toggle--disabled").addClass("woocommerce-input-toggle--enabled");
        vall = "yes";
      }

      var instance_id = me.parent().first().data("id");

      $(`input.data_enabled[data-id=${instance_id}]`).val(vall);

      $(`tr[data-id=${instance_id}][data-enabled]`).attr("data-enabled", vall);

    });

    /* Edit Entry */
    $(document).on("click tap", ".wc-shipping-stage-method-edit", function(e) {
      e.preventDefault();
      var me = $(this);
      var instance_id = me.data("id"),
        instance_title = $(`input.data_title[data-id=${instance_id}]`).val(),
        instance_description = $(`input.data_description[data-id=${instance_id}]`).val(),
        instance_img = $(`input.data_img[data-id=${instance_id}]`).val(),
        settings_html = $("#tmpl-wc-modal-shipping-method-settings-edit").html();

      settings_html = settings_html.replace(/{{{ data.stage_name }}}/g, instance_title);
      settings_html = settings_html.replace(/{{{ data.stage_description }}}/g, instance_description);
      settings_html = settings_html.replace(/{{{ data.stage_img }}}/g, instance_img);


      $(this).WCBackboneModal({
        template: 'wc-modal-shipping-method-settings',
        variable: {
          instance_id: instance_id,
          method: {
            method_title: instance_title,
            settings_html: settings_html,
          }
        },
      });

      check_for_blank_trs();
    });

    /* Delete Entry */
    $(document).on("click tap", ".wc-shipping-stage-method-delete", function(e) {
      e.preventDefault();
      var me = $(this);
      $.confirm({
        title: me.data("title"),
        content: me.data("content"),
        buttons: {
          confirm:{
            text: _l10n.yesTxt,
            keys: ['enter'],
            action: function(){
              $(`tbody.wc-shipping-stage-method-rows tr[data-id='${me.data("id")}']`).remove();
              check_for_blank_trs();
            }
          },
          cancel: {
            text: _l10n.noTxt,
            keys: ['esc'],
            action: function () {return;}
          },
        }
      });
    });

    /* Add new Entry */
    $(document).on("click tap", ".button.wc-shipping-stage-add-method", function(e) {
      e.preventDefault();
      var me = $(this);
      id++;
      str = $('#tmpl-wc-shipping-zone-method-row').html();
      str = str.replace(/{{ data.instance_id }}/g, id);
      str = str.replace(/{{ data.enabled }}/g, "yes");
      str = str.replace(/{{ data.title }}/g, _l10n.sample_name);
      str = str.replace(/{{ data.enabled_icon }}/g, _l10n.enabled);
      str = str.replace(/{{ data.img }}/g, `${_l10n.url}images/wcss_3.svg`);
      str = str.replace(/{{ data.method_description }}/g, _l10n.sample_description);
      $("tbody.wc-shipping-zone-method-rows").append(str);
      $("tr.shipping-stage--blank").remove();
      check_for_blank_trs();
    });

    /* Reset Entries to Default */
    $(document).on("click tap", ".button.wc-shipping-stage-reset-method", function(e) {
      e.preventDefault(); var me = $(this);
      $.confirm({
        title: me.data("title"),
        content: me.data("content"),
        buttons: {
          confirm:{
            text: _l10n.yesTxt,
            keys: ['enter'],
            action: function(){
              $(`tbody.wc-shipping-stage-method-rows tr[data-id]`).remove();
              check_for_blank_trs();
              check_for_data_import(_l10n.json_data_default);
            }
          },
          cancel: {
            text: _l10n.noTxt,
            keys: ['esc'],
            action: function () {return;}
          },
        }
      });

    });

    /* Delete all Entries */
    $(document).on("click tap", ".button.wc-shipping-stage-delete-all", function(e) {
      e.preventDefault(); var me = $(this);
      $.confirm({
        title: me.data("title"),
        content: me.data("content"),
        buttons: {
          confirm:{
            text: _l10n.yesTxt,
            keys: ['enter'],
            action: function(){
              $(`tbody.wc-shipping-stage-method-rows tr[data-id]`).remove();
              check_for_blank_trs();
            }
          },
          cancel: {
            text: _l10n.noTxt,
            keys: ['esc'],
            action: function () {return;}
          },
        }
      });
    });

    /* Save Edit form */
    $(document).on("click tap", "#btn-savebutton", function(e) {
      e.preventDefault();
      var me = $(this);
      check_for_blank_trs();
      var instance_id = $("article.wc-modal-shipping-method-settings input[name=instance_id]").val();
      var instance_img = $("article.wc-modal-shipping-method-settings input#woocommerce_stage_img").val();
      var instance_title = $("article.wc-modal-shipping-method-settings input#woocommerce_stage_name").val();
      var instance_description = $("article.wc-modal-shipping-method-settings input#woocommerce_stage_description").val();

      $(`input.data_img[data-id=${instance_id}]`).val(instance_img);
      $(`input.data_title[data-id=${instance_id}]`).val(instance_title);
      $(`input.data_description[data-id=${instance_id}]`).val(instance_description);

      $(`tr[data-id=${instance_id}] .wc-shipping-zone-method-title .--title`).text(instance_title);
      $(`tr[data-id=${instance_id}] .wc-shipping-zone-method-description>strong`).text(instance_title);
      $(`tr[data-id=${instance_id}] .wc-shipping-zone-method-description>span`).text(instance_description);
      $(`tr[data-id=${instance_id}] .wc-shipping-zone-method-description>img`).attr("src", instance_img);

      $(".modal-close").click();
      check_for_data_export();
    });

    /* Cancel Edit form */
    $(document).on("click tap", "#btn-caneledit", function(e) {
      e.preventDefault();
      $(".modal-close").click();
      check_for_data_export();
    });

    /* Save whole form */
    $(document).on("click tap", "#submit", function(e) {
      e.preventDefault();
      var me = $(this);
      var template = $("#tmpl-blockUI").html();

      me.addClass("disabled").attr("disabled", "disabled").prop("disabled", true)
      $("#message").fadeOut().remove();
      $("tbody.wc-shipping-stage-method-rows").css("position", "relative").append(template);

      if (_pepro_ajax_request != null) {
        _pepro_ajax_request.abort();
      }
      _pepro_ajax_request = $.ajax({
        type: "POST",
        dataType: "json",
        url: _l10n.ajaxurl,
        data: {
          action: _l10n.td,
          nonce: me.attr("integrity"),
          wparam: "ppwcss",
          lparam: "savesettings",
          dparam: check_for_data_export(),
        },
        success: function(r) {
          if (r.success === true) {
            $.alert({ icon: 'fas fa-check-circle', boxWidth: '400px',type: "green", title: _l10n.successTxt, content: r.data.msg, });
          } else {
            $.alert({ icon: 'fas fa-exclamation-triangle', boxWidth: '400px',type: "red", title: _l10n.errorTxt, content: r.data.msg, });
            console.error(r);
          }
        },
        error: function(r) {
          $.alert({ icon: 'fas fa-exclamation-triangle', boxWidth: '400px',type: "red", title: _l10n.errorTxt, content: r.data.msg, });
          console.error(r.data);
        },
        complete: function(r) {
          $("tbody.wc-shipping-stage-method-rows").removeAttr("style");
          $("tbody.wc-shipping-stage-method-rows .blockUI.blockOverlay").remove();
          me.removeClass("disabled").removeAttr("disabled").prop("disabled", false);
        },
      });



    });

    /* Close Notice */
    $(document).on("click tap", ".notice-dismiss", function(e) {
      e.preventDefault();
      $(this).parent().fadeOut().remove();
    });

    /* Set Default Shipping Icon */
    $(document).on("click tap", ".img--x", function(e) {
      e.preventDefault();
      var me = $(this);
      var url = me.attr("src");
      $("article.wc-modal-shipping-method-settings input#woocommerce_stage_img").val(url);
    });

    function check_for_blank_trs() {
      str = $('#tmpl-wc-shipping-zone-method-row-blank').html();
      if ($("tbody.wc-shipping-stage-method-rows tr").length < 1) {
        $("tbody.wc-shipping-zone-method-rows").append(str);
      }
      check_for_data_export();
    }

    function check_for_data_import(data=null) {
      if (data) {
        if (typeof data === 'object' && data !== null) {
          $.each(data, function(index, val) {

            instance_id = val.id;
            if (id > instance_id) {
              id++;
            } else {
              id = parseInt(instance_id) + 1;
            }
            img = val.icon;
            title = val.title;
            enabled = val.enabled;
            enabled_icon = enabled == "yes" ? _l10n.enabled : _l10n.disabled;
            method_description = val.description;

            str = $('#tmpl-wc-shipping-zone-method-row').html();
            str = str.replace(/{{ data.instance_id }}/g, instance_id);
            str = str.replace(/{{ data.enabled }}/g, enabled);
            str = str.replace(/{{ data.title }}/g, title);
            str = str.replace(/{{ data.enabled_icon }}/g, enabled_icon);
            str = str.replace(/{{ data.img }}/g, img);
            str = str.replace(/{{ data.method_description }}/g, method_description);
            $("tbody.wc-shipping-zone-method-rows").append(str);
            $("tr.shipping-stage--blank").remove();
          });
        }else{
          check_for_data_import(_l10n.json_data_default);
        }
      }
      check_for_blank_trs();
      check_for_data_export();
    }

    function check_for_data_export() {
      var $_data = [];
      $("tbody.wc-shipping-stage-method-rows>tr[data-enabled]").each(function(index, val) {
        var instance_id = $(val).attr("data-id");
        var instance_title = $(`input.data_title[data-id=${instance_id}]`).val();
        var instance_description = $(`input.data_description[data-id=${instance_id}]`).val();
        var instance_img = $(`input.data_img[data-id=${instance_id}]`).val();
        var instance_enabled = $(`input.data_enabled[data-id=${instance_id}]`).val();
        $_data.push({
          id: instance_id,
          icon: instance_img,
          title: instance_title,
          enabled: instance_enabled,
          description: instance_description,
        });
      });
      return ($_data === [] ? 0 : $_data);
    }

  });
})(jQuery);
