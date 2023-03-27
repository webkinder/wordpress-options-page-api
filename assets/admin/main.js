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

			if (compare === '==') {
				if (input.value !== value) {
					isVisible = false;
				}
			} else {
				if (input.value === value) {
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
		} else {
			elementToHide.classList.add('hidden');
		}
	};

	// Handle show_on mechanism
	const showOnElements = document.querySelectorAll('[data-wk-show-on]');
	if (showOnElements.length) {
		showOnElements.forEach(element => {
			const {
				wkShowOn
			} = element.dataset;

			// split with | to get multiple values
			const showConditions = wkShowOn.split('|');

			// loop if not empty
			if (showConditions.length) {
				showConditions.forEach(condition => {
					const [key] = condition.split(':');
					const input = document.querySelector(`[name*="${key}"]`);
					if (!!input) {
						// TODO: decide if updated on actual input or not
						// if (input.type === 'radio') {
						// 	input.addEventListener('change', e => {
						// 		handleShowOn(e.target, element, showConditions);
						// 	});
						// } else if (input.type === 'checkbox') {
						// 	input.addEventListener('change', e => {
						// 		handleShowOn(e.target, element, showConditions);
						// 	});
						// } else {
						// 	input.addEventListener('input', e => {
						// 		handleShowOn(e.target, element, showConditions);
						// 	});
						// }

						handleShowOn(input, element, showConditions);
					}
				});
			}
		});
	}
});