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
			
			$.each($(".filterTypeSelector"),function(){
				var self = $(this);
				var container = self.closest('.filter_block');
				self.on("change",function(){
					var selected_val = self.val();
					container.find(".filteroptions").hide();
					container.find(".filteroptions input,.filteroptions select").removeAttr("required");
					container.find(".filteroptions.type_"+selected_val).show();
					container.find(".filteroptions.type_"+selected_val+" input[data-req='required'],.filteroptions.type_"+selected_val+" select[data-req='required']").attr("required",true);
				}).trigger("change");
			});
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
				
				$.wsuwp_spn.apply_map_removal();
				$.wsuwp_spn.apply_map_fallback();
				$.wsuwp_spn.apply_filter_blocks();
	
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
				area.fadeOut(350,function(){
					area.html("");
					if(is_fallback){
						area.closest('li').remove();
						var fall_block = block.find('.fallbacks').eq(0);	
						if(fall_block.find('li').length==0){
							fall_block.removeClass('active');
						}
					}
				});
				
				block.find('.mapping-add').fadeIn(500);
			});
		},
		apply_map_fallback:function (){
			$('.fallback-add').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var block = self.closest('.field_block_area');
				var area = self.next('ul.fallbacks');
				var count = area.find('li').length;
				var input_name = self.data("base_input_name");
				var content = $("#mapping_template").html();
				area.addClass("active");
				
				area.append('<li>');
				var targ_li = area.find('li:last');
				targ_li.hide();
				targ_li.html(content.split("{##}").join(count).split("{INPUT_NAME}").join(input_name));
				//targ_li.find('.fallback-add').remove();
				targ_li.fadeIn(500);
				
				
				$.wsuwp_spn.apply_map_removal();
				$.wsuwp_spn.apply_map_fallback();
			});	
		},
		apply_filter_blocks:function (){
			$('.filter-add').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var block = self.closest('.map_filter_wrapper');
				var area = block.find('ul');
				var count = area.find('li').length;
				var content = $("#filter_template").html();
				
				var block_name = self.data("block_name");
				var input_name = self.data("base_input_name");
				
				block.addClass('active');
				area.append('<li>');
				var targ_li = area.find('li:last');
				targ_li.hide();
				targ_li.html(content.split("{##}").join(count).split("{INPUT_NAME}").join(input_name).split("{STUB_NAME}").join(block_name));
				targ_li.fadeIn(500);
				$.wsuwp_spn.apply_map_filter_removal();
			});
		},
		apply_map_filter_removal:function (){
			$('.filter-removal').off().on('click',function(e){
				e.preventDefault();
				var self = $(this);
				var area = self.closest('li');
				area.fadeOut(350,function(){
					area.remove();
				});
			});
		},
	};
	$.wsuwp_spn.int();
})(jQuery);