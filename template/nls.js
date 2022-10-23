function refresh_people(func) {
    func = func || "";
    data_obj = $("[data-op=datatable]").data("obj");

    doPostAjax({op: 'nls', args: {mode: 'filter', obj: data_obj}}, function(code, answer) {
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
    /*$(".people select").select2MultiCheckboxes({
        placeholder: "Choose multiple elements",
        width: "auto"
    });*/
    
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
        reload();           
    });
}

function reload_tr($t)
{
    let ar = fill_mas([$("#analytic-level")]);
    
    let source_id = +($t.data('id'));       
    ar['mode'] = 'calc';
    ar['nls_source_id'] = source_id;
    
    $t.find("td").eq(0).append("<p class='loading pull-right'>загрузка...</p>");
    let $parent = $("tr[data-parent=" + source_id + "]");
    
    if (source_id == 0)
    {
       let t = [];
       $(".js-analytic tr.show_detail").each(function(){
            id = +($(this).data('id'));
            if (id) t.push(id);
       });
       
       ar['nls_source_id'] = t;
    }
    
    doPostAjax({op: 'nls', args: ar}, function(code, answer){    
        if (code == "success")
        {
            if ($parent.length > 0)
                $parent.find(".nls_answer").eq(0).html(answer);
            else
                $t.after("<tr data-parent='" + source_id + "'><td class='nls_answer'>" + answer + "</td></tr>");
            
            $open_table = $("tr[data-parent]").eq(0);
            $thead = $open_table.find("thead");
            
            if ($open_table.length > 0)
            {
               $("tr[data-parent=" + source_id + "]").find(".nls_answer thead th").each(function(i, el){
                    $(el).width($thead.find("th").eq(i).width());
               });
            }
                
            $t.find(".loading").remove();
        }
    });
}

var cb = function(start, end, label) {           
        $('.range_picker span').html(start.locale('ru').format('DD.MM.YY') + ' - ' + end.locale('ru').format('DD.MM.YY'));
    };
    
function time($td)
{
    setTimeout(function(){
        $td.removeClass("change");
    }, 1000);
}
    
function save()
{
    let $td = $("td.open");
   
    if ($td.length > 0)
    { 
        let text = $td.text().trim();
        let $tr = $td.closest("tr");
        let field_val = $td.data("field");
        
        if (text == '')
        {
            text = $td.data('save');
        }
        
        ar  = {op: 'nls', args:{mode:'expense_action', nls_source_id: $tr.find("td").eq(1).text(), day: $tr.find("td").eq(0).text(),
                                        channel: $("[name*=channel_id]:checked").attr("name"), field: field_val,
                                            original: $tr.data(field_val), value: text}};
                                        
        doPostAjax(ar, function(code, answer){
            if (code == "success")
            {
                $td.addClass("change");
                $td.text(answer); 
                time($td);
            }
        });
        
        //console.log(ar);
        
        $td.removeClass("open"); 
    }
}

