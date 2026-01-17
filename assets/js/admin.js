(function($){
	$(function(){
		$('.armo-df-color').wpColorPicker();
	});
})(jQuery);

jQuery(function($){
	$(document).on('click','.armo-df-copy',function(){
		var txt = $(this).data('copy');
		if(!txt) return;
		navigator.clipboard.writeText(txt).then(()=>{
			var $b=$(this); var old=$b.text();
			$b.text('Copiado');
			setTimeout(()=>{$b.text(old);},1200);
		});
	});
});
