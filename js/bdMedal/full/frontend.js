/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined) {
	XenForo.bdMedal_ShowHiddenTrigger = function($li) { this.__construct($li); };
	XenForo.bdMedal_ShowHiddenTrigger.prototype = {
		__construct: function($li) {
			this.$li = $li;
			this.$a = $li.find('a');
			this.$hidden = $($li.data('selector'));
			
			this.$li.show();
			this.$hidden.hide();
			
			this.$a.click($.context(this, 'click'));
		},
		
		click: function(e) {
			this.$li.hide();
			this.$hidden.show();
			
			e.preventDefault();
		}
	};

	XenForo.register('li.bdMedal_ShowHiddenTrigger', 'XenForo.bdMedal_ShowHiddenTrigger');

}
(jQuery, this, document);