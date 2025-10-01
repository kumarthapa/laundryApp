<script type="text/javascript">
    // Main DataTable initializer + unified delete support
    function getDataTableS(options, filterData = {}, tableHeaders, getStats = null) {
        console.log("DataTable URL:", options.url);
        console.log("is_delete", options.is_delete)
        // Generate Select2 filters
        getFilterDropdownButtons(filterData);

        // Initialize DataTable
        var dataTable = $("#DataTables2024").DataTable({
            ajax: {
                url: options.url,
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error: ", textStatus, errorThrown);
                    $('#DataTables2024').html(
                        `<tr>
                            <td colspan="100%" class="text-center">
                                <div class="alert alert-danger" role="alert">Error while loading data!</div>
                            </td>
                        </tr>`
                    );
                }
            },
            columns: tableHeaders,
            order: false,
            dom: '<"card-header"<"head-label text-center"><"dt-action-buttons text-end"B>><"d-flex justify-content-between align-items-center row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            displayLength: options?.displayLength ?? 10,
            lengthMenu: [7, 10, 25, 50, 75, 100],
            buttons: [{
                    text: '<i class="bx bx-trash me-1"></i> Delete',
                    className: 'create-new btn btn-danger mx-2 deleteSelectedBtn disabled ' + (options
                        .is_delete ? 'd-block' : 'd-none'),
                    action: function(e, dt, node, config) {
                        // collect selected ids and call unified delete
                        var ids = [];
                        $('#DataTables2024 tbody input.row-checkbox:checked').each(function() {
                            var id = $(this).data('id');
                            if (id) ids.push(id);
                        });
                        if (ids.length === 0) {
                            alert('Please select at least one row to delete.');
                            return;
                        }
                        unifiedDelete(ids, options.delete_url);
                    }
                },
                {
                    text: '<i class="bx bx-export me-1"></i>' + (options?.is_export2 ?? options.is_export2),
                    className: 'create-new btn btn-primary mx-2 exportBtn2 ' + (options.is_export2 ?
                        'd-block' : 'd-none'),
                    action: function(e, dt, node, config) {
                        $(".exportBtn2").trigger('click');
                    }
                },
                {
                    text: '<i class="bx bx-export me-1"></i>' + (options?.is_export ?? "Export All"),
                    className: 'create-new btn btn-primary mx-2 exportBtn ' + (options.is_export ?
                        'd-block' : 'd-none'),
                    action: function(e, dt, node, config) {
                        $(".exportBtn").trigger('click');
                    }
                },

                {
                    text: '<i class="bx bx-import me-1"></i>' + (options?.is_import ?? "Import"),
                    className: 'create-new btn btn-primary mx-2 bulkImportBtn ' + (options
                        .createPermissions && options.is_import ? 'd-block' : 'd-none'),
                },
                {
                    text: '<i class="bx bx-plus me-1"></i>' + (options?.createTitle ?? "Add New Record"),
                    className: 'create-new btn btn-primary addNewRecordBtn ' + (options.manuall_create ?
                        'd-block' : 'd-none'),
                }
            ],
            drawCallback: function(settings) {
                // Insert select-all header checkbox into first TH if not present
                var $theadFirst = $('#DataTables2024 thead th').first();
                if ($theadFirst.length && $theadFirst.find('.select-all-checkbox').length === 0) {
                    $theadFirst.html(
                        '<div class="form-check"><input type="checkbox" class="form-check-input select-all-checkbox" /></div>'
                    );
                }

                // update Delete Selected button state based on selection
                toggleDeleteSelectedButton();
            }
        });

        // header label
        $('div.head-label').html('<h4 class="card-title mb-0">' + (options?.title ?? ' ') + '</h4>');

        // --- events: selection handling ---
        $(document).on('change', '.select-all-checkbox', function() {
            var checked = $(this).prop('checked');
            $('#DataTables2024 tbody').find('input.row-checkbox').prop('checked', checked);
            toggleDeleteSelectedButton();
        });

        $(document).on('change', '#DataTables2024 tbody input.row-checkbox', function() {
            var allCount = $('#DataTables2024 tbody input.row-checkbox').length;
            var checkedCount = $('#DataTables2024 tbody input.row-checkbox:checked').length;
            $('.select-all-checkbox').prop('checked', allCount > 0 && allCount === checkedCount);
            toggleDeleteSelectedButton();
        });

        function toggleDeleteSelectedButton() {
            var checkedCount = $('#DataTables2024 tbody input.row-checkbox:checked').length;
            var $btn = $('.deleteSelectedBtn');
            if (checkedCount > 0) {
                $btn.removeClass('disabled');
            } else {
                $btn.addClass('disabled');
            }
        }

        // --- Search & filters ---
        $(document).on('change', '.filter-selected-data', function() {
            let filters = {};
            $('.filter-selected-data').each(function() {
                const filterKey = $(this).attr('id').replace('Filter', '');
                filters[filterKey] = $(this).val();
            });
            const queryString = $.param(filters);
            $('#DataTables2024').DataTable().ajax.url(options.url + '?' + queryString).load();
        });

        $(document).on('keyup', '.dataTables_filter input[type="search"]', function() {
            let filters2 = {};
            filters2['search'] = $(this).val();
            filters2['default_dateRange'] = $("#selectedDaterange").val() || '';
            const queryString2 = $.param(filters2);
            $('#DataTables2024').DataTable().ajax.url(options.url + '?' + queryString2).load();
        });

        return dataTable;
    }

    // helper to render filter dropdowns (unchanged)
    function getFilterDropdownButtons(filterData = {}) {
        let dropdownButtonsHtml = '';
        for (const key in filterData) {
            if (filterData.hasOwnProperty(key)) {
                const filter = filterData[key];
                dropdownButtonsHtml += `
                <select id="${key}Filter" class="form-control filter-selected-data select2-filter" style="width: 200px;">
                    <option value="">${filter.filter_name}</option>
                    ${Object.entries(filter.data)
                        .map(([filterKey, filterValue]) => `<option value="${filterKey}">${filterValue}</option>`)
                        .join('')}
                </select>
            `;
            }
        }
        // Inject into an existing element, e.g., with id 'filter-container'
        $('#filter-container').prepend(dropdownButtonsHtml);
        $('.select2-filter').select2(); // Initialize Select2 on these dropdowns
    }

    // Unified delete function for single or multiple ids
    // Accept either a single id (number/string) or an array of ids
    function unifiedDelete(idsOrId, delete_url) {
        var ids = Array.isArray(idsOrId) ? idsOrId : [idsOrId];

        if (!ids || ids.length === 0) {
            alert('No items selected for deletion.');
            return;
        }

        if (!confirm('Are you sure you want to delete ' + ids.length + ' item(s)? This action cannot be undone.')) {
            return;
        }

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: delete_url,
            method: 'POST',
            data: {
                ids: ids
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || (ids.length + ' item(s) deleted.'));
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Delete failed');
                }
            },
            error: function(xhr, status, error) {
                var msg = xhr.responseJSON?.message || error;
                toastr.error("Error: " + msg);
            }
        });
    }

    // Single-row delete trigger used in action dropdown - calls unifiedDelete
    function deleteRowById(id) {
        if (!id) {
            alert('Invalid id');
            return;
        }
        unifiedDelete(id, '');
    }
</script>
