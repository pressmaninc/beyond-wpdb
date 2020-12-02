const BEYOND_WPDB = {
    checkedValuesG: [],
    existJsonTablesG: [],
    existVirtualColumnsG: {},

    /**
   * Get exist json tables
   *
   * @returns {Promise<*>}
   */
    getExistJsonTablesApi() {
        const formData = new FormData();
        formData.append( 'action', BEYOND_WPDB_CONFIG.exist_tables.get.action );
        formData.append( 'nonce', BEYOND_WPDB_CONFIG.exist_tables.get.nonce );

        return BEYOND_WPDB.api( formData );
    },

    /**
   * Get exist virtual columns in json table
   *
   * @returns {Promise<*>}
   */
    getExistVirtualColumnsApi() {
        const formData = new FormData();
        formData.append( 'action', BEYOND_WPDB_CONFIG.virtual_columns.get.action );
        formData.append( 'nonce', BEYOND_WPDB_CONFIG.virtual_columns.get.nonce );

        return BEYOND_WPDB.api( formData );
    },
    createVirtualColumnsApi( primary, columns ) {
        const formData = new FormData();
        formData.append( 'action', BEYOND_WPDB_CONFIG.virtual_columns.create.action );
        formData.append( 'nonce', BEYOND_WPDB_CONFIG.virtual_columns.create.nonce );
        formData.append( 'primary', primary );
        formData.append( 'columns', columns );

        return BEYOND_WPDB.api( formData );
    },

    /**
   * Activate a specific table
   *
   * @param primary
   * @returns {Promise<*>}
   */
    activateActionApi( primary ) {
        const formData = new FormData();
        formData.append( 'action', BEYOND_WPDB_CONFIG.data_init.create.action );
        formData.append( 'nonce', BEYOND_WPDB_CONFIG.data_init.create.nonce );
        formData.append( 'primary', primary );

        return BEYOND_WPDB.api( formData );
    },

    /**
   * Deactivate a specific table
   *
   * @param primary
   * @returns {Promise<*>}
   */
    deactivateActionApi( primary ) {
        const formData = new FormData();
        formData.append( 'action', BEYOND_WPDB_CONFIG.data_init.delete.action );
        formData.append( 'nonce', BEYOND_WPDB_CONFIG.data_init.delete.nonce );
        formData.append( 'primary', primary );

        return BEYOND_WPDB.api( formData );
    },

    /**
   * send api
   *
   * @param formData
   * @returns {Promise<*>}
   */
    api( formData ) {
        return new Promise(function(resolve, reject) {
            jQuery.ajax({
                url: BEYOND_WPDB_CONFIG.api,
                type: "POST",
                async: true,
                contentType: false,
                processData: false,
                data: formData,
                dataType: "json",
            }).then(
                function (result, status, jqXHR) {
                    const re = {
                        data: result,
                        status: jqXHR.status
                    };
                    resolve(re);
                },
                function () {
                    reject();
                }
            )
        });
    },

    /**
   * check
   *
   * @param id
   * @param active
   */
    'check': ( id, active ) => {
        jQuery( `#${id}_${active}` )
            .prop( 'checked', true );
    },

    /**
   * display virtual columns section
   *
   * @param target
   * @param data
   * @param display
   */
    displayVirtualColumns: ( target, data, disabled ) => {
        if ( ! disabled ) {
            jQuery( `.${target}` )
                .removeClass( 'table_not_exists' );
            jQuery( `#${target}_textarea` )
                .prop( 'disabled', false );
            if ( data ) {
                jQuery( `.${target} textarea[name="${target}"]` )
                    .val( data.join( '\n' ) );
            }
        } else {
            jQuery( `.${target}` )
                .addClass( 'table_not_exists' );
            jQuery( `#${target}_textarea` )
                .prop( 'disabled', true );
            jQuery( `.${target} textarea[name="${target}"]` )
                .val( '' );
        }
    },

    /**
   * get checked values
   * @returns {[]}
   */
    getCheckedValues: () => {
        const checkedValues = [];

        BEYOND_WPDB_CONFIG.json_tables.forEach( ( table ) => {
            let checkedValue = jQuery(
                `[name=name_data_init_${table}]:checked`
            )
                .val();
            checkedValues.push( checkedValue );
        });

        return checkedValues;
    },

    /**
   * Get exist json tables and exist virtual columns in json table
   *
   * @returns {Promise<void>}
   */
    setTablesAndColumns: async() => {
        const existTables = [
        ];
        const existJsonTables = await BEYOND_WPDB.getExistJsonTablesApi();
        const existVirtualColumns = await BEYOND_WPDB.getExistVirtualColumnsApi();

        if ( 200 === existJsonTables.status && 200 === existVirtualColumns.status ) {
            existJsonTables.data.data.forEach( ( val ) => {
                existTables.push( val );
            });

            BEYOND_WPDB_CONFIG.json_tables.forEach( ( val ) => {
                if ( existTables.includes( val ) ) {
                    BEYOND_WPDB.check( `data_init_${val}`, 'active' );
                    BEYOND_WPDB.displayVirtualColumns(
                        `virtual_column_${val}`,
                        existVirtualColumns.data.data[val],
                        false
                    );
                } else {
                    BEYOND_WPDB.check( `data_init_${val}`, 'deactive' );
                    BEYOND_WPDB.displayVirtualColumns( `virtual_column_${val}`, '', true );
                }
            });

            jQuery( '.data-init-input-loading' )
                .addClass( 'd-none' );
            jQuery( '.data-init-input-radio' )
                .removeClass( 'd-none' );
            jQuery( '.data-init-input-radio' )
                .addClass( 'd-block' );
            jQuery( '.create_virtualColumns_text-area' )
                .removeClass( 'd-none' );
            jQuery( '.create_virtualColumns_text-area' )
                .addClass( 'd-inline-block' );

            this.checkedValuesG = BEYOND_WPDB.getCheckedValues();
            this.existJsonTablesG = existJsonTables.data.data;
            this.existVirtualColumnsG = existVirtualColumns.data.data;

            const $virtualColmunsBtn = jQuery( '#beyond-wpdb-virtual-columns-btn' );
            if ( ! this.existJsonTablesG.length ) {
                $virtualColmunsBtn.prop( 'disabled', true );
            } else {
                $virtualColmunsBtn.prop( 'disabled', false );
            }

        } else {
            if ( 200 !== existJsonTables.status ) {
                alert( existJsonTables.data );
            }
            if ( 200 !== existVirtualColumns.status ) {
                alert( existVirtualColumns.data );
            }
        }
    },

    /**
   * activate or deactivate the table
   *
   * @param val
   * @returns {Promise<void>}
   */
    activateOrDeactivateEach: async( jsonTable ) => {
        const prefix = BEYOND_WPDB_CONFIG.prefix;
        let primary = '';
        let checkedValue = jQuery(
            `[name=name_data_init_${jsonTable}]:checked`
        )
            .val();

        if ( jsonTable.match( /postmeta_beyond/ ) ) {
            primary = 'post';
        } else if ( jsonTable.match( /usermeta_beyond/ ) ) {
            primary = 'user';
        } else {
            primary = 'comment';
        }

        const $activateLoading = jQuery(
            `.activate_data_init_${prefix}${primary}meta_beyond`
        );

        if ( '1' === checkedValue ) {
            if ( ! this.existJsonTablesG.includes( jsonTable ) ) {
                $activateLoading.removeClass( 'd-none' );
                const response = await BEYOND_WPDB.activateActionApi( primary );
                $activateLoading.addClass( 'd-none' );

                let data = '';
                if ( 201 !== response.status ) {
                    if ( 'object' === typeof response.data ) {
                        data = response.data.data;
                    } else {
                        data = response.data;
                    }

                    const index = data.indexOf( 'at position' );
                    const position = parseInt( data.slice( index ).split( ' ' )[2] );
                    const maxLen = parseInt( jQuery( '#group_concat_max_len' ).html() );

                    if ( data.match( /Missing a closing quotation mark in string/ ) && ( position === maxLen + 2 ) ) {
                        alert( 'For an existing {postmeta|usermeta|comment},the value "group_concat_max_len" in mySQL may be too small.https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_group_concat_max_len' );
                    } else {
                        alert( data );
                    }
                }
            }
        } else {
            if ( this.existJsonTablesG.includes( jsonTable ) ) {
                $activateLoading.removeClass( 'd-none' );
                const response = await BEYOND_WPDB.deactivateActionApi( primary );
                $activateLoading.addClass( 'd-none' );

                if ( 201 !== response.status ) {
                    if ( 'object' === typeof response.data ) {
                        alert( response.data.data );
                    } else {
                        alert( response.data );
                    }
                }
            }
        }
    },

    /**
   * @param disabled
   */
    updateBtnDisabled: ( disabled ) => {
        const $InitUpdateBtn = jQuery( '#beyond-wpdb-init-btn' );
        const $VirtualColumnsUpdateBtn = jQuery( '#beyond-wpdb-virtual-columns-btn' );

        $InitUpdateBtn.prop( 'disabled', disabled );
        $VirtualColumnsUpdateBtn.prop( 'disabled', disabled );
    },

    /**
   * activate or deactivate each tables
   */
    activateOrDeactivate: async() => {
        const checkedValues = BEYOND_WPDB.getCheckedValues();

        if ( JSON.stringify( this.checkedValuesG ) !== JSON.stringify( checkedValues ) ) {
            BEYOND_WPDB.updateBtnDisabled( true );
            await BEYOND_WPDB.activateOrDeactivateEach( BEYOND_WPDB_CONFIG.json_tables[0]);
            await BEYOND_WPDB.activateOrDeactivateEach( BEYOND_WPDB_CONFIG.json_tables[1]);
            await BEYOND_WPDB.activateOrDeactivateEach( BEYOND_WPDB_CONFIG.json_tables[2]);
            BEYOND_WPDB.updateBtnDisabled( false );
        }
    },

    /**
   * create virtual columns on the table
   *
   * @param jsonTable
   * @returns {Promise<void>}
   */
    createVirtualColumnsEach: async( jsonTable ) => {
        const prefix = BEYOND_WPDB_CONFIG.prefix;
        let primary = '';
        let columns = jQuery( `#virtual_column_${jsonTable}_textarea` )
            .val();
        let checkingColumns = columns.split( '\n' )
            .filter( val => val );

        if ( JSON.stringify( checkingColumns.sort() ) === JSON.stringify( this.existVirtualColumnsG[jsonTable].sort() ) ) {
            return;
        }

        if ( jsonTable.match( /postmeta_beyond/ ) ) {
            primary = 'post';
        } else if ( jsonTable.match( /usermeta_beyond/ ) ) {
            primary = 'user';
        } else {
            primary = 'comment';
        }

        if ( ! jQuery( `.virtual_column_${jsonTable}` )
            .hasClass( 'table_not_exists' ) ) {
            const $createLoading = jQuery(
                `.create_virtual_column_${prefix}${primary}meta_beyond`
            );

            $createLoading.removeClass( 'd-none' );
            const response = await BEYOND_WPDB.createVirtualColumnsApi( primary, columns );
            $createLoading.addClass( 'd-none' );

            if ( 201 !== response.status ) {
                if ( 'object' === typeof response.data ) {
                    alert( response.data.data );
                } else {
                    alert( response.data );
                }
            }
        }
    },

    /**
   * create virtual columns on each tables
   *
   * @returns {Promise<void>}
   */
    createVirtualColumns: async() => {
        BEYOND_WPDB.updateBtnDisabled( true );
        if ( this.existJsonTablesG[0].match( /commentmeta_beyond/ ) ) {
            const table = this.existJsonTablesG.shift();
            this.existJsonTablesG.push( table );
        }
        for ( let table of this.existJsonTablesG ) {
            await BEYOND_WPDB.createVirtualColumnsEach( table );
        }
        BEYOND_WPDB.updateBtnDisabled( false );
    }
};


document.addEventListener( 'DOMContentLoaded', async() => {
    jQuery( '.update-nag' )
        .addClass( 'd-none' );

    await BEYOND_WPDB.setTablesAndColumns();

    jQuery( '#beyond-wpdb-init-btn' )
        .on( 'click', async() => {
            await BEYOND_WPDB.activateOrDeactivate();
            await BEYOND_WPDB.setTablesAndColumns();
        });

    jQuery( '#beyond-wpdb-virtual-columns-btn' )
        .on( 'click', async() => {
            await BEYOND_WPDB.createVirtualColumns();
            await BEYOND_WPDB.setTablesAndColumns();
        });
});
