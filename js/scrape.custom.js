// JavaScript Document
(function($){
	$(function() {
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
		
		
		
		
	});
})(jQuery);