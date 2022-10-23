function save()
{
    $td = $("td.open");
    
    if ($td.length > 0)
    { 
        $td.removeClass("open"); 
        //$td.css({height: ''});
        
        if ($td.find(".textarea").length > 0)
        {
            $textarea = $td.find("textarea");
            text = $textarea.val().trim();    
            $td.find(".textarea .s_value").text(text); 
            
            $td.find(".c_error").show();
            
            $tr = $td.closest("tr");
            
            user_id_val = $tr.data("user_id");
            eq_val = $tr.find("td").index($td); 
            day_val = $("thead th").eq(eq_val).data("day");
            
            doPostAjax({op: 'calendar', args:{mode:'change_interval', user_id: user_id_val, day: day_val, interval: text}}, function(code, answer) {
                if (code == "success")
                {
                    $textarea = $td.find(".textarea");
                    $s_value = $textarea.find(".s_value");
                    $s_value.text(answer[0]);
                    
                    class_name = $textarea.attr("class");
                    class_name = class_name.split(' ');
                    
                    for (j = 0; j < class_name.length; j++)
                    {
                        if (class_name[j].indexOf('col-') != -1)
                        {
                            $textarea.removeClass(class_name[j]);
                        }
                    } 
                    
                    if (answer[0])
                        $textarea.removeClass("empty");
                    else
                        $textarea.addClass("empty");
                        
                    if (answer[1]) $textarea.addClass(answer[1]);
                }
            });
        }
    }    
}

function scroll_event()
{
    document.getElementById("timeline-inner")
         .addEventListener('wheel', function(event) {
           if (event.deltaMode == event.DOM_DELTA_PIXEL) {
             var modifier = 1;
             // иные режимы возможны в Firefox
           } else if (event.deltaMode == event.DOM_DELTA_LINE) {
             var modifier = parseInt(getComputedStyle(this).lineHeight);
           } else if (event.deltaMode == event.DOM_DELTA_PAGE) {
             var modifier = this.clientHeight;
           }
           if (event.deltaY != 0) {
             // замена вертикальной прокрутки горизонтальной
             this.scrollLeft += modifier * event.deltaY;
             event.preventDefault();
           }
         });
    
    $(".inner").scrollLeft(1);
    
    $(".inner").scroll(function(e){
        $elem = $(this);
        newScrollLeft = $elem.scrollLeft();
        width = $elem.width();
        scrollWidth = $elem.get(0).scrollWidth;        
        offset = 0;
        
        if (scrollWidth - newScrollLeft - width === offset) {
            
           iday_val = $(".table-calendar th").last().data("day_offset") + 1;
           
           doPostAjax({op: 'calendar', args:{mode:'show_line', iday: iday_val, group_array: $("[name=group_array]").val(), user_ids: $("[name=master_ids]").val(), 
                                addres_id: db_mas['addres_id']}}, function(code, answer) {
                if (code == "success")
                {
                    $answer = $(answer);
                    
                    $ths = $answer.find("thead th");
                    $thead = $(".table-calendar thead tr");
                     
                    $ths.each(function(key, value){
                        if (key) $thead.append(value);    
                    });                    
                                                            
                    $trs = $answer.find("tbody tr");
                    
                    $trs.each(function(eq_val, val){
                       
                       $tbody = $(".table-calendar tbody tr").eq(eq_val);
                       
                       $(this).find("td").each(function(key, value){                         
                            if (key) $tbody.append(value);
                       });
                                                                          
                    });
                    
                }
            });
        }
        
        if (newScrollLeft === 0) {
            
            iday_val = $(".table-calendar th").eq(1).data("day_offset") - 15;
            
            doPostAjax({op: 'calendar', args:{mode:'show_line', iday: iday_val, group_array: $("[name=group_array]").val(), user_ids: $("[name=master_ids]").val(), 
                            addres_id: db_mas['addres_id']}}, function(code, answer) {
                if (code == "success")
                {
                    $answer = $(answer);
                    $(".table-calendar th").eq(0).remove();
                    
                    $(".table-calendar tr").each(function(){
                         $(this).find("td").eq(0).remove(); 
                    });
                    
                    $ths = $answer.find("thead th");
                    $thead = $(".table-calendar thead tr");                     
                    $thead.prepend($ths);
                                      
                    $trs = $answer.find("tbody tr");
                    
                    $trs.each(function(eq_val, val){                       
                       $tbody = $(".table-calendar tbody tr").eq(eq_val);                                            
                       $tbody.prepend($(this).find("td"));                           
                    });
                    
                    $(".inner").scrollLeft(1);
                }
            });
        }
    });
}

