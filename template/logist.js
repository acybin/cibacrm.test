var append_print = true;
var matrix_status = [];

function p_notify(title_val, func, text_val)
{
      text_val = text_val || 'Вы уверены?';
      
      new PNotify({
            title: title_val,
            text: text_val,
            type: 'notice',
            before_open: function (PNotify) {
                PNotify.get().css(get_center_pos(PNotify.get().width()));
            },
            styling: 'bootstrap3',
            buttons: {
                closer: false,
                sticker: false
            },
            hide: false,
            confirm: {
                confirm: true,
                buttons: [{
                            text: 'Да', 
                            click: function(notice){
        
                               notice.remove();
                               func();
                               
                            }
                        },
                        {
                            text: 'Нет',
                            click: function(notice){
                                notice.remove();                               
                            }
                        }],
                } 
          });
}

function after_select_status_logist_id(code, answer, func_param)
{
   id_val = func_param[0];
   $t = func_param[1];
   $tr = func_param[2];
   
   ml = $tr.find("[data-field=m_list]").text();
   val = +($t.val());
   
   if ($t.find("option:selected").data("end") == 1)
   {
        $tr.addClass("dt-system"); 
        $tr.find(".fa-close").remove();
        save();
   }
 
   if (val == 5)
   {
        if (ml)
        {
           doPostAjax({op: 'logist', args: {mode: 'check_cash', addres_id: db_mas2['addres_id'], id: id_val, organization_id: $("[name=organization_id]").val()}}, function(code, answer){
         
           });
        }  
   }
   
   if (val == 11)
   {
        if (ml)
        {
            doPostAjax({op: 'logist', args: {mode: 'check_returning', addres_id: db_mas2['addres_id'], id: id_val, organization_id: $("[name=organization_id]").val()}}, function(code, answer){
            
            });
        }
   }
   
   if (val == 22)
   {
        if (ml)
        {
            doPostAjax({op: 'logist', args: {mode: 'check_podr', addres_id: db_mas2['addres_id'], id: id_val, organization_id: $("[name=organization_id]").val()}}, function(code, answer){
           
            });
        }     
   }
   
   if (val == 4)
   {
        doPostAjax({op: 'logist', args: {mode: 'reset_order', addres_id: db_mas2['addres_id'], id: id_val, organization_id: $("[name=organization_id]").val()}}, function(code, answer){
           
        });
   }
   
   doPostAjax({op: 'logist', args: {mode: 'check_courier', m_list: ml, organization_id: $("[name=organization_id]").val()}}); 
   doPostAjax({op: 'logist', args: {mode: 'check_go', id: id_val}}); 
}

function handler(ar)
{
    return function(event){ doPostAjax(ar) };
}

