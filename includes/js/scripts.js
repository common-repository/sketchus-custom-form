(function($) {

	if (typeof _skfom == 'undefined' || _skfom === null) {
		_skfom = {};
	}

	_skfom = $.extend({
		cached: 0
	}, _skfom);

	$.fn.skfomInitForm = function() {
		this.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				$form.skfomClearResponseOutput();
				$form.find('[aria-invalid]').attr('aria-invalid', 'false');
				$form.find('img.ajax-loader').css({ visibility: 'visible' });
				return true;
			},
			beforeSerialize: function($form, options) {
				$form.find('[placeholder].placeheld').each(function(i, n) {
					$(n).val('');
				});
				return true;
			},
			data: { '_skfom_is_ajax_call': 1 },
			dataType: 'json',
			success: $.skfomAjaxSuccess,
			error: function(xhr, status, error, $form) {
				var e = $('<div class="ajax-error"></div>').text(error.message);
				$form.after(e);
			}
		});

		if (_skfom.cached) {
			this.skfomOnloadRefill();
		}

		this.skfomToggleSubmit();

		this.find('.skfom-submit').skfomAjaxLoader();

		this.find('.skfom-acceptance').click(function() {
			$(this).closest('form').skfomToggleSubmit();
		});

		this.find('.skfom-exclusive-checkbox').skfomExclusiveCheckbox();

		this.find('.skfom-list-item.has-free-text').skfomToggleCheckboxFreetext();

		this.find('[placeholder]').skfomPlaceholder();

		if (_skfom.jqueryUi && ! _skfom.supportHtml5.date) {
			this.find('input.skfom-date[type="date"]').each(function() {
				$(this).datepicker({
					dateFormat: 'yy-mm-dd',
					minDate: new Date($(this).attr('min')),
					maxDate: new Date($(this).attr('max'))
				});
			});
		}

		if (_skfom.jqueryUi && ! _skfom.supportHtml5.number) {
			this.find('input.skfom-number[type="number"]').each(function() {
				$(this).spinner({
					min: $(this).attr('min'),
					max: $(this).attr('max'),
					step: $(this).attr('step')
				});
			});
		}

		this.find('.skfom-character-count').skfomCharacterCount();

		this.find('.skfom-validates-as-url').change(function() {
			$(this).skfomNormalizeUrl();
		});
	};

	$.skfomAjaxSuccess = function(data, status, xhr, $form) {
		if (! $.isPlainObject(data) || $.isEmptyObject(data)) {
			return;
		}

		var $responseOutput = $form.find('div.skfom-response-output');

		$form.skfomClearResponseOutput();

		$form.find('.skfom-form-control').removeClass('skfom-not-valid');
		$form.removeClass('invalid spam sent failed');

		if (data.captcha) {
			$form.skfomRefillCaptcha(data.captcha);
		}

		if (data.quiz) {
			$form.skfomRefillQuiz(data.quiz);
		}

		if (data.invalids) {
			$.each(data.invalids, function(i, n) {
				$form.find(n.into).skfomNotValidTip(n.message);
				$form.find(n.into).find('.skfom-form-control').addClass('skfom-not-valid');
				$form.find(n.into).find('[aria-invalid]').attr('aria-invalid', 'true');
			});

			$responseOutput.addClass('skfom-validation-errors');
			$form.addClass('invalid');

			$(data.into).trigger('skfom:invalid');
			$(data.into).trigger('invalid.skfom'); // deprecated

		} else if (1 == data.spam) {
			$form.find('[name="g-recaptcha-response"]').each(function() {
				if ('' == $(this).val()) {
					var $recaptcha = $(this).closest('.skfom-form-control-wrap');
					$recaptcha.skfomNotValidTip(_skfom.recaptchaEmpty);
				}
			});

			$responseOutput.addClass('skfom-spam-blocked');
			$form.addClass('spam');

			$(data.into).trigger('skfom:spam');
			$(data.into).trigger('spam.skfom'); // deprecated

		} else if (1 == data.mailSent) {
			$responseOutput.addClass('skfom-mail-sent-ok');
			$form.addClass('sent');

			if (data.onSentOk) {
				$.each(data.onSentOk, function(i, n) { eval(n) });
			}

			$(data.into).trigger('skfom:mailsent');
			$(data.into).trigger('mailsent.skfom'); // deprecated

		} else {
			$responseOutput.addClass('skfom-mail-sent-ng');
			$form.addClass('failed');

			$(data.into).trigger('skfom:mailfailed');
			$(data.into).trigger('mailfailed.skfom'); // deprecated
		}

		if (data.onSubmit) {
			$.each(data.onSubmit, function(i, n) { eval(n) });
		}

		$(data.into).trigger('skfom:submit');
		$(data.into).trigger('submit.skfom'); // deprecated

		if (1 == data.mailSent) {
			$form.resetForm();
		}

		$form.find('[placeholder].placeheld').each(function(i, n) {
			$(n).val($(n).attr('placeholder'));
		});

		$responseOutput.append(data.message).slideDown('fast');
		$responseOutput.attr('role', 'alert');

		$.skfomUpdateScreenReaderResponse($form, data);
	};

	$.fn.skfomExclusiveCheckbox = function() {
		return this.find('input:checkbox').click(function() {
			var name = $(this).attr('name');
			$(this).closest('form').find('input:checkbox[name="' + name + '"]').not(this).prop('checked', false);
		});
	};

	$.fn.skfomPlaceholder = function() {
		if (_skfom.supportHtml5.placeholder) {
			return this;
		}

		return this.each(function() {
			$(this).val($(this).attr('placeholder'));
			$(this).addClass('placeheld');

			$(this).focus(function() {
				if ($(this).hasClass('placeheld'))
					$(this).val('').removeClass('placeheld');
			});

			$(this).blur(function() {
				if ('' == $(this).val()) {
					$(this).val($(this).attr('placeholder'));
					$(this).addClass('placeheld');
				}
			});
		});
	};

	$.fn.skfomAjaxLoader = function() {
		return this.each(function() {
			var loader = $('<img class="ajax-loader" />')
				.attr({ src: _skfom.loaderUrl, alt: _skfom.sending })
				.css('visibility', 'hidden');

			$(this).after(loader);
		});
	};

	$.fn.skfomToggleSubmit = function() {
		return this.each(function() {
			var form = $(this);

			if (this.tagName.toLowerCase() != 'form') {
				form = $(this).find('form').first();
			}

			if (form.hasClass('skfom-acceptance-as-validation')) {
				return;
			}

			var submit = form.find('input:submit');
			if (! submit.length) return;

			var acceptances = form.find('input:checkbox.skfom-acceptance');
			if (! acceptances.length) return;

			submit.removeAttr('disabled');
			acceptances.each(function(i, n) {
				n = $(n);
				if (n.hasClass('skfom-invert') && n.is(':checked')
				|| ! n.hasClass('skfom-invert') && ! n.is(':checked')) {
					submit.attr('disabled', 'disabled');
				}
			});
		});
	};

	$.fn.skfomToggleCheckboxFreetext = function() {
		return this.each(function() {
			var $wrap = $(this).closest('.skfom-form-control');

			if ($(this).find(':checkbox, :radio').is(':checked')) {
				$(this).find(':input.skfom-free-text').prop('disabled', false);
			} else {
				$(this).find(':input.skfom-free-text').prop('disabled', true);
			}

			$wrap.find(':checkbox, :radio').change(function() {
				var $cb = $('.has-free-text', $wrap).find(':checkbox, :radio');
				var $freetext = $(':input.skfom-free-text', $wrap);

				if ($cb.is(':checked')) {
					$freetext.prop('disabled', false).focus();
				} else {
					$freetext.prop('disabled', true);
				}
			});
		});
	};

	$.fn.skfomCharacterCount = function() {
		return this.each(function() {
			var $count = $(this);
			var name = $count.attr('data-target-name');
			var down = $count.hasClass('down');
			var starting = parseInt($count.attr('data-starting-value'), 10);
			var maximum = parseInt($count.attr('data-maximum-value'), 10);
			var minimum = parseInt($count.attr('data-minimum-value'), 10);

			var updateCount = function($target) {
				var length = $target.val().length;
				var count = down ? starting - length : length;
				$count.attr('data-current-value', count);
				$count.text(count);

				if (maximum && maximum < length) {
					$count.addClass('too-long');
				} else {
					$count.removeClass('too-long');
				}

				if (minimum && length < minimum) {
					$count.addClass('too-short');
				} else {
					$count.removeClass('too-short');
				}
			};

			$count.closest('form').find(':input[name="' + name + '"]').each(function() {
				updateCount($(this));

				$(this).keyup(function() {
					updateCount($(this));
				});
			});
		});
	};

	$.fn.skfomNormalizeUrl = function() {
		return this.each(function() {
			var val = $.trim($(this).val());

			if (val && ! val.match(/^[a-z][a-z0-9.+-]*:/i)) { // check the scheme part
				val = val.replace(/^\/+/, '');
				val = 'http://' + val;
			}

			$(this).val(val);
		});
	};

	$.fn.skfomNotValidTip = function(message) {
		return this.each(function() {
			var $into = $(this);

			$into.find('span.skfom-not-valid-tip').remove();
			$into.append('<span role="alert" class="skfom-not-valid-tip">' + message + '</span>');

			if ($into.is('.use-floating-validation-tip *')) {
				$('.skfom-not-valid-tip', $into).mouseover(function() {
					$(this).skfomFadeOut();
				});

				$(':input', $into).focus(function() {
					$('.skfom-not-valid-tip', $into).not(':hidden').skfomFadeOut();
				});
			}
		});
	};

	$.fn.skfomFadeOut = function() {
		return this.each(function() {
			$(this).animate({
				opacity: 0
			}, 'fast', function() {
				$(this).css({'z-index': -100});
			});
		});
	};

	$.fn.skfomOnloadRefill = function() {
		return this.each(function() {
			var url = $(this).attr('action');

			if (0 < url.indexOf('#')) {
				url = url.substr(0, url.indexOf('#'));
			}

			var id = $(this).find('input[name="_skfom"]').val();
			var unitTag = $(this).find('input[name="_skfom_unit_tag"]').val();

			$.getJSON(url,
				{ _skfom_is_ajax_call: 1, _skfom: id, _skfom_request_ver: $.now() },
				function(data) {
					if (data && data.captcha) {
						$('#' + unitTag).skfomRefillCaptcha(data.captcha);
					}

					if (data && data.quiz) {
						$('#' + unitTag).skfomRefillQuiz(data.quiz);
					}
				}
			);
		});
	};

	$.fn.skfomRefillCaptcha = function(captcha) {
		return this.each(function() {
			var form = $(this);

			$.each(captcha, function(i, n) {
				form.find(':input[name="' + i + '"]').clearFields();
				form.find('img.skfom-captcha-' + i).attr('src', n);
				var match = /([0-9]+)\.(png|gif|jpeg)$/.exec(n);
				form.find('input:hidden[name="_skfom_captcha_challenge_' + i + '"]').attr('value', match[1]);
			});
		});
	};

	$.fn.skfomRefillQuiz = function(quiz) {
		return this.each(function() {
			var form = $(this);

			$.each(quiz, function(i, n) {
				form.find(':input[name="' + i + '"]').clearFields();
				form.find(':input[name="' + i + '"]').siblings('span.skfom-quiz-label').text(n[0]);
				form.find('input:hidden[name="_skfom_quiz_answer_' + i + '"]').attr('value', n[1]);
			});
		});
	};

	$.fn.skfomClearResponseOutput = function() {
		return this.each(function() {
			$(this).find('div.skfom-response-output').hide().empty().removeClass('skfom-mail-sent-ok skfom-mail-sent-ng skfom-validation-errors skfom-spam-blocked').removeAttr('role');
			$(this).find('span.skfom-not-valid-tip').remove();
			$(this).find('img.ajax-loader').css({ visibility: 'hidden' });
		});
	};

	$.skfomUpdateScreenReaderResponse = function($form, data) {
		$('.skfom .screen-reader-response').html('').attr('role', '');

		if (data.message) {
			var $response = $form.siblings('.screen-reader-response').first();
			$response.append(data.message);

			if (data.invalids) {
				var $invalids = $('<ul></ul>');

				$.each(data.invalids, function(i, n) {
					if (n.idref) {
						var $li = $('<li></li>').append($('<a></a>').attr('href', '#' + n.idref).append(n.message));
					} else {
						var $li = $('<li></li>').append(n.message);
					}

					$invalids.append($li);
				});

				$response.append($invalids);
			}

			$response.attr('role', 'alert').focus();
		}
	};

	$.skfomSupportHtml5 = function() {
		var features = {};
		var input = document.createElement('input');

		features.placeholder = 'placeholder' in input;

		var inputTypes = ['email', 'url', 'tel', 'number', 'range', 'date'];

		$.each(inputTypes, function(index, value) {
			input.setAttribute('type', value);
			features[value] = input.type !== 'text';
		});

		return features;
	};

	$(function() {
		_skfom.supportHtml5 = $.skfomSupportHtml5();
		$('div.skfom > form').skfomInitForm();
	});

})(jQuery);
