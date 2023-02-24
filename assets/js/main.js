"use strict";

$(function () {
	
	
	/* ============= bootstrap tooltip ============ */
	
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	});
	
	
	/*
		============= console tricks [ display alert box ] ================
		:alert
	*/
	
	if( uss[':alert'] && uss[':alert'].trim() != '' ) {
		bootbox.alert({
			title: uss.platform,
			message: uss[':alert']
		});
	};
	
	
	/*
		============ console trick [ display toastr notification ] ===========
		:toastr.error
		:toastr.error.1
		:toastr.error.2
		:toastr.success
	*/
	
	Object.keys(uss).forEach(function(key) {
		let match = key.match(/^:toastr\.(?:error|info|warning|success)(?:\.[a-z0-9_\-]*)?$/i);
		if( !match ) return;
		let part = match[0].substr(1).split('.');
		let type = part[1];
		toastr[ type ]( uss[key], null, {
			progressBar: true,
			newestOnTop: false
		});
	});
	
	
	/* 
		============ image preview ============= 
		
		Preview an image on input change;
		
		<img src='' id='the-image-element />
		
		<input type='file' accept='image/*' data-uss-image-preview='#the-image-element' />
		
	*/
	
	$("input[type='file'][data-uss-image-preview]").each(function() {
		$(this).on('change', function() {
			try {
				let img = this.dataset.imagePreview;
				let image = $('img' + img);
				if( !image.length ) return;
				let reader = new FileReader();
				reader.addEventListener('load', function() {
					image.attr( 'src', this.result );
				});
				let file = this.files[0];
				let type = file.type.split('/')[0];
				if( type == 'image' ) reader.readAsDataURL( file );
				else toastr.error( 'Invalid Image file', null, {
					progressBar: true,
					showMethod: 'slideDown',
				});
			} catch(e) {
				console.log( e );
			};
		});
	});
	
	
	/*
		========== confirm an anchor before redirecting =========
		
		<a href='' data-uss-confirm='The confirmation message'> 
			Click here to redirect 
		</a>
		
	*/
	
	$("a[data-uss-confirm]").on('click', function(e) {
		e.preventDefault();
		let anchor = this;
		let message = this.dataset.ussConfirm;
		if( !message || message.trim() == '' ) message = 'You are about to leave this page';
		bootbox.confirm({
			message: message,
			size: 'small',
			className: 'text-center animate__animated animate__faster animate__bounceIn',
			centerVertical: true,
			closeButton: false,
			callback: function(choice) {
				if( !choice ) return;
				let target = anchor.target;
				if( (!target || target == "_self") && !anchor.dataset.ussFeatures ) {
					window.location.href = anchor.href;
				} else {
					/*
						windowFeature Sample that works on Chrome:
						------------------------------------------
						resizable=yes, scrollbars=yes, titlebar=yes, width=300, height=300, top=10, left=10
					*/
					window.open( anchor.href, target, anchor.dataset.ussFeatures );
				}
			},
			onShow: function(e) {
				$(e.currentTarget).removeClass('fade');
			}
		});
	});
	
	
	/*
		=================== confirm a form before submitting ===================
		
		<form method='POST' data-uss-confirm='The confirmation message'>
			...
		</form>
		
	*/
	
	$("form[data-uss-confirm]").on('submit', function(e) {
		e.preventDefault();
		let form = this;
		let message = this.dataset.ussConfirm;
		if( !message || message.trim() == '' ) message = 'Please confirm this process to continue';
		bootbox.confirm({
			message: `<div class='px-4'>${message}</div>`,
			className: 'text-center animate__animated animate__faster animate__bounceIn',
			size: 'small',
			callback: function(yea) {
				if( !yea ) return;
				let node = e.originalEvent.submitter;
				if( node.hasAttribute('name') ) {
					let nodeName = node.tagName.toLowerCase();
					let permit = [
						( nodeName == 'input' && node.type == 'submit' ),
						( nodeName == 'button' && ['submit', '', undefined].includes( node.type ) )
					];
					if( permit.includes(true) ) {
						let input = document.createElement('input');
						input.type = 'hidden';
						input.name = node.name;
						input.value = node.value;
						if( input.value == undefined ) input.value = '';
						form.appendChild( input );
					}
				};
				form.submit();
			},
			onShow: function(e) {
				$(e.currentTarget).removeClass('fade');
			},
			closeButton: false,
			centerVertical: true
		});
	});
	
	
	/*
		=============== copy a text on button click ==============
		
	*/
	
	$("[data-uss-copy]").click(function() {
		// get the element;
		let self = this;
		let el = $(this.dataset.ussCopy).get(0);
		if( !el ) return toastr.warning( "Failed to copy content" );
		// copy the text;
		let text;
		if( ['input', 'select', 'textarea'].includes( el.nodeName.toLowerCase() ) )
			text = el.value.trim();
		else text = el.innerText.trim();
		// add to clipboard
		navigator.clipboard.writeText( text ).then(
			() => toastr.info( self.dataset.ussMessage || 'Copied to clipboard' ),
			() => toastr.warning( 'Not copied to clipboard' )
		);
	});
	
	
	/*
		=========== Auto select an option from the <select/> element ===========
		Make select work similar to input
	*/
	
	$("select[value]").each(function() {
		let value = this.getAttribute('value');
		if( value == '' ) return;
		this.value = value;
	});
	
	
});