$(function(){  
    
    $("body").on("click", ".calendar-nav",  function(){
        
       $context = $(this).closest(".wrapper");
       user_id_val = $context.find("[name=user_id]").val();
       
       doPostAjax({op: 'calendar', args: {mode: 'show', month: $(this).data("month"), user_id: user_id_val}}, function(code, answer){
            if (code == "success")
            { 
                $("#calendar-show").html(answer);
            }        
        });
        
        return false;
    });
    
    $("body").on("click", ".table-calendar .start-stop", function(){
        
        $tr = $(this).closest("tr");
        $td = $(this).closest("td");
        $t = $(this);
        
        user_id_val = $tr.data("user_id");
        
        if ($(this).hasClass("fa-pause"))
            start_stop_val = 0; 
        else
            start_stop_val = 1; 
        
        doPostAjax({op: 'calendar', args: {mode: 'start_stop', user_id: user_id_val, start_stop: start_stop_val}}, function(code, answer){
            if (code == "success")
            { 
               $t.remove();
               $td.append(answer);
            }        
        });
       
        return false; 
    });
    
    /*$("body").on("click", ".recrut", function(){
        
        doPostAjax({op: 'calendar', args: {mode: 'recrut_modal', group_id: $(this).data('group_id')}}, function(code, answer){            
            $("#popups").html(answer);
            $("#popups").find(".phone").mask("+7 (999) 999-99-99");  
            $('#recrut_modal').modal('show');          
        });
        
        return false;
    });
    
   $("body").on("input", "#recrut_modal input", function(){
        if ($(this).val())
            $(this).addClass("passed");
        else
            $(this).removeClass("passed");
   });
    
   $("body").on("click", "#recrut_modal .btn-primary", function(){
    
        $form = $(this).closest("form");
        
        name_val = $form.find("[name=name]");
        phone_val = $form.find("[name=phone]");
        group_id_val = $form.find("[name=group_id]");
         
        doPostAjax({op: 'calendar', args: {mode: 'recrut', name: name_val, phone: phone_val, group_id: group_id_val}}, function(code, answer){
            if (code == "success")
            {
                
            }
        });
        
        return false; 
    });*/
    
    $("body").on("click", ".table-calendar td", function(e){
        
        save();
        pass = true;
         
        if ($(this).find(".textarea").length > 0)
        {
            if (!$(this).find(".textarea").hasClass("disabled"))
            {
                text = $(this).find(".textarea").text();
                $(this).find(".s_value").html('<textarea>'+ text +'</textarea>');
                $(this).find(".c_error").hide();
                $(this).find(".textarea textarea").focus();
            }
            else
            {
                pass = false;
            }
        }

        if (pass)
        {
            $(this).addClass("open");      
            //$(this).css({height: $(this).height() + 16});
        }
        
        $(".spec-menu").remove();
        
        e.stopPropagation();
        return false; 
    });
    
    $("body").on('click', '.open .textarea', function(e){
        e.stopPropagation();     
        return false;
    });
    
    $("body").on('click', '.spec-menu', function(e){
        e.stopPropagation(); 
        return false;
    });
    
    $("body").on('click', function(){
        save();
        $(".spec-menu").remove();
    });

    if ($(".inner").length > 0) scroll_event();
    
    $("body").on("mouseover", ".textarea span.c_error", function(){
        $(this).closest("td").append('<div class="timeline-popup">' + $(this).data("text") + '</div>');
        left_val = $(this).position().left + 10;  
        top_val = $(this).position().top - 26;  
        $(this).closest("td").find(".timeline-popup").css({left: left_val, top: top_val});  
    });
    
    $("body").on("mouseout", ".textarea span.c_error", function(){
        $(this).closest("td").find(".timeline-popup").remove();  
    });
    
    $("body").on("click", ".name", function(){
       
       $(".spec-menu").remove();
       $(".name.active").removeClass('active');
       
       $(this).addClass("active");
        
       $t = $(this);
       $tr = $(this).closest("tr");
       $outer = $(".panel_content");
       user_id_val = $tr.data("user_id");
        
       doPostAjax({op: 'calendar', args: {mode: 'show_spec', user_id: user_id_val, group_array: $("[name=group_array]").val(), addres_id: db_mas['addres_id']}}, function(code, answer){
            
            if (code == "success")
            {
                $answer = $(answer);
                $outer.append($answer);
                top_val = $t.offset().top + 20 - $(window).scrollTop();
                
                if ((top_val + $answer.height()) >= $(window).height())
                    top_val = $t.offset().top - 5 - $(window).scrollTop() - $answer.height();
                    
                $answer.css({'top': top_val, 'left': $t.offset().left});                
            }
        });
        
        return false;
    });
    
   $("body").on("click", ".spec-menu li label.active", function(){
       
        $fa = $(this).find(".fa");
        
        $tr = $(".name.active").closest("tr");
        user_id_val = $tr.data("user_id");
        
        $li = $(this).closest("li");
        model_id_val = $li.data("model_type_id"); 
        
        $ar = {op: 'calendar', args: {mode: 'change_spec', user_id: user_id_val, model_type_id: model_id_val}};
        
        if ($fa.hasClass("fa-square-o"))
        {
            $fa.removeClass("fa-square-o");
            $fa.addClass("fa-check-square-o");
            doPostAjax($ar);
        }
        else
        {
            $fa.addClass("fa-square-o");
            $fa.removeClass("fa-check-square-o");
            $ar['args']['remove'] = 1;
            doPostAjax($ar);
        }
        
        return false;
   });
   
   $("body").on("blur", ".spec-menu [name=k]", function(){
        
        $tr = $(".name.active").closest("tr");
        user_id_val = $tr.data("user_id");
        $t = $(this);
        
        doPostAjax({op: 'calendar', args: {mode: 'change_k', user_id: user_id_val, k: $t.val()}}, function(code, answer){
            $t.val(answer);
        });
        
   });
   
    $("body").on("keydown", ".spec-menu [name=k]", function (e) {
       
        //189 -
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
      
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
 
            (e.keyCode >= 35 && e.keyCode <= 40)) {

                 return;
        }

        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
   
   $(window).resize(function(){
        $(".spec-menu").remove();
   });
   
   $(window).scroll(function(){
        $(".spec-menu").remove();
   });
   
});