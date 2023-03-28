jQuery(document).ready(function($) {
	// Initiate Color Picker
	$('.wp-color-picker-field').wpColorPicker();

	// Switches option sections
	$('.group').hide();
	var activetab = '';
	if (typeof(localStorage) != 'undefined') {
		activetab = localStorage.getItem("activetab");
	}

	// If url has section id as hash then set it as active or override the current local storage value
	if (window.location.hash) {
		activetab = window.location.hash;
		if (typeof(localStorage) != 'undefined') {
			localStorage.setItem("activetab", activetab);
		}
	}

	if (activetab != '' && $(activetab).length) {
		$(activetab).fadeIn();
	} else {
		$('.group:first').fadeIn();
	}

	$('.group .collapsed').each(function() {
		$(this).find('input:checked').parent().parent().parent().nextAll().each(
			function() {
				if ($(this).hasClass('last')) {
					$(this).removeClass('hidden');
					return false;
				}
				$(this).filter('.hidden').removeClass('hidden');
			});
	});

	if (activetab != '' && $(activetab + '-tab').length) {
		$(activetab + '-tab').addClass('nav-tab-active');
	} else {
		$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
	}

	// Handle section tabs
	$('.nav-tab-wrapper a').click(function(evt) {
		$('.nav-tab-wrapper a').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active').blur();
		var clicked_group = $(this).attr('href');
		if (typeof(localStorage) != 'undefined') {
			localStorage.setItem("activetab", $(this).attr('href'));
		}
		$('.group').hide();
		$(clicked_group).fadeIn();
		evt.preventDefault();
	});

	// Handle file upload browsing
	$('.wk-options-file-browse').on('click', function(event) {
		event.preventDefault();

		var self = $(this);

		// Create the media frame.
		var file_frame = wp.media.frames.file_frame = wp.media({
			title: self.data('uploader_title'),
			button: {
				text: self.data('uploader_button_text'),
			},
			multiple: false
		});

		file_frame.on('select', function() {
			attachment = file_frame.state().get('selection').first().toJSON();
			self.prev('.wk-options-file-url').val(attachment.url).change();
		});

		// Finally, open the modal
		file_frame.open();
	});

	// Function to decide whether to show or hide the options field based on conditions
	const handleShowOn = (input, element, showConditions) => {
		let isVisible = true;
		let onlyDesc = true;

		showConditions.forEach(condition => {
			const [key, compare, value, onlyDescSub] = condition.split(':');
			onlyDesc = onlyDescSub == '1';
			let currentInput = null;

			if(compare === 'checked') {
				currentInput = document.querySelector(`[name*="${key}"][value="${value}"]`);
			} else {
				currentInput = document.querySelector(`[name*="${key}"]`);
			}

			if (compare === '==') {
				if (currentInput.value !== value) {
					isVisible = false;
				}
			} else if(compare === 'checked') {
				if (currentInput.checked === false) {
					isVisible = false;
				}
			} else {
				if (currentInput.value === value) {
					isVisible = false;
				}
			}
		});

		let elementToHide = element;
		if (onlyDesc === false) {
			elementToHide = element.closest('tr');
		}

		if (isVisible) {
			elementToHide.classList.remove('hidden');
			element.querySelectorAll('.is-required').forEach(el => {
				el.setAttribute('required', true);
			});
		} else {
			elementToHide.classList.add('hidden');
			element.querySelectorAll('.is-required').forEach(el => {
				el.removeAttribute('required');
			});
		}
	};

	// Handle show_on mechanism
	const showOnElements = document.querySelectorAll('[data-wk-show-on]');
	if (showOnElements.length) {
		showOnElements.forEach(element => {
			const {
				wkShowOn
			} = element.dataset;

			// Split with | to get multiple values
			const showConditions = wkShowOn.split('|');

			// Loop if not empty
			if (showConditions.length) {
				showConditions.forEach(condition => {
					const [key, compare, value] = condition.split(':');
				
					let input = null;

					if(compare === 'checked') {
						input = document.querySelector(`[name*="${key}"][value="${value}"]`);
					} else {
						input = document.querySelector(`[name*="${key}"]`);
					}

					if (!!input) {
						if (input.type === 'radio') {
							input.addEventListener('change', e => {
								handleShowOn(e.target, element, showConditions);
							});
						} else if (input.type === 'checkbox') {
							input.addEventListener('change', e => {
								handleShowOn(e.target, element, showConditions);
							});
						} else {
							input.addEventListener('input', e => {
								handleShowOn(e.target, element, showConditions);
							});
						}
						
						handleShowOn(input, element, showConditions);
					}
				});
			}
		});
	}

	// Function to decide whether to disable the options field based on conditions or not
	const handleDisabledOn = (input, element, disabledConditions) => {
		let isDisabled = true;

		disabledConditions.forEach(condition => {
			const [key, compare, value] = condition.split(':');
			let currentInput = null;

			if(compare === 'checked') {
				currentInput = document.querySelector(`[name*="${key}"][value="${value}"]`);
			} else {
				currentInput = document.querySelector(`[name*="${key}"]`);
			}

			if (compare === '==') {
				if (currentInput.value !== value) {
					isDisabled = false;
				}
			} else if(compare === 'checked') {
				if (currentInput.checked === false) {
					isDisabled = false;
				}
			} else {
				if (currentInput.value === value){
					isDisabled = false;
				}
			}
		});

		const elements = element.querySelectorAll('input, select, textarea');
		elements.forEach(element => {
			if(isDisabled === true) {
				element.setAttribute('disabled', true);
				// Uncheck if checked
				if (element.type === 'checkbox') {
					element.checked = false;
				}
			} else {
				element.removeAttribute('disabled');
			}
		});
	};

	// Handle disabled_on mechanism
	const disabledOnElements = document.querySelectorAll('[data-wk-disabled-on]');
	if (disabledOnElements.length) {
		disabledOnElements.forEach(element => {
			const {
				wkDisabledOn
			} = element.dataset;

			// Split with | to get multiple values
			const disabledConditions = wkDisabledOn.split('|');

			// Loop if not empty
			if (disabledConditions.length) {
				disabledConditions.forEach(condition => {
					const [key, compare, value] = condition.split(':');
				
					let input = null;

					if(compare === 'checked') {
						input = document.querySelector(`[name*="${key}"][value="${value}"]`);
					} else {
						input = document.querySelector(`[name*="${key}"]`);
					}

					if (!!input) {
						if (input.type === 'radio') {
							input.addEventListener('change', e => {
								handleDisabledOn(e.target, element, disabledConditions);
							});
						} else if (input.type === 'checkbox') {
							input.addEventListener('change', e => {
								handleDisabledOn(e.target, element, disabledConditions);
							});
						} else {
							input.addEventListener('input', e => {
								handleDisabledOn(e.target, element, disabledConditions);
							});
						}
						
						handleDisabledOn(input, element, disabledConditions);
					}
				});
			}
		});
	}
});