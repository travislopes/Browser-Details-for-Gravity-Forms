window.GFBrowserDetails = null;

( function( $ ) {
	
	GFBrowserDetails = function( args ) {
		
		for ( var prop in args ) {
			if ( args.hasOwnProperty( prop ) )
				this[prop] = args[prop];
		}
		
		this.form = $( '#gform_' + this.formId );
		
		this.init = function() {
			
			/* Load a WhichBrowser object. */
			this.whichBrowser = new WhichBrowser();
			
			/* Prepare the browser details. */
			this.prepareDetails();
			
			this.displayBrowserDetails();
			
		}
		
		this.displayBrowserDetails = function() {
			
			var html = '<li class="gfield browser_details">';
			
			/* Display browser details if enabled. */
			if ( this.displayDetails ) {

				html += '<dl>';

				/* Add each detail to the form. */
				for ( var detail in this.details ) {
					
					html += '<dt>' + this.labels[ detail ] + '</dt>';
	
					if ( typeof( this.details[ detail ] ) == 'object' )
						html += '<dd>' + this.details[ detail ].string + '</dd>';
					else if ( typeof( this.details[ detail ] ) == 'boolean' )
						html += '<dd>' + ( this.details[ detail ] ? 'Enabled' : 'Disabled' ) + '</dd>';					
					else
						html += '<dd>' + this.details[ detail ] + '</dd>';
					
				}
				
				html += '</dl>';
			
			}
			
			/* Add browser details to input. */
			html += "<input type='hidden' name='gform_browserdetails' id='gform_browserdetails' value='" + JSON.stringify( this.details ) + "' />";
			
			html += '</li>';
			
			/* Append HTML to form. */
			this.form.find( '.gform_fields' ).append( html );
			
		}
		
		this.getBrowser = function() {
			
			return this.whichBrowser.browser.name + ' ' + this.whichBrowser.browser.version.original;
			
		}
		
		this.getBrowserResolution = function() {
			
			resolution = {
				'width':  window.innerWidth || document.documentElement.clientWidth || document.body.offsetWidth,
				'height': window.innerHeight || document.documentElement.clientHeight || document.body.offsetHeight,	
			};
			
			resolution.string = resolution.width + 'x' + resolution.height;
			
			return resolution;
			
		}
		
		this.getColorDepth = function() {
			
			return screen.colorDepth + 'bit';
			
		}
		
		this.getCookies = function() {
			
			return navigator.cookieEnabled;
			
		}
		
		this.getFlashVersion = function() {
			
			/* If flash is not installed, return false. */
			if ( ! FlashDetect.installed )
				return false;
				
			flash_version  = FlashDetect.major;
			flash_version += '.' + FlashDetect.minor;
			flash_version += '.' + ( FlashDetect.revision < 0 ? 0 : FlashDetect.revision );
			
			return flash_version;
			
		}
		
		this.getOperatingSystem = function() {
			
			return this.whichBrowser.os.name + ' ' + this.whichBrowser.os.version.original;
			
		}
		
		this.getScreenResolution = function() {
			
			return {
				'string': screen.width + 'x' + screen.height,
				'width':  screen.width,
				'height': screen.height	
			};
			
		}
		
		this.prepareDetails = function() {
			
			this.details = {
				'browser':           this.getBrowser(),
				'browserResolution': this.getBrowserResolution(),
				'colorDepth':        this.getColorDepth(),
				'cookies':           this.getCookies(),
				'flashVersion':      this.getFlashVersion(),
				'ip':                this.ipAddress,
				'javascript':        true,
				'operatingSystem':   this.getOperatingSystem(),
				'screenResolution':  this.getScreenResolution()
			}
			
		}
		
		this.init();
		
	}
	
} )( jQuery );