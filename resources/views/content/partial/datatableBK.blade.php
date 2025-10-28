{{-- @php
    $userTypes = App\Helpers\UtilityHelper::getUserTypes();
@endphp --}}
<script type="text/javascript">
    function getDataTableS(options, filterData = {}, tableHeaders, getStats) {
        // Create an array of filter buttons
        let filter_dropdown_button = getFilterDropdownButtons(filterData)
        // --------------------------------------------------------------------
        var createTitle = options?.createTitle ?? "Add New Record";
        var isCreate = options?.createPermissions ? 'd-block' : 'd-none'; // : 'disabled';

        var import_btn_name = options?.is_import ?? "Import";
        var isImport = (options.createPermissions && options.is_import) ? 'd-block' : 'd-none'; // : 'disabled';
        // -------------------------------------------------------------------------------------


        var dataTable = $("#DataTables2025").DataTable({
            ajax: {
                "url": options.url,
                "error": function(jqXHR, textStatus, errorThrown) {
                    // Handle error here
                    $('#DataTables2025').DataTable().draw(false);
                    console.error("AJAX Error: ", textStatus, errorThrown);
                    $('#DataTables2025').append(
                        `<tr>
                          <td colspan="100%" class="">
                            <div class = "alert alert-danger text-center" role="alert" >
                              Error while  loading!
                            </div>
                          </td>
                         </tr>`
                    );
                }

            },
            columns: tableHeaders,
            // columnDefs:

            order: false,
            dom: '<"card-header"<"head-label text-center"><"dt-action-buttons text-end"B>><"d-flex justify-content-between align-items-center row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            displayLength: 7,
            lengthMenu: [7, 10, 25, 50, 75, 100],
            buttons: [
                ...filter_dropdown_button,
                {
                    text: '<i class="bx bx-import me-1"></i>' + import_btn_name,
                    className: 'create-new btn btn-primary mx-2 bulkImportBtn ' + isImport,
                },
                {
                    text: '<i class="bx bx-plus me-1"></i>' + createTitle,
                    className: 'create-new btn btn-primary addNewRecordBtn ' + isCreate,
                }

            ],
            // responsive: {
            //     details: {
            //         display: $.fn.dataTable.Responsive.display.modal({
            //             header: function(row) {
            //                 console.log('row')
            //                 var data = row.data();
            //                 return 'Details of ' + data['role_name'];
            //             }
            //         }),
            //         type: 'column',
            //         renderer: function(api, rowIdx, tableHeaders) {
            //             var data = $.map(tableHeaders, function(col, i) {
            //                 return col.title !==
            //                     '' ?
            //                     '<tr data-dt-row="' + col.rowIndex +
            //                     '" data-dt-column="' +
            //                     col.columnIndex +
            //                     '">' +
            //                     '<td>' +
            //                     col.title +
            //                     ':' +
            //                     '</td> ' +
            //                     '<td>' +
            //                     col.data +
            //                     '</td>' +
            //                     '</tr>' :
            //                     '';
            //             }).join('');

            //             return data ? $('<table class="table"/><tbody />').append(data) : false;
            //         }
            //     }
            // },
            success: function(response) {
                // Call the getStats callback and pass the response data
                // getStats(response);
                console.log("success response:", response);
            }

        });
        $('div.head-label').html('<h4 class="card-title mb-0">' + (options?.title ?? ' ') + '</h4>');
        //   }

        // Handle click event on dropdown items (Table filters)
        $(document).on('click', '.dropdown-item', function() {
            const selectedValue = $(this).text().trim().toLowerCase();
            console.log(selectedValue)
            $('#DataTables2025 tbody tr').filter(function() {
                if (selectedValue === 'all') {
                    $(this).toggle(true);
                } else {
                    $(this).toggle($(this).text().toLowerCase().indexOf(selectedValue) > -1);
                }
            });
        });
    };

    function getFilterDropdownButtons(filterData = {}) {
        function isObjectOrArray(value) {
            return (typeof value === 'object' && value !== null);
        }

        let dropdown_buttons = [];

        for (const key in filterData) {
            if (filterData.hasOwnProperty(key)) {
                const filter = filterData[key];

                if (isObjectOrArray(filter.data)) {
                    const filter_dropdown_options = Object.entries(filter.data).map(([key, value]) => {
                        return {
                            text: value,
                            className: 'dropdown-item',
                        };
                    });

                    dropdown_buttons.push({
                        extend: "collection",
                        className: "btn btn-label-primary dropdown-toggle me-2 custom-filter-dropdown",
                        text: filter.filter_name,
                        buttons: filter_dropdown_options
                    });
                }
            }
        }

        return dropdown_buttons;
    }

    // $(document).ready(function() {
    // }); // end jquery document dot write
</script>
