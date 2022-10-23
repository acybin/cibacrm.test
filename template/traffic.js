function refresh_people(func) {
    func = func || "";
    data_obj = $("[data-op=datatable]").data("obj");

    doPostAjax({op: 'traffic', args: {mode: 'filter', obj: data_obj}}, function(code, answer) {
        if (code === "success") { 
            app = answer;

            if (func === 'reload') {
                $(".people").html(app);
                init_select();          
                window[func]();
            }

            if (func === 'init_datatable_fix') {
                window[func]();            
                $(".people").html(app);
                init_select();                
                reload();
            }
            
            $(".btn.go").insertAfter(".calendar-wrapper");
        }
    });
}

function formatState2 (state) {
      if (!state.id) {
        return state.text;
      }
      
      $state_element = $(state.element);
      
      fa = $state_element.data('not_active');
    
      if (fa)
      {
         var $state;
         
         $state = $(
            '<span class="red">' + state.text + '<fa class="fa fa-trash-o"></fa></span>'
          );
      
        return $state;
      }
      else
      {
         return state.text;       
      }
}

function init_select()
{
    $(".people select").select2({
        templateResult: formatState2,
        templateSelection: formatState2,
    });
    
    $(".people select").on("select2:select", function(e) {
         if (+$(this).val())
            $(this).addClass("passed");
         else
            $(this).removeClass("passed"); 
            
        setCookie($(this).attr("name"), $(this).val(), {expires: 3600 * 24, path: '/'});
    });
    
    $('.flats, .flats_green').iCheck({
        checkboxClass: 'icheckbox_flat-green',
    });
}

$(function(){

    $("body").off("ifChanged", "[class^=flats]:not(.no-reload)");
    
    $("body").on("ifChanged", "[class^=flats]:not(.no-reload)", function(){
         if ($(this).is(":checked"))
            v = 1;
         else
            v = 0;
         setCookie($(this).attr("name"), v, {expires: 3600 * 24, path: '/'});    
    });
    
    $("body").on('draw.dt', "[data-table=traffic]",  function (e, target) {
         chart_load_remove();
         $(window).trigger("resize");
         $(".scroll-table").doubleScroll();
    });
    
    $("body").on('preXhr.dt', "[data-table=traffic]",  function (e, target) {
         chart_load();
    });
    
    $("body").on('click', ".go", function(){
         reload();   
    });
    
    $("body").off('apply.daterangepicker', '.range_picker');
    
    $("body").on('apply.daterangepicker', '.range_picker', function(ev, picker) {
        
          cookie_start_name = 'start_date_offer';
          cookie_end_name = 'end_date_offer';
          
          $("[name=start_date]").val(picker.startDate.format('YYYY-MM-DD 00:00:00'));
          $("[name=end_date]").val(picker.endDate.format('YYYY-MM-DD 23:59:59'));
            
          setCookie(cookie_start_name, $("[name=start_date]").val(), {expires: 3600 * 24, path: '/'});
          setCookie(cookie_end_name, $("[name=end_date]").val(), {expires: 3600 * 24, path: '/'});
    });
    
    $('body').on('click', '#download-traffic', function(){
        
        $t = $(this).find("i");
        cl = $t.attr("class");         
      
        $t.attr("class", "fa fa-spinner fa-spin fa-fw fa-2x");
        
        d = {};
        init_filter();
        d.dop_filter = $.extend(true, db_mas2, fill_mas([$(".tables_filter"), true]));
        
        d.mode = 'datatable';
        d.s_mode = 2;   
        
        datatable_order = getCookie('datatable_order');
        
        col = 1;
        order = 'desc';
         
        if (typeof datatable_order !== "undefined")
        {
            datatable_order = JSON.parse(datatable_order);    
            if (typeof datatable_order[0] !== "undefined")  
            {
                if (typeof datatable_order[0][0] !== "undefined")      
                    col = datatable_order[0][0];
                    
                if (typeof datatable_order[0][1] !== "undefined")      
                    order = datatable_order[0][1];
            }
        }
        
        d.order = [];
        d.order.push({'column': col, 'dir': order});
                       
        obj = {op: 'traffic', responce: true, args: d};
         
        doPostAjax(obj, function(answer){
             if (answer)
             {
                window.location = answer;
             } 
             
             $t.attr("class", cl);         
        });
        
        return false; 
    });
    
    $("body").on("mouseenter", "[data-tooltip]", function(e){
        
        $tip = $(this).find(".tool-tip");
        
        if ($tip.length == 0)
        {
            tooltip_text = $(this).data("tooltip");
            $tooltip = $('<div class="tool-tip">' + tooltip_text + '</div>');
            
            if ($(this).data("toolup")) $tooltip.addClass("tool-up");
            if ($(this).data("tooldown")) $tooltip.addClass("tool-down");
            
            $(this).append($tooltip);
        }
    });
    
    $("body").on("mouseleave", "[data-tooltip]", function(e){
        $(this).find(".tool-tip").remove();
    });
    
});