function refresh_people(func)
{
    func = func || "";

    status_field = 'status_' + data_obj + '_id'; 
    
    ar = {op: data_obj, args: {mode: 'filter', addres_id: db_mas2['addres_id'],
                                        start_date: $("[name=start_date]").val(), end_date: $("[name=end_date]").val()}};
                                        
    ar['args'][status_field] = db_mas2[status_field]; 
                                        
    if ($("[name=organization_id]").length > 0)
    {
       ar['args']['organization_id'] = $("[name=organization_id]").val();
    }
    
    if ($("[name=type_f]").length > 0)
    {
        ar['args']['type'] = $("[name=type_f]").val();
    }                                    
    
    doPostAjax(ar, function(code, answer){
        if (code == "success")
        { 
            app = answer;
            
            if (func == 'reload')
            {
                $(".people").html(app);
                init_select();
                
                window[func]();     
            }
            
            if (func == 'init_datatable_fix')
            {
                window[func]();
                
                append = '';
                
                if (data_obj == 'cash')
                {
                    append += '<button class="btn btn-primary" href="#" id="copy_record"><i class="fa fa-copy" title="копировать"></i> копировать</button>';
                }
                
                 append += '<button class="btn btn-primary" href="#" id="new_record">+ новая запись</button>';
                                
                $(".people").html(app);
                
                $(".tables_filter .pull-right").append(append);
            
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

function save()
{
    $td = $("td.open");
   
    if ($td.length > 0)
    { 
        $td.removeClass("open"); 
        $td.css({height: ''});
        update = true;
        
        text_val = undefined;
        delete(text_val); 
           
        if ($td.find(".select_area").length > 0)
        {
            //$td.find(".select_area select").hide();
            //$td.find(".select_area .select2").remove();
            $td.find(".select_area select").select2('destroy');
            
            $td.find("option").each(function(){
                $(this).prop("disabled", "");   
            }); 
            
            $td.find(".select_area span.s_value").show();
            $td.find(".subway").show();
            
            text_val = $td.find(".select_area select").val();
            field_val = $td.find(".select_area").data('field');
        }
        
        if ($td.find("textarea").length > 0)
        {
            $textarea = $td.find("textarea");
            
            text = $textarea.val().trim();
            
            $tr = $td.closest("tr");
        
            id_val = get_tr_id($tr, data_obj_id);
            field = $td.find(".textarea").data('field');
            
            if (field == 'order_id')
            {
                //if (text)
                //{
                    $td.find(".textarea").text(text);
                    update = false;  
                    doPostAjax({op: data_obj, args:{mode:'number',value:text,id:id_val,addres_id: db_mas2['addres_id']}}, function(code, answer) {
                                            
                        //if (code == "error")
                        //{
                            //$td.find(".textarea").text('');
                        //}
                        
                        //if (code == "success")
                        //{
                            //$td.find(".textarea").text(answer);    
                        //}
                    });     
                //}
                //else
                //{
                     //$td.find(".textarea").text('');    
                //}
            }
            else
            {
                ar  = {op:'term', args:{mode:'update', table:data_obj_table, id:id_val}};
                func = '';
                
                if (!id_val && data_obj == "partner_order")
                {
                    ar = {op:'partner_order', args:{mode:'add', table:data_obj_table, call_id: get_tr_id($tr, 'call_id')}};
                    func = 'add_new_tr';
                }
                
                ar['args'][field] = text;
                    
                if (window[func])
                    doPostAjax(ar, func, [id_val, $(this), $tr]);
                else
                    doPostAjax(ar);    
            
                $td.find(".textarea").text(text);
            }
            
            text_val = text;
            field_val = field;
        }
        
        if ($td.find(".change_date").length > 0)
        {
            $(".daterangepicker.dropdown-menu").remove();
        }
        
        if (typeof text_val !== "undefined" && update)
        {
            $tr = $td.closest("tr");
            id_val = get_tr_id($tr, data_obj_id);
            
            ar  = {op:data_obj, args:{mode:'update_record', id:id_val, field:field_val, text:text_val}};
            
            if ($("[name=organization_id]").length > 0)
            {
               ar['args']['organization_id'] = $("[name=organization_id]").val();
            }
                
            doPostAjax(ar);        
        }
        
        //if (data_obj == 'cash')
        //{
            //refresh_people('money');
        //}
    }
}

function add_new_tr(code, answer, func_param)
{
    func_param[2].data(data_obj_id, answer);
}

function formatStateStatus (state) {
  if (!state.id) {
    return state.text;
  }
  
  var $state;
  
  $state_element = $(state.element);

  bck = $state_element.data('txt_color');
  if (bck)
  {
       $state = $(
        '<span data-txt_color="'+ bck + '">' + state.text + '</span>'
      );
  }
  else
  {
     $state = $(
            '<span>' + state.text + '</span>'
          );
  }
  
  return $state;
}
  
tp_config = $.extend(true, {}, timepicker_config);
tp_config['locale']['format'] = 'DD.MM.YY H:mm';

$(function(){      
    
    $("body").on('draw.dt', "[data-table=logists]",  function (e, target) {        
        i = [4, 5, 7, 8, 12];
        c = [9, 10, 11, 12, 15, 16, 17];
        ba = [5, 7, 8];
        bw = [4];
                       
        $(this).find('tr').each(function (){
            $td = $(this).find('td');
            $th = $(this).find('th');
            
            for (j = 0; j < i.length; j++)
            {
               $td.eq(i[j] - 1).addClass('wrap');   
            }
            
            for (j = 0; j < c.length; j++)
            {
               $td.eq(c[j] - 1).addClass('center');  
               $th.eq(c[j] - 1).addClass('center');    
            }
            
            for (j = 0; j < ba.length; j++)
            {
               $td.eq(ba[j] - 1).addClass('break-all');
            }
            
            for (j = 0; j < bw.length; j++)
            {
               $td.eq(bw[j] - 1).addClass('break-word');
            }
            
        });
        
        $(this).find("[name=status_logist_id]").each(function(){
            $.data(this, 'current', $(this).val());
        });
        
        $(window).trigger("resize");
    });
    
    
    $("body").on("click", "#new_record", function(){
        
        params = {op: data_obj, args: {mode: 'add_new', addres_id: db_mas2['addres_id']}};
        
        if ($("[name=organization_id]").length > 0)
        {
           params['args']['organization_id'] = $("[name=organization_id]").val();
        }
        
        if ($("[name=type_f]").length > 0)
        {
           params['args']['type'] = $("[name=type_f]").val();
        }
                
        doPostAjax(params, function(){           
            //reload(); 
        });
        
        return false; 
    });
    
    $("body").on("click", 'td .fa-copy', function(){
        
        $tr = $(this).closest("tr");
        id_val = get_tr_id($tr, data_obj_id);
        
        ar = {op: data_obj, args: {mode: 'copy', id: id_val, addres_id: db_mas2['addres_id']}};
        
        if ($("[name=organization_id]").length > 0)
        {
           ar['args']['organization_id'] = $("[name=organization_id]").val();
        }
        
        if ($("[name=type_f]").length > 0)
        {
           ar['args']['type'] = $("[name=type_f]").val();
        }
        
        doPostAjax(ar, function(){           
            //reload(); 
        });
        
        return false; 
    });
    
    $("body").on("click", '#copy_record', function(){
        
        $tr = $("[data-table=cashs] tbody tr").eq(0);
        id_val = get_tr_id($tr, data_obj_id);
        
        ar = {op: data_obj, args: {mode: 'copy_button', id: id_val, addres_id: db_mas2['addres_id']}};
        
        if ($("[name=organization_id]").length > 0)
        {
           ar['args']['organization_id'] = $("[name=organization_id]").val();
        }
        
        if ($("[name=type_f]").length > 0)
        {
           ar['args']['type'] = $("[name=type_f]").val();
        }
        
        doPostAjax(ar, function(){           
            //reload(); 
        });
        
        return false; 
    });
    
    $("body").on("click", '.fa-plug', function(){
        
        $tr = $(this).closest("tr");
        id_val = get_tr_id($tr, data_obj_id);
        
        ar = {op: data_obj, args: {mode: 'sold', id: id_val, addres_id: db_mas2['addres_id']}};
        
        if ($("[name=organization_id]").length > 0)
        {
           ar['args']['organization_id'] = $("[name=organization_id]").val();
        }
        
        if ($("[name=type_f]").length > 0)
        {
            ar['args']['type'] = $("[name=type_f]").val();
        } 
        
        order_id = $tr.find("[data-field=order_id]").text().trim();
           
        if (order_id.length)
        {
            doPostAjax(ar);
        }
        else
        {
            p_notify('Продать без заказа', handler(ar));   
        }
        
        return false; 
    });
    
    $("body").on("click", ".fa-minus-circle", function(){
        
        $tr = $(this).closest("tr");
        id_val = get_tr_id($tr, data_obj_id);
        
        ar = {op: data_obj, args: {mode: 'minus', id: id_val, addres_id: db_mas2['addres_id']}};
        
        if ($("[name=organization_id]").length > 0)
        {
           ar['args']['organization_id'] = $("[name=organization_id]").val();
        }
        
        doPostAjax(ar, function(){           
            //reload(); 
        });
        
        return false; 
    });
    
    $("body").on("click", 'tr:not(.dt-block,.dt-system) .fa-close', function(){
        
        $t = $(this);
        p_notify('Удалить запись', function(){
             
                $tr = $t.closest("tr");
                id_val = get_tr_id($tr, data_obj_id);
                
                ar = {op: data_obj, args: {mode: 'delete', id: id_val}};
                
                if ($("[name=organization_id]").length > 0)
                {
                   ar['args']['organization_id'] = $("[name=organization_id]").val();
                }
                                            
                doPostAjax(ar, function(){           
                    //reload(); 
                });
        });
    });
    
    $("body").on("click", '.fa-arrow-right', function(){
        
        $tr = $(this).closest("tr");
        id_val = get_tr_id($tr, data_obj_id);
        
        doPostAjax({op: data_obj, args: {mode: 'go', id: id_val}}, function(code, answer){           
            if (code == "success")
            {
                path = 'card';
                encode_value = answer;
                
                form = '<form method="GET" target="_blank" action="/'+path+'/" style="display: none;" id="location"><input type="hidden" name="q" value="' + encode_value +'"></form>';
                
                $("form#location").remove();
                $("body").append(form);
                $("form#location").submit();
            }
        });
        
        return false; 
    });
    
     $("body").on("click", "td sup.log:not(.go)", function(e){
        
          $tr = $(this).closest("tr");
          $td = $(this).closest("td");
          
          id_val = get_tr_id($tr, data_obj_id);
          $div = $td.find("[data-field]");
           
          args1 = {op: 'get_log', args : {
                    table: data_obj_table, 
                    name: $div.data("field"),
                    id: id_val,
                }};
                
          doPostAjax(args1, 'getLog');
          
          e.stopPropagation();
          return false; 
     });
    
     $("body").on("click", "td sup.log.go", function(e){
        
          $tr = $(this).closest("tr");
          $td = $(this).closest("td");
          
          id_val = get_tr_id($tr, data_obj_id);
          
          if ($(this).hasClass("active"))
             v_val = 0;
          else
             v_val = 1;                          
           
          args1 = {op: 'logist', args : {
                    mode: 'm_go',
                    id: id_val,
                    value: v_val,
                }};
                
          doPostAjax(args1);
        
          e.stopPropagation();
          return false; 
    });
    
    $("body").on("click", ".datatable tr td", function(e){
        
        $tr = $(this).closest("tr");
        if ($tr.hasClass("dt-block") || $tr.hasClass("dt-system")) 
        {
            $textarea = $(this).find(".textarea");
            
            if ($textarea.length > 0)
            {
                if (!$textarea.hasClass("free"))
                {
                    e.stopPropagation();
                    return false;
                }
            }
            else
            {
                if (window[data_obj + '_tr_click']) window[data_obj + '_tr_click']($tr);
                e.stopPropagation();
                return false;
            }
        }
        
        save();
        
        pass = true;
        
        if ($(this).find(".select_area").length > 0)
        {
            if (typeof $(this).find(".select_area").data("readonly") === "undefined")
            {
                $select = $(this).find(".select_area select");
                
                if ($select.attr("name") == "status_logist_id")
                {
                    m_val = parseInt($select.val());
                    
                    if (typeof matrix_status[m_val] !== "undefined")
                    {
                        $select.find("option").each(function(){
                            if (matrix_status[m_val].indexOf(parseInt($(this).attr("value"))) == -1)
                                $(this).prop("disabled", "disabled");   
                        });
                    }
                    else
                    {
                        $select.find("option").each(function(){
                            $(this).prop("disabled", "disabled");   
                        }); 
                    }
                    
                    $select.select2({
                        minimumResultsForSearch: -1,
                        templateResult: formatStateStatus,
                        templateSelection: formatStateStatus,
                    });
                }
                else
                {
                    dynamic_fill = ["metro_id", "model_type_id", "brand_id", "model_id", "region_id", "tag", "place_id"];
                    select_name = $select.attr("name");
                    
                    if (~dynamic_fill.indexOf(select_name))
                    {
                        if (select_name == "tag")
                            mode_fill = "tag";
                        else
                            mode_fill = select_name.slice(0, -3);
                        
                        dynamic_data_obj = data_obj;
                        
                        $select.select2({
                                  ajax: {
                                        url: "/admin/",
                                        dataType: 'json',
                                        delay: 250,
                                        data: function (params) {
                                          return {              
                                            op: dynamic_data_obj,
                                            responce: true,
                                            args: {mode: mode_fill, q: params.term, page: params.page, 
                                                    organization_id: $("[name=organization_id]").val()}
                                          };
                                        },
                                        processResults: function (data, params) {
                            
                                          params.page = params.page || 1;               
                                                       
                                          return {
                                            results: data.items,
                                            pagination: {
                                              more: (params.page * 30) < data.total_count
                                            }
                                          };
                                        },
                                        cache: true
                                    },
                                  createTag: function(params) {
                                    return {
                                        id: params.term,
                                        text: params.term,
                                        isNew: true,         
                                    }
                                  },        
    
                                  templateResult: formatRepo, 
                                  templateSelection: formatRepoSelection 
                            });
                    }                    
                    else
                    {
                        $select.select2({
                            minimumResultsForSearch: -1,
                        });
                    }
                }
                
                $select.data('select2').$dropdown.addClass("ciba_excel_select");
                
                if (select_name == "tag") $select.data('select2').$dropdown.addClass("ciba_excel_tag");
                
                $(this).find(".select_area span.s_value").hide();
                $(this).find(".subway").hide();
            }    
        }
        
        if ($(this).find(".textarea").length > 0)
        {
            if (typeof $(this).find(".textarea").data("readonly") === "undefined")
            {
                text = $(this).find(".textarea").text();
                
                if ($(this).find(".textarea").hasClass("decimal"))
                {
                    if (text)
                    {
                        text = Math.abs(parseInt(text));
                    }
                }
                
                $(this).find(".textarea").html('<textarea>'+ text +'</textarea>');
                $(this).find(".textarea textarea").focus();
            }
            else
            {
                pass = false;
            }
        }
        
        if ($(this).find(".change_date").length > 0)
        {
            $t = $(this).find(".change_date");
            
            text = $(this).text();
             
            if (text)
            {    
                text_arr = text.split(' ');
                text_arr[0] = text_arr[0].split('.');
                text_tmp = '20' + text_arr[0][2] + '-' + text_arr[0][1] + '-' + text_arr[0][0] + ' ' + text_arr[1] + ':00';
                tp_config['startDate'] = moment(text_tmp);
            }
            else
            {
                tp_config['startDate'] = moment();
            }
            
            $t.daterangepicker(tp_config);
            
            $t.data('daterangepicker').toggle();
            
            $t.on('apply.daterangepicker', function(ev, picker) {
                $(this).closest("td").removeClass("open");  
                $(".daterangepicker.dropdown-menu").remove(); 
                
                $tr = $(this).closest('tr');
                id_val = get_tr_id($tr, data_obj_id);
                
                $t.text(picker.startDate.format('DD.MM.YY HH:mm'));
                d_time = picker.startDate.format('YYYY-MM-DD HH:mm:00');
                
                field_val = $(this).data("field");
                
                ar  = {op:data_obj, args:{mode:'change_date', id:id_val, date: d_time}};
                ar['args']['field'] = field_val;
                    
                doPostAjax(ar); 
                
                ar  = {op:data_obj, args:{mode:'update_record', id:id_val, field:field_val, text:d_time}};
                
                if ($("[name=organization_id]").length > 0)
                {
                   ar['args']['organization_id'] = $("[name=organization_id]").val();
                }
                
                doPostAjax(ar); 
             });
             
             $container = $t.data('daterangepicker').container;
             
             $container.on("click", function(e){
                e.stopPropagation();
             });             
             
            $t.on('cancel.daterangepicker', function(ev, picker) {
                $(this).closest("td").removeClass("open");  
                $(".daterangepicker.dropdown-menu").remove();  
                
                field_val = $(this).data("field");
                
                if (field_val == 'deadline')
                {
                    $tr = $(this).closest('tr');
                    id_val = get_tr_id($tr, data_obj_id);
                    
                    ar  = {op:data_obj, args:{mode:'change_date', id:id_val, date: '', field: field_val}};
                    doPostAjax(ar); 
                
                    $t.text('');
                    
                    ar  = {op:data_obj, args:{mode:'update_record', id:id_val, field:field_val, text:''}};
                    
                    if ($("[name=organization_id]").length > 0)
                    {
                       ar['args']['organization_id'] = $("[name=organization_id]").val();
                    }
                    
                    doPostAjax(ar);
                }
             }); 
        }
        
        if (pass)
        {
            $(this).css({height: $(this).height() + 16});
            $(this).addClass("open");
            //console.log($(this).closest('tr').find('td').eq(1).text());
        }
        
        e.stopPropagation();
        return false;
    });
    
    $("body").on("click", "#m_list_print", function(){
        
        $input = $(this).parent().find("input");
        
        if ($input.val())
        {
            doPostAjax({op: 'logist', args: {mode: 'ml', ml:$input.val(), organization_id:$("[name=organization_id]").val(), type:$("[name=type_f]").val()}}, function(code, answer){  
                if (code == "success")
                {
                    print(answer);
                }
            });
        }
        else
        {
            $input.focus();
        }
                
        return false;
    });
   
   $("body").on('click', function(){
        save();
   });
    
   $("body").on("keydown", ".decimal textarea", function (e) {
       
        //189 -
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
      
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
 
            (e.keyCode >= 35 && e.keyCode <= 40)) {

                 return;
        }

        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
    
    
    $("body").on('click', '.open .textarea, .open .select_area', function(e){
        e.stopPropagation();
        return false;
    });
   
   $("body").on("change", '.select_area select', function(){
        
        name = $(this).attr("name");
        if (name == "status_logist_id") return;
        
        $tr = $(this).closest("tr");
        
        id_val = get_tr_id($tr, data_obj_id);
        
        ar  = {op:'term', args:{mode:'update', table:data_obj_table, id:id_val}};
        func = "after_select_" + name;
        
        if (!id_val && data_obj == "partner_order")
        {
            ar = {op:'partner_order', args:{mode:'add', table:data_obj_table, call_id: get_tr_id($tr, 'call_id')}};
            func = 'add_new_tr';
        }
        
        ar['args'][name] = $(this).val();            

        if (window[func])
            doPostAjax(ar, func, [id_val, $(this), $tr]);
        else
            doPostAjax(ar);    
        
        $select_area = $(this).closest(".select_area");
        
        $span = $select_area.find("span.s_value");
        
        if ($(this).val() == "0" || $(this).val() == null)
        {
            $select_area.addClass("dt-red");       
        }
        else
        {
            $select_area.removeClass("dt-red");
        }
        
        //$span.removeClass();
        
        select_text = [];
                                        
        $(this).find("option:selected").each(function(){
            select_text.push($(this).text());  
        });
           
        $span.text(select_text.join(' ')); 
    });
    
    $("body").on("change", '.select_area select[name=status_logist_id]', function(){
        
        name = $(this).attr("name");
              
        $tr = $(this).closest("tr");
        
        id_val = get_tr_id($tr, data_obj_id);
        
        pass = true;
        
        $t = $(this);
        val = +($t.val());
         
        if ($t.find("option:selected").data("end"))
        {
           fact = $tr.find("[data-field=price_fact]").text(); 
            
           if (!fact)
           {
                new PNotify({
                    title: 'Ошибка при обновлении',
                    text: 'Введите фактическую цену!',
                    type: 'error',
                    styling: 'bootstrap3',
                    buttons: {
                            closer: true,
                            sticker: false,
                        },
                    delay: 5000, 
                 });
                 
                pass = false; 
           }
           else
           {
                pass = confirm('Вы уверены?');    
           }
        }
        else
        {
            pass = true;
        }
        
        if (!pass)
        {
             $t.val($.data(this, 'current'));
             return false;
        }
        else
        {
            ar  = {op:'term', args:{mode:'update', table:data_obj_table, id:id_val}};
            ar['args'][name] = $(this).val();
                
            func = "after_select_" + name;
            if (window[func])
                doPostAjax(ar, func, [id_val, $(this), $tr]);
            else
                doPostAjax(ar);    
            
            $select_area = $(this).closest(".select_area");
            
            $span = $select_area.find("span.s_value");
            
            if (+($(this).val()))
            {
                $select_area.removeClass("dt-red");    
            }
            else
            {
                $select_area.addClass("dt-red");        
            }
            
            //$span.removeClass();
            $span.text($(this).find("option:selected").text()); 
            
            $.data(this, 'current', $t.val());
        }
        //console.log('123');
    });
        
    $("body").on("change", ".filter-list input", function(e){          
          id = $(this).val();
          t = this;
          $context = $(this).closest(".btn-group");          

          if (id == "-1")
          {
               $context.find("[type=checkbox]").each(function(){
                    $parent = $(this).parent();
                    $(this).prop("checked", false);
                    $parent.removeClass("active");
               }); 
          }
         else
         {
            if ($(this).parent().hasClass("btn-radio"))
            {
                if ($(this).is(":checked"))
                {
                     $context.find("[type=checkbox]").not(t).each(function(){
                        $parent = $(this).parent();
                        $(this).prop("checked", false);
                        $parent.removeClass("active");
                     });
                }
            }
            else
            {
                if ($context.find(".btn-radio input:checked").length > 0)
                {
                     $context.find(".btn-radio input:checked").each(function(){
                        $parent = $(this).parent();
                        $(this).prop("checked", false);
                        $parent.removeClass("active");
                     });
                }  
            }
         }
         
         init_filter();  
         refresh_people('reload');
    });
    
    if ($("[name=matrix]").length > 0)
    {
        matrix_status = $.parseJSON($("[name=matrix]").val());
    }
    
    $("body").on("click", ".panel-heading", function(){
        if ($(this).hasClass("collapsed"))
        {
            $(this).find(".fa").addClass("fa-chevron-up");
            $(this).find(".fa").removeClass("fa-chevron-down");
        }
        else
        {
            $(this).find(".fa").addClass("fa-chevron-down");
            $(this).find(".fa").removeClass("fa-chevron-up");
        }
    });
});