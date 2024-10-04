
// import './dx.all.js'

$(() => {

    var popup = $('#popup').dxPopup().dxPopup('instance');
    const apiUrl = $(location).attr('href').replace($(location).attr('pathname').split('/')[2], '') + '/api.php';


    var startDate = null;

    startDate ??= new Date(2018, 1, 1, 1, 1, 1);

    var endDate = new Date();

    const $loadIndicator = $('<div>').dxLoadIndicator({ visible: false });
    const $dropDownButtonImage = $('<img>', {
        alt: 'Custom icon',
        src: 'images/icons/custom-dropbutton-icon.svg',
        class: 'custom-icon',
    });

    function getPopupOptions(labelDetails, printer) {
        const popupOptions = {
            width: 700,
            height: 550,
            container: '.dx-viewport',
            showTitle: true,
            title: 'Print Imformation',
            visible: false,
            dragEnabled: true,
            hideOnOutsideClick: false,
            showCloseButton: false,
            position: {
                at: 'center',
                my: 'center',
                collision: 'overlay',
            },
            toolbarItems: [
                {
                    widget: 'dxSelectBox',
                    toolbar: 'bottom',
                    location: 'before',
                    options: {
                        // items: simpleProducts,
                        inputAttr: { 'aria-label': 'Select Printer' },
                        displayExpr: 'lpt_number',
                        valueExpr: 'lpt_number',
                        dataSource: {
                            store: {
                                type: 'odata',
                                version: 2,
                                url: apiUrl + '/getAllPrinters',
                            },
                        },
                        itemTemplate(data) {
                            return `<div class='custom-item'><div class='product-name'>${data.lpt_number}</div></div>`;
                        },
                    },
                },
                {
                    widget: 'dxButton',
                    toolbar: 'bottom',
                    location: 'before',
                    options: {
                        icon: 'print',
                        stylingMode: 'contained',
                        text: `Print`,
                        onClick() {
                            const message = `Printing label for item ${labelDetails.ORDER_ITEM_NUMBER} of order ${labelDetails.ORDER_NUMBER} on ${printer.lpt_description}`;
                            $.post("http://" + printer.ptr_connected_mcn_ip.trim() + ":" + printer.ptr_connected_mcn_port + "/api/v1/print?design=wmsLabel&variables=" + JSON.stringify(labelDetails) + "&printer=Preview&window=show&copies=1", { variables: labelDetails }).then(res => res);
                            DevExpress.ui.notify({
                                message,
                                position: {
                                    my: 'center top',
                                    at: 'center top',
                                },
                            }, 'success', 3000);
                            popup.hide()
                        },
                    },
                },
                {
                    widget: 'dxButton',
                    toolbar: 'bottom',
                    location: 'after',
                    options: {
                        text: 'Close',
                        stylingMode: 'outlined',
                        type: 'normal',
                        onClick(e) {
                            popup.hide();
                        },
                    },
                }],
        };
        return popupOptions;
    }

    const popupContentTemplate = function (labelDetails) {
        return $('<div>').append(
            $(`<div class="label-container">
                  <div class="row">
                    <div class="col-8">
                      <div class="label-header">${labelDetails.SELLER_LOGO}</div>
                      <div>${labelDetails.SELLER_ADDRESS}</div>
                    </div>
                    <div class="col-4 text-end">
                      <div>id#</div>
                      <div class="id-box">${labelDetails.LABEL_NUMBER}</div>
                    </div>
                  </div>

                  <div class="row mt-3">
                    <div class="col-8 border-box">
                      <div class="text-bold">${labelDetails.BUYER_NAME}</div>
                      <div>${labelDetails.BUYER_ADD}</div>
                      <div>Q5 13</div>
                    </div>
                    <div class="col-4 border-box">
                      <div class="d-flex justify-content-between small-text">
                        <div>${labelDetails.ORDER_NUMBER}</div>
                        <div>26358885</div>
                      </div>
                      <div class="d-flex justify-content-between small-text">
                        <div>PO</div>
                        <div>${labelDetails.PO_NUMBER}</div>
                      </div>
                      <div class="d-flex justify-content-between small-text">
                        <div>SVC</div>
                        <div>${labelDetails.SHIP_CODE}</div>
                      </div>
                    </div>
                  </div>
                    <div class="row mt-1">
                        <div class="barcode col-8"></div>
                        <div class="text-center text-bold col-2 row align-items-center">${labelDetails.ABBR}</div>
                    </div>  
                
                  <div class="row mt-2">
                    <div class="col-8 border-box">
                      <div class="text-bold">L2233K11</div>
                    </div>
                    <div class="col-4 border-box d-flex justify-content-between">
                      <div>62823</div>
                      <div>BOS</div>
                      <div>1</div>
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-8">
                      <div class="text-bold">${labelDetails.PART_DESCRIPTION}</div>
                    </div>
                    <div class="col-4 d-flex justify-content-between">
                      <div>151732</div>
                      <div>26358885</div>
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-6">
                      <div class="small-text">stop#</div>
                    </div>
                    <div class="col-6 text-end">
                      <div>0</div>
                    </div>
                  </div>
                </div>`)
        );
    };


    // $('#range-selection').dxDateRangeBox({
    //     value: initialValue,
    //     onValueChanged: showSelectedDays,
    // });



    var dataGrid = $('#gridContainer').dxDataGrid({}).dxDataGrid('instance');

    $('#start-date-time').dxDateBox({
        type: 'datetime',
        value: (new Date()).getTime() - 365 * 24 * 60 * 60 * 1000,
        inputAttr: { 'aria-label': 'Date Time' },
        onValueChanged(data) {
            console.log(data);
            let date = new Date(data.value)
            startDate = `${date.getFullYear()}/${date.getMonth()}/${date.getDay()} ${date.getHours()}:${date.getMinutes()}:${date.getSeconds()}`
            dataGrid.refresh()
            //how to reload data in  devexpress jquery datagrid?
        },
    });

    $('#end-date-time').dxDateBox({
        type: 'datetime',
        value: new Date(),
        inputAttr: { 'aria-label': 'Date Time' },
        onValueChanged(data) {
            let date = new Date(data.value)
            endDate = `${date.getFullYear()}/${date.getMonth()}/${date.getDay()} ${date.getHours()}:${date.getMinutes()}:${date.getSeconds()}`
            dataGrid.refresh()
        },
    });

    $('#icon-done').dxButton({
        icon: 'check',
        type: 'success',
        text: 'Done',
        onClick() {
            DevExpress.ui.notify('The Done button was clicked');
        },
    });

    dataGrid = $('#gridContainer').dxDataGrid({
        dataSource: {
            store: {
                type: 'odata',
                version: 2,
                url: apiUrl + `/getAllOrders?startDate=${startDate.getFullYear()}/${startDate.getMonth()}/${startDate.getDay()} ${startDate.getHours()}:${startDate.getMinutes()}:${startDate.getSeconds()}
                &endDate=${endDate.getFullYear()}/${endDate.getMonth()}/${endDate.getDay()} ${endDate.getHours()}:${endDate.getMinutes()}:${endDate.getSeconds()}}`,
                key: 'order_num',
            },
        },
        filterRow: {
            visible: true,
            applyFilter: 'auto',
        },
        // searchPanel: {
        //     visible: true,
        //     width: 240,
        //     placeholder: 'Search...',
        // },
        headerFilter: {
            visible: true,
        },
        allowColumnResizing: true,
        columnAutoWidth: true,
        // columnChooser: {
        //   enabled: true,
        // },
        columnFixing: {
            enabled: true,
        },
        paging: {
            pageSize: 10,
        },
        pager: {
            showPageSizeSelector: true,
            allowedPageSizes: [10, 25, 50, 100],
        },
        remoteOperations: false,
        // groupPanel: { visible: true },
        // grouping: {
        //     autoExpandAll: false,
        // },
        // allowColumnReordering: true,
        rowAlternationEnabled: true,
        showBorders: true,
        width: '100%',
        columns: [
            // {
            //     caption: "Print All",
            //     type: 'buttons',
            //     width: 110,
            //     buttons: [{
            //         hint: 'Print All',
            //         icon: 'print',
            //         visible(e) {
            //             return !e.row.isEditing;
            //         },
            //         disabled(e) {
            //             // return isChief(e.row.data.Position);
            //         },
            //         onClick(e) {
            //             // const clonedItem = $.extend({}, e.row.data, { ID: maxID += 1 });
            //             // employees.splice(e.row.rowIndex, 0, clonedItem);
            //             // e.component.refresh(true);
            //             e.event.preventDefault();
            //         },
            //     }],
            // },
            {
                dataField: 'name',
                caption: 'Company Name',
                width: '15%',
            },
            {
                dataField: "customer_id",
                dataType: 'number'
            },
            {
                dataField: "order_num",
                dataType: 'number'
            },
            {
                dataField: "cust_po_num",
                dataType: 'number'
            },
            {
                dataField: 'host_order_num',
                caption: 'Host Order No.',
                width: '8%',
            },
            "ship_via",
            "special_instr",
            "shipping_instr",
            "Address",
            "city",
            "state",
            "zip",
        ],
        masterDetail: {
            enabled: true,
            template(container, options) {
                const orderData = options.data;
                $.get(apiUrl + '/getOrderDetails?orderNumber=' + orderData.order_num).then(res => {
                    // $('<div>').addClass('master-detail-caption').text('orderData . FirstName orderData . LastNames Tasks:').appendTo(container);
                    $('<div>').dxDataGrid({
                        columnAutoWidth: true,
                        showBorders: true,
                        dataSource: res,
                        rowAlternationEnabled: true,
                        keyExpr: 'shadow',
                        columns: [
                            // "line_num",
                            "shadow",
                            "p_l",
                            "part_number",
                            "part_desc",
                            // "uom",
                            "qty_ord",
                            // "qty_ship",
                            // "qty_bo",
                            // "qty_avail",
                            "min_ship_qty",
                            // "case_qty",
                            "inv_code",
                            // "line_status",
                            // "hazard_id",
                            // "zone",
                            // "whse_loc",  
                            "qty_in_primary",
                            // "num_messg",
                            "part_weight",
                            // "part_subline",
                            // "part_category",
                            // "part_group",
                            // "part_class",
                            // "item_pulls",
                            // "specord_num",
                            // "inv_comp",
                            {
                                caption: "Print",
                                type: 'buttons',
                                width: 110,
                                buttons: [{
                                    hint: 'Print',
                                    icon: 'print',
                                    visible(e) {
                                        return !e.row.isEditing;
                                    },
                                    disabled(e) {
                                        // return isChief(e.row.data.Position);
                                    },
                                    onClick(e) {
                                        const itemData = e.row.data;
                                        var labelDetails =
                                        {
                                            BARCODE: itemData.shadow,
                                            ORDER_ITEM_NUMBER: "<b>" + itemData.shadow + "</b>",
                                            PO_NUMBER: orderData.cust_po_num,
                                            SELLER_LOGO: "<i><b>" + orderData.company_name + "</b></i>",
                                            PART_DESCRIPTION: itemData.part_desc,
                                            SHIP_CODE: orderData.ship_via,
                                            SELLER_ADDRESS: orderData.Address,
                                            LABEL_NUMBER: "<b>" + (options.row.rowIndex + 1) + "</b>",
                                            BUYER_ADD: orderData.company_address,
                                            BUYER_NAME: "<b>" + orderData.name + "</b>",
                                            ORDER_NUMBER: itemData.ord_num,
                                            ABBR: `<b>${orderData.company_abbr}</b>`
                                        };


                                        popup = $('#popup').dxPopup({ contentTemplate: popupContentTemplate(labelDetails), ...getPopupOptions(labelDetails, e.row.data) }).dxPopup('instance');

                                        popup.show();

                                        // $.post("http://" + str.trim(e.row.data.ptr_connected_mcn_ip) + ":" + ptr_connected_mcn_port + "/api/v1/print?design=wmsLabel&variables=" + labelDesignString + "&printer=Preview&window=show&copies=1", { variables: labelDetails }).then(res => res);
                                        e.event.preventDefault();
                                    },
                                }],
                            },
                        ],
                    }).appendTo(container);
                })
            },
        },
    }).dxDataGrid('instance');
});  