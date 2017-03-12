/* ConTroll Wordpress Plugin utility scripts */

(function(win,doc){ // scope to not leak strict mode
	'use strict';
	var exports = win;
	
	var popupGroups = {};
	
	function elementVisible(el) {
		var style = win.getComputedStyle(el);
		return !(style.display === 'none');
	}
	
	function hideElement(el) {
		el.style.display = 'none';
	}
	
	function showElement(el) {
		el.style.display = 'unset';
	}
	
	var revalidateControl = function(event) {
		if (event.currentTarget && event.currentTarget.setCustomValidity) {
			event.currentTarget.setCustomValidity('');
			event.currentTarget.removeEventListener('keyup', revalidateControl);
		}
	};
	
	exports.toggle_popup_box = function(boxSelector) {
		var box = doc.querySelector(boxSelector);
		if (!box)
			return;
		if (elementVisible(box)) {
			hideElement(box);
		} else {
			showElement(box);
		}
	};
	
	exports.toggle_popup_in_group = function(groupName, boxSelector) {
		var box = doc.querySelector(boxSelector);
		if (!box)
			return;
		if (popupGroups[groupName]) {
			hideElement(popupGroups[groupName]);
			delete popupGroups[groupName];
		}
		if (elementVisible(box)) {
			hideElement(box);
		} else {
			showElement(box);
			popupGroups[groupName] = box;
		}
	};
	
	exports.registration_submit_callback = function(event) {
		var form = event.currentTarget;
		if (form['pass'].value == 'new' && (!form['pass-name'].value)) {
			if (form['pass-name'].setCustomValidity) {
				form['pass-name'].setCustomValidity(form['pass-name'].getAttribute('error-text') || 'Pass owner name is a required field');
				form['pass-name'].addEventListener('keyup', revalidateControl, false);
			}
			event.preventDefault();
			return false;
		}
		return true;
	};
})(window,document);