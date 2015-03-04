// JavaScript Document
(function($){
	$.wsuwp_spn={
		int:function(){
			$(function() {
				$.wsuwp_spn.ready();
			});			
		},
		ready:function(){
			$.each($(".radio_buttons"),function(){
				$(this).buttonset();
			});
			
			

			

			$.wsuwp_spn.apply_map_additonal();
			$.wsuwp_spn.apply_map_removal();
			$.wsuwp_spn.apply_map_fallback();
			$.wsuwp_spn.apply_filter_blocks();
			$.wsuwp_spn.apply_map_filter_removal();
			$.wsuwp_spn.apply_filter_config();
			$.wsuwp_spn.int_pass_show();
			$.wsuwp_spn.apply_map_show();
			$.wsuwp_spn.meta_choice_init();
			
			$('[for*="_post_status_"]').on("click",function(){
				//console.log( ($(this).is('.ui-state-active[for*="post_status_private"]')?"block":"none") );
				$("#post_password_area").css({"display":($(this).is('.ui-state-active[for*="post_status_private"]')?"block":"none")});
			});


			$('[for*="_post_excerpt_"]').on("click",function(){
				//console.log( ($(this).is('.ui-state-active[for*="post_status_private"]')?"block":"none") );
				$(".field_block.post_excerpt").css({"display":($(this).is('.ui-state-active[for*="post_excerpt_yes"]')?"block":"none")});
			});
			
	
		},
		
		int_pass_show:function(){
			$.each($(".show_hide_pass"),function(){
				var self = $(this);
				if(self.next('.showhide_btn').length<=0){
					self.after('<button class="showhide_btn">Show</button>');
					self.data('showhide',"hidden");
				}
				var btn = self.next('.showhide_btn');
				btn.on('click',function(e){
					e.preventDefault();
					var state = self.data('showhide');
					self.attr('type',(state=="hidden"?"text":"password"));
					
					btn.text((state=="hidden"?"Hide":"Show"));
					self.data('showhide',(state=="hidden"?"":"hidden"));
				});
			});
		},

		meta_choice_init: function(){
			$('#meta_choice').on("change",function(){
				var self = $(this);
				$('.field_block.'+self.val()).fadeIn(500,function(){
					self.find(':selected').removeAttr('selected').attr('disabled',true);
					
				});
			});
		},

		apply_map_show:function(){
			$('.mapping-showhide').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var block = self.closest('.field_block');
				var area = block.find('.fields_area').eq(0);
				
				if(self.is(".open")){
					area.fadeOut(500,function(){
						self.removeClass("open");
						area.addClass("closed");
					});
				}else{
					area.fadeIn(500,function(){
						self.addClass("open");
						area.removeClass("closed");
					});
				}
			});
		},
		
		
		
		
		apply_map_additonal:function(){
			$('.mapping-add').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var block = self.closest('.field_block');
				var area = block.find('.fields_area').eq(0);
				var block_name = self.data("block_name");
				var input_name = self.data("base_input_name");
				var content = $("#mapping_template").html();
				area.hide();
				self.fadeOut(350);
				area.html( content.split("{INPUT_NAME}").join(input_name).split("{STUB_NAME}").join(block_name) );
				area.fadeIn(500);
				
				if(block.find('.filter-discription:visible').length<=0){
					block.find('.post_fill:visible .filter-discription:first,.pre_fill:visible .filter-discription:first').show();
				}

				$.wsuwp_spn.apply_map_removal();
				$.wsuwp_spn.apply_map_fallback();
				$.wsuwp_spn.apply_filter_blocks();
				$.wsuwp_spn.apply_map_filter_removal();
				block.find('.mapping-showhide').fadeIn(500,function(){ $(this).addClass("open"); area.removeClass("closed"); });
			});
		},
		apply_map_removal:function (){
			$('.mapping-removal').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var block = self.closest('.field_block');
				var area = self.closest('.field_block_area').eq(0);
				var is_fallback = area.closest('.fallbacks').length>0;
				var block_name = self.data("block_name");
				
				$.wsuwp_spn.confirmation_message("Are you sure?",{
					"yes":function(){
						area.fadeOut(350,function(){
							area.html("");
							if(is_fallback){
								area.closest('li').remove();
								var fall_block = block.find('.fallbacks').eq(0);	
								if(fall_block.find('li').length==0){
									fall_block.removeClass('active');
								}
							}
							if(block.find('.mapping-removal').length<=0){
								block.find('.mapping-add').fadeIn(500);
								block.find('.mapping-showhide').fadeOut(500,function(){ $(this).removeClass("open"); area.addClass("closed"); });
							}
						});

					},
					"no":function(){}
				});
			});
		},
		apply_map_fallback:function (){
			$('.fallback-add').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var block = self.closest('.field_block_area');
				var area = self.next('ul.fallbacks');
				var count = area.children('li').length;
				var input_name = self.data("base_input_name")+"[{##}]";
				var content = $("#mapping_template").html();
				area.addClass("active");
				
				area.append('<li>');
				var targ_li = area.find('li:last');
				targ_li.hide();
				targ_li.html(content.split("{INPUT_NAME}").join(input_name).split("{##}").join(count>0?count:0));
				//targ_li.find('.fallback-add').remove();
				targ_li.fadeIn(500);

				$.wsuwp_spn.apply_map_removal();
				$.wsuwp_spn.apply_map_fallback();
				$.wsuwp_spn.apply_filter_blocks();
			});	
		},
		apply_filter_blocks:function (){
			$('.filter-add').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var block = self.closest('.map_filter_wrapper');
				var area = block.find('ul');
				var count = area.children('li').length;
				var content = $("#filter_template").html();
				
				var block_name = self.data("block_name");
				var input_name = self.data("base_input_name");
				
				block.addClass('active');
				area.append('<li>');
				var targ_li = area.find('li:last');
				targ_li.hide();
				targ_li.html(content.split("{INPUT_NAME}").join(input_name).split("{##}").join(count>0?count:0).split("{STUB_NAME}").join(block_name));
				targ_li.fadeIn(500);
				$.wsuwp_spn.apply_map_filter_removal();
				$.wsuwp_spn.apply_filter_config();
			});
		},
		apply_map_filter_removal:function (){
			$('.filter-removal').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var area = self.closest('li');
				$.wsuwp_spn.confirmation_message("Are you sure?",{
					"yes":function(){
						area.fadeOut(350,function(){
							area.remove();
						});
					},
					"no":function(){}
				});
			});
		},
		apply_filter_config:function(){
			$.each($(".filterTypeSelector"),function(){
				var self = $(this);
				var container = self.closest('.filter_block');
				self.off().on("change",function(){
					var selected_val = self.val();
					container.find(".filteroptions").hide();
					container.find(".filteroptions input,.filteroptions select").removeAttr("required");
					container.find(".filteroptions.type_"+selected_val).show();
					container.find(".filteroptions.type_"+selected_val+" input[data-req='required'],.filteroptions.type_"+selected_val+" select[data-req='required']").attr("required",true);
				}).trigger("change");
			});
		},
		
		
		
		
		confirmation_message:function (html_message,callback){
			if($("#mess").length<=0){
				$('body').append('<div id="mess">');
			}
			$("#mess").html( (typeof html_message === 'string' || html_message instanceof String) ? html_message : html_message.html() );
			$( "#mess" ).dialog({
				autoOpen: true,
				resizable: false,
				width: 350,
				minHeight: 25,
				modal: true,
				draggable : false,
				create:function(){
					$('.ui-dialog-titlebar').remove();
					$('body').css({overflow:"hidden"});
				},
				buttons:{
					Yes:function(){
						if($.isFunction(callback.yes)){
							callback.yes();
						}
						$( this ).dialog( "close" );
					},
					No: function() {
						if($.isFunction(callback.no)){
							callback.no();
						}
						$( this ).dialog( "close" );
					}
				},
				close: function() {
					$.wsuwp_spn.close_dialog_modle($( "#mess" ));
				}
			});
		},
		close_dialog_modle: function(jObj){
			jObj.dialog( "destroy" );
			jObj.remove();
			if($(".ui-dialog.ui-widget.ui-widget-content").length<=0){
				$('body').css({overflow:"auto"});
			}
		},
		
		
		
		
		
	};
	$.wsuwp_spn.int();
})(jQuery);