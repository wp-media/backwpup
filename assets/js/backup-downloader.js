window.BWU = window.BWU || {};

( function ( $, _, BWU, ajaxurl, tbRemove )
{
    var downloader;
    var decrypter;
    var Downloader;

    BWU.Restore = BWU.Restore || {};
    BWU.Restore.Functions = BWU.Restore.Functions || {

        /**
         * Remove previously printed messages
         */
        removeMessages: function ()
        {
            $( document.body )
                .find( '#bwu_response' )
                .remove();
        },

        /**
         * Print Error Messages
         *
         * @param {string} message The message to print.
         *
         * @return {void}
         */
        printMessageError: function ( message, container )
        {

            var $container = $( container );

            if ( !message ) {
                return;
            }

            this.removeMessages();

            $container
                .append( '<p id="bwu_response" class="response response-error">' + message + '</p>' );
        },

        /**
         * Print Success Messages
         *
         * @param {string} message The message to print.
         *
         * @return {void}
         */
        printMessageSuccess: function ( message, container )
        {
            var $container = $( container );

            if ( !message ) {
                return;
            }

            this.removeMessages();

            $container
                .append( '<p id="bwu_response" class="response response-success">' + message + '</p>' );
        },

        /**
         * Load Site
         *
         * @param {number} id The step to point to.
         * @param {string} nonce The value for the nonce.
         */
        loadNextStep: function ( id, nonce )
        {
            var search = 'step=' + id;
            location.replace(
                location.origin + location.pathname + '?' + search + '&page=backwpuprestore&backwpup_action_nonce=' + nonce );
        },

        /**
         * Calculate Percentage
         *
         * @param {number} index The current index of the file extracted.
         * @param {number} total The total count of files to extract.
         *
         * @returns {number} The percentage value
         */
        calculatePercentage: function ( index, total )
        {
            var value = index / total;

            return Math.round( value * 100 );
        },

        /**
         * Create a constant property
         * @param value
         * @returns {{value: *, writable: boolean, configurable: boolean, enumerable: boolean}}
         */
        makeConstant: function ( value )
        {
            return {
                value: value,
                writable: false,
                configurable: false,
                enumerable: false
            };
        }
    };
    BWU.States = BWU.States || Object.create( {}, {

        DONE: BWU.Restore.Functions.makeConstant( 'done' ),

        DOWNLOADING: BWU.Restore.Functions.makeConstant( 'downloading' ),

        NEED_DECRYPTION_KEY: BWU.Restore.Functions.makeConstant( 'need_decryption_key' )
    } );

    if ( !ajaxurl ) {
        // eslint-disable-line
        console.warn( 'Missing ajaxurl value.' );

        return;
    }
    if ( !( 'EventSource' in window ) ) {
        // eslint-disable-line
        console.warn( 'Event Source does not exist in this browser' );

        return;
    }

    function destruct ()
    {
        this.closeEventSource();
        this.cleanUi();
        this.decrypter && this.decrypter.destruct();
    }

    function hideElement ( el )
    {
        if ( !el ) {
            return;
        }

        el.style.display = 'none';
    }

    function showElement ( el )
    {
        if ( !el ) {
            return;
        }

        el.style.display = 'block';
    }

    Downloader = {

        showWaitingMessage: function ()
        {
            showElement( this.waitingUi );
        },

        showProgressUi: function ()
        {
            showElement( this.progressUi );
        },

        showSuccessMsg: function ()
        {
            showElement( this.successUi );
        },

        hideWaitingMessage: function ()
        {
            hideElement( this.waitingUi );
        },

        hideProgressUi: function ()
        {
            hideElement( this.progressUi );
        },

        hideNotice: function ()
        {
            BWU.Restore.Functions.removeMessages( this.containerUi );
        },

        hideSuccessMsg: function ()
        {
            hideElement( this.successUi );
        },

        cleanUi: function ()
        {
            this.hideWaitingMessage();
            this.hideProgressUi();
            this.hideNotice();
            this.hideSuccessMsg();
            this.decrypter && this.decrypter.hide();
        },

        done: function ()
        {
            this.showSuccessMsg();
            window.location.href = this.currentTarget.dataset.url;

            setTimeout( tbRemove, 3000 );
        },

        onMessage: function ( message )
        {
            var data;

            try {
                data = JSON.parse( message.data );

                switch ( data.state ) {
                    case BWU.States.DOWNLOADING:
                        this.cleanUi();
                        this.showProgressUi();

                        $( '#progresssteps' )
                            .css( {
                                width: data.download_percent + '%'
                            } )
                            .text( data.download_percent + '%' );
                        break;

                    case BWU.States.DONE:
                        this.done( data.message );
                        break;
                }
            } catch ( exc ) {
                BWU.Restore.Functions.printMessageError(
                    exc.message,
                    this.containerUi
                );
                destruct.call( this );
            }
        },

        onError: function ( message )
        {
            var data = JSON.parse( message.data );

            this.closeEventSource();

            switch ( data.message ) {
                case BWU.States.NEED_DECRYPTION_KEY:
                    this.cleanUi();
                    this.decrypter && this.decrypter.needDecryption( data.status );
                    break;
                default:
                    BWU.Restore.Functions.printMessageError( data.message, this.containerUi );
                    destruct.call( this );
                    break;
            }

            return this;
        },

        initializeEventSource: function ()
        {
            if ( !_.isUndefined( this.eventSource ) ) {
                return;
            }

            this.eventSource = new EventSource(
                ajaxurl
                + '?action=download_backup_file&destination=' + this.currentTarget.dataset.destination
                + '&jobid=' + this.currentTarget.dataset.jobid
                + '&file=' + this.currentTarget.dataset.file
                + '&local_file=' + this.currentTarget.dataset.localFile
                + '&backwpup_action_nonce=' + this.currentTarget.dataset.nonce
            );

            this.eventSource.onmessage = this.onMessage;
            this.eventSource.addEventListener( 'log', this.onError );
        },

        closeEventSource: function ()
        {
            if ( _.isUndefined( this.eventSource ) ) {
                return;
            }

            this.eventSource.close();
            this.eventSource = undefined;
        },

        startDownload: function ( evt )
        {
            evt.preventDefault();

            this.currentTarget = evt.target;

            this.showWaitingMessage();
            this.initializeEventSource();
        },

        decrypt: function ()
        {
            this.cleanUi();
            this.decrypter && this.decrypter.decrypt( {
                backwpup_action_nonce: this.currentTarget.dataset.nonce,
                encrypted_file_path: this.currentTarget.dataset.localFile
            } );
        },

        construct: function ( decrypter )
        {

            var containerUi = document.querySelector( '#tb_container' );
            if ( !containerUi ) {
                return false;
            }

            _.bindAll(
                this,
                'showWaitingMessage',
                'hideWaitingMessage',
                'showProgressUi',
                'hideSuccessMsg',
                'showSuccessMsg',
                'done',
                'onMessage',
                'onError',
                'initializeEventSource',
                'closeEventSource',
                'startDownload',
                'addListeners',
                'decrypt',
                'hideNotice',
                'cleanUi',
                'init'
            );

            this.containerUi = containerUi;
            this.waitingUi = this.containerUi.querySelector( '#download-file-waiting' );
            this.progressUi = this.containerUi.querySelector( '.progressbar' );
            this.successUi = this.containerUi.querySelector( '#download-file-success' );

            this.currentTarget = undefined;
            this.eventSource = undefined;
            this.decrypter = decrypter;

            return this;
        },

        addListeners: function ()
        {
            _.forEach( document.querySelectorAll( '.backup-download-link' ), function ( downloadLink )
            {
                downloadLink.addEventListener( 'click', this.startDownload );
            }.bind( this ) );

            $( '#submit_decrypt_key' ).on( 'click', this.decrypt );
            $( 'body' ).on( 'thickbox:removed', function ()
            {
                destruct.call( this );
            }.bind( this ) );

            if ( this.decrypter ) {
                $( 'body' ).on( this.decrypter.ACTION_DECRYPTION_SUCCESS, this.done );
            } else {

            }

            return this;
        },

        init: function ()
        {
            this.addListeners();

            return this;
        }
    };

    downloader = Object.create( Downloader );

    if ( !_.isUndefined( BWU.DecrypterFactory ) ) {
        decrypter = BWU.DecrypterFactory(
            ajaxurl,
            document.querySelector( '#decrypt_key' ),
            document.querySelector( '#decryption_key' )
        );
    }

    if ( downloader.construct( decrypter ) ) {
        downloader.init();
    }

}( window.jQuery, window._, window.BWU, window.ajaxurl, window.tb_remove ) );
