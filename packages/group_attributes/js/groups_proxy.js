(function($){

	/**
	 * takes our url like /dashboard/users/groups?task=edit&gID=3 and transforms it to groups/edit/3
	 */
	function get_new_url (url) {
		var vars = {}, hash, hashes, base_url, marker = url.indexOf('?');

		base_url = url.slice(0, marker);

		hashes = url.slice(marker + 1).split('&');
		for (var i = 0; i < hashes.length; i++){
			hash = hashes[i].split('=');
			vars[hash[0]] = hash[1];
		}

		return [base_url, vars.task, vars.gID].join('/');
	};


	$(function(){
		$('a.ccm-group-inner').each(function(){
			this.href = get_new_url(this.href);
		});
	});

})(jQuery);