$(function(){

  $("body").on("click", ".js-show-sub", function(){
        
        $tr = $(this).closest("tr");
        source_id = get_tr_id($tr, "source_id");
        
        $t = $(this);
        $outer = $(this).closest("td");
        $spec = $(".spec-organization");
        
        args_v = {};
        args_v['mode'] = 'show_sub';
        args_v['source_id'] = source_id;
        
        doPostAjax({op: 'nls', args: args_v}, function(code, answer){
            if (code == "success")
            {  
                $answer = $(answer);
                $outer.append($answer);
                $spec.remove();
            }
        });
        
        return false;
    });
    
    $("body").on("click", ".js-show-mango", function(){
        
        $tr = $(this).closest("tr");
        source_id = get_tr_id($tr, "source_id");
        
        $t = $(this);
        $outer = $(this).closest("td");
        $spec = $(".spec-organization");
        
        args_v = {};
        args_v['mode'] = 'show_mango';
        args_v['source_id'] = source_id;
        
        doPostAjax({op: 'nls', args: args_v}, function(code, answer){
            if (code == "success")
            {
                $answer = $(answer);
                $outer.append($answer);
                $spec.remove();
            }
        });
        
        return false;
    });
    
    $("body").on("click", ".spec-organization a[href*=resale_work]", function(e){
        //e.preventDefault();
        e.stopPropagation();
    });
    
    $("body").on('click', '.js-close', function(){
        $(".spec-organization").remove();
        return false; 
    }); 
    
     $("body").on('click', '.go', function(){
        
        $('.js-analytic tr[data-parent]').each(function(){
            parent = $(this).data('parent');
            reload_tr($(".js-analytic tr.show_detail[data-id=" + parent + "]"));         
        });
        
        return false; 
        
    });
    
    $("body").on('click', '.nls_answer th.sorting_desc, .nls_answer th.sorting_asc', function(e){

        if ($(this).hasClass("sorting_desc"))
            dir = 0;
        else
            dir = 1;
            
        $("[name=order_dir]").val(dir);
        
        $(".go").trigger("click");
    }); 
     
    $("body").on('click', '.nls_answer th:not(.sorting_desc, .sorting_asc)', function(){
        
        $context = $(this).closest('table');
        index = $context.find("th").index($(this));
        
        $("[name=order_column]").val(index);
        
        $(".go").trigger("click");
    });
    
    
    $("body").on('click', '#show_nls_modal', function(){
        
        filter_vals = {};
        chart_load();
        
        doPostAjax({op: 'nls', args: {mode: 'modal', dop_filter: db_mas2}}, function(code, answer){
            
            if (code == "success")
            {
                $("#popups").html(answer);
                
                $(".daterangepicker.dropdown-menu").remove();
                
                range_picker_config_auto = $.extend(true, {}, range_picker_config);
                range_picker_config_auto['startDate'] = moment($("[name=start_date]").val());
                range_picker_config_auto['endDate'] = moment($("[name=end_date]").val());
                range_picker_config_auto['timePicker'] = false;
                range_picker_config_auto['opens'] = 'left';
                    
                $(".range_picker").daterangepicker(range_picker_config_auto, cb);
                $('.range_picker .interval').html(moment($("[name=start_date]").val()).format('DD.MM.YY') + ' - ' + moment($("[name=end_date]").val()).format('DD.MM.YY'));
                
                $('#nls_modal input.flat-green').iCheck({
                    checkboxClass: 'icheckbox_flat-green',
                });
                
                $(".ciba-compact-box .js-times").hide();    
    
                $('input.flat_green').iCheck({
                    checkboxClass: 'icheckbox_flat-green',
                });
                
                $('input.flat_blue').iCheck({
                    checkboxClass: 'icheckbox_flat-blue',
                });
                
                $('input.flat_orange').iCheck({
                    checkboxClass: 'icheckbox_flat-orange',
                });
                
                reinit_checkbox();
                please_show_times();
                
                $(".ciba-filter_scrollbox").each(function(){
                    $(this).on("scroll", generate_handler_scroll($(this)));
                });
                
                $('#nls_modal').modal('show');   
            }
            
            chart_load_remove();       
        });
        
        return false; 
    });
    
    $("body").on('click', '.js-analytic tr.show_detail', function(){
        $t = $(this);
        source_id = $t.data('id');  
          
        $t.toggleClass("open");
        $parent = $("tr[data-parent=" + source_id + "]");
        
        if ($parent.length > 0)
        {
            /*if ($t.hasClass("open"))
                $parent.show();
            else
                $parent.hide();*/
                
        }
        else
        {
            reload_tr($t);
            return false;
        }
    });
    
    $("body").on('focus', 'td[contenteditable=true]', function(){
        
        save();
        $(this).addClass("open");
        $(this).data('save', $(this).text());
        $(this).text('');
        
        return false;
    });
    
    $("body").on('blur', 'td[contenteditable=true]', function(){
        save();
    });
    
    $(document).on('keydown', function(e) {
        $td = $(e.target);
        if (e.key === 'Enter' && $td.is('[contenteditable]')) {
            
            $next = $td.nextAll('[contenteditable=true]');
            $td.trigger('blur');
            
            if ($next.length > 0)
            {
                $next.trigger('focus');
            }
            else
            {
                $tr = $td.closest("tr");
                $next = $tr.next("tr").find("[contenteditable=true]");
                
                if ($next.length > 0)
                {
                    $next.eq(0).trigger("focus");
                }
            }
            
            e.preventDefault();
        }
    });
    
    
    /*$("body").on("click", ".js-plus", function(){
        
        doPostAjax({op: 'nls', args: {mode: 'expense_modal', nls_source_id: $(this).data("nls_source_id")}}, function(code, answer){  
            
            $("#popups").html(answer);
            $("#popups").find("select").select2({
                        minimumResultsForSearch: -1
                    });
            $("#popups #summ").number(true, 0, '.', ' ');
            
            date_config = $.extend(true, {}, timepicker_config);
            date_config['autoUpdateInput'] = true;
            date_config['locale']['format'] = 'DD.MM.YYYY';
            
            text = $("#popups .date").val();
            
            text_arr = text.split('.');
            text_tmp = text_arr[2] + '-' + text_arr[1] + '-' + text_arr[0];
            
            date_config['startDate'] = moment(text_tmp);
            date_config['timePicker'] = false;
            
            $("#popups .date").daterangepicker(date_config); 
        
            $("#popups .date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format('DD.MM.YYYY'));
            });
            
            $("#popups .date").on('cancel.daterangepicker', function(ev, picker) {

            });      
                                    
            $('#expense_modal').modal('show'); 
        });
        
        return false;
    });
    
    $("body").on("change", "#expense_modal .form-control", function(){
        if ($(this).val())
            $(this).addClass("passed");
         else          
            $(this).removeClass("passed");
    });
    
    $("body").on("click", "#expense_modal .btn-primary", function(){
     
         $context = $("#expense_modal");
         
         args_v = fill_mas([$context]);
         args_v['mode'] = 'expense_action';
                  
         doPostAjax({op: 'nls', args: args_v}, function(code, answer){
                
             if (code == "success")
             {
                $context.modal('hide');  
             }
        });
        
     });*/
      
});  