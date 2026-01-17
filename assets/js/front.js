(function(){
	function hasMode(){ return !!localStorage.getItem('armo_df_mode'); }
	function setMode(m){ localStorage.setItem('armo_df_mode', m); }
	function show(){
		var data = window.ArmoDineFlow || {};
		var modal = document.getElementById('armo-df-mode-modal');
		if(!modal) return;

		document.getElementById('armo-df-modal-title').textContent = (data.strings && data.strings.title) || 'Select mode';
		var btns = modal.querySelectorAll('.armo-df-modal-btn');
		btns.forEach(function(btn){
			var mode = btn.getAttribute('data-mode');
			btn.textContent = (data.strings && data.strings[mode === 'dinein' ? 'dine_in' : mode]) || mode;
			btn.onclick = function(){
				setMode(mode);
				if(mode === 'dinein'){ window.location.href = data.joinUrl || '/dineflow/join/'; return; }
				if(mode === 'takeaway'){ window.location.href = data.takeaway || '/'; return; }
				if(mode === 'delivery'){ window.location.href = data.delivery || '/dineflow/delivery/'; return; }
			};
		});
		modal.style.display = 'block';
	}
	if(!hasMode()){
		if(document.readyState === 'loading'){ document.addEventListener('DOMContentLoaded', show); } else { show(); }
	}
})();
