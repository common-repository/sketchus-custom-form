(function($) {

	if (typeof _skfom == 'undefined' || _skfom === null) {
		_skfom = {};
	}

	$(function() {
		var welcomePanel = $('#welcome-panel');
		var updateWelcomePanel;

		updateWelcomePanel = function( visible ) {
			$.post( ajaxurl, {
				action: 'skfom-update-welcome-panel',
				visible: visible,
				welcomepanelnonce: $( '#welcomepanelnonce' ).val()
			});
		};

		$('a.welcome-panel-close', welcomePanel).click(function(event) {
			event.preventDefault();
			welcomePanel.addClass('hidden');
			updateWelcomePanel( 0 );
		});

		$('#contact-form-editor').tabs({
			active: _skfom.activeTab,
			activate: function(event, ui) {
				$('#active-tab').val(ui.newTab.index());
			}
		});

		$('#contact-form-editor-tabs').focusin(function(event) {
			$('#contact-form-editor .keyboard-interaction').css(
				'visibility', 'visible');
		}).focusout(function(event) {
			$('#contact-form-editor .keyboard-interaction').css(
				'visibility', 'hidden');
		});

		$('input:checkbox.toggle-form-table').click(function(event) {
			$(this).skfomToggleFormTable();
		}).skfomToggleFormTable();

		if ('' == $('#title').val()) {
			$('#title').focus();
		}

		$.skfomTitleHint();

		$('.contact-form-editor-box-mail span.mailtag').click(function(event) {
			var range = document.createRange();
			range.selectNodeContents(this);
			window.getSelection().addRange(range);
		});

		$(window).on('beforeunload', function(event) {
			var changed = false;

			$('#skfom-admin-form-element :input[type!="hidden"]').each(function() {
				if ($(this).is(':checkbox, :radio')) {
					if (this.defaultChecked != $(this).is(':checked')) {
						changed = true;
					}
				} else {
					if (this.defaultValue != $(this).val()) {
						changed = true;
					}
				}
			});

			if (changed) {
				event.returnValue = _skfom.saveAlert;
				return _skfom.saveAlert;
			}
		});

		$('#skfom-admin-form-element').submit(function() {
			if ('copy' != this.action.value) {
				$(window).off('beforeunload');
			}

			if ('save' == this.action.value) {
				$('#publishing-action .spinner').addClass('is-active');
			}
		});
	});

	$.fn.skfomToggleFormTable = function() {
		return this.each(function() {
			var formtable = $(this).closest('.contact-form-editor-box-mail').find('fieldset');

			if ($(this).is(':checked')) {
				formtable.removeClass('hidden');
			} else {
				formtable.addClass('hidden');
			}
		});
	};

	/**
	 * Copied from wptitlehint() in 
	 -admin/js/post.js
	 */
	$.skfomTitleHint = function() {
		var title = $('#title');
		var titleprompt = $('#title-prompt-text');

		if ('' == title.val()) {
			titleprompt.removeClass('screen-reader-text');
		}

		titleprompt.click(function() {
			$(this).addClass('screen-reader-text');
			title.focus();
		});

		title.blur(function() {
			if ('' == $(this).val()) {
				titleprompt.removeClass('screen-reader-text');
			}
		}).focus(function() {
			titleprompt.addClass('screen-reader-text');
		}).keydown(function(e) {
			titleprompt.addClass('screen-reader-text');
			$(this).unbind(e);
		});
	};
	


})(jQuery);
