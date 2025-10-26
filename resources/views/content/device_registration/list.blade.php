@extends('layouts/contentNavbarLayout')

@section('title', 'Device Registration - List')
@section('page-style')
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">
    <style>
        /* small niceties */
        table.dataTable tbody tr td {
            vertical-align: middle;
        }

        .dt-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        /* keep table compact like your reference */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            height: calc(1.6em + .5rem);
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card p-3">
                <div class="top-actions">
                    <div>
                        <h5 class="mb-0">Device Registrations</h5>
                        <small class="text-muted">Manage device licenses</small>
                    </div>

                    <div>
                        <a href="{{ route('device_registration.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus"></i> Add Device
                        </a>
                    </div>
                </div>

                <div class="card-datatable table-responsive pt-0">
                    <table class="datatables-basic table table-bordered table-striped" id="DataTables2025"
                        style="width:100%;">
                        <thead>
                            <tr id="dt-head-row">
                                {{-- headers will be injected by JS using $table_headers (checkbox skipped) --}}
                            </tr>
                        </thead>
                        <tbody>
                            {{-- DataTables will populate body --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewRowDetails" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="row-title">Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-sm-4">Device ID</dt>
                        <dd class="col-sm-8" id="device_id">-</dd>
                        <dt class="col-sm-4">Serial</dt>
                        <dd class="col-sm-8" id="serial_number">-</dd>
                        <dt class="col-sm-4">License</dt>
                        <dd class="col-sm-8" id="license_key">-</dd>
                        <dt class="col-sm-4">Start</dt>
                        <dd class="col-sm-8" id="start_date">-</dd>
                        <dt class="col-sm-4">End</dt>
                        <dd class="col-sm-8" id="end_date">-</dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8" id="status">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        $(function() {
            // tableHeaders is generated server-side by TableHelper and contains array of {key: "Title"} objects
            var tableHeaders = {!! $table_headers !!};

            // Convert headers to an ordered column config for DataTables
            var columns = [];
            var headerRowHtml = '';

            function colObj(dataKey, title) {
                return {
                    data: dataKey,
                    title: title,
                    defaultContent: '',
                };
            }

            // Build headers and columns; SKIP checkbox column entirely
            tableHeaders.forEach(function(h) {
                var keys = Object.keys(h);
                var key = keys[0];
                var title = h[key];

                // Skip checkbox column
                if (key === 'checkbox') {
                    return;
                }

                // build thead
                headerRowHtml += '<th>' + title + '</th>';

                var c = colObj(key, title);

                // special handling for actions/dates/id
                if (key === 'actions') {
                    c.orderable = false;
                    c.searchable = false;
                    c.className = 'dt-actions';
                    c.width = '100px';
                    c.render = function(data) {
                        return data || '';
                    };
                } else if (key === 'id' || key === 'device_registration_id') {
                    // Hide id column (useful for ordering/search)
                    c.visible = false;
                } else if (key === 'created_at' || key === 'updated_at') {
                    c.render = function(data) {
                        if (!data) return '';
                        var d = new Date(data);
                        if (!isNaN(d.getTime())) {
                            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') +
                                '-' + String(d.getDate()).padStart(2, '0') + ' ' + String(d.getHours())
                                .padStart(2, '0') +
                                ':' + String(d.getMinutes()).padStart(2, '0');
                        }
                        return data;
                    };
                } else if (key === 'start_date' || key === 'end_date') {
                    c.render = function(data) {
                        if (!data) return '-';
                        return data;
                    };
                } else {
                    c.render = function(data) {
                        return data === null || data === undefined || data === '' ? '-' : data;
                    };
                }

                columns.push(c);
            });

            // Destroy existing instance only (do NOT remove the table or THEAD)
            if ($.fn.DataTable.isDataTable('#DataTables2025')) {
                $('#DataTables2025').DataTable().clear().destroy();
            }

            // Inject headers into the table thead after destroy
            $('#dt-head-row').html(headerRowHtml);

            // Compute default order index:
            // Prefer hidden id/device_registration_id if present, otherwise first non-actions column
            var orderIndex = 0;
            for (var i = 0; i < columns.length; i++) {
                if (columns[i].data === 'id' || columns[i].data === 'device_registration_id') {
                    orderIndex = i;
                    break;
                }
            }
            if (orderIndex === 0) {
                for (var j = 0; j < columns.length; j++) {
                    if (columns[j].data !== 'actions') {
                        orderIndex = j;
                        break;
                    }
                }
            }

            $('#DataTables2025').DataTable({
                processing: true,
                responsive: true,
                autoWidth: false,
                serverSide: false, // since backend returns full data (not paginated)
                pageLength: 10,
                lengthMenu: [7, 10, 25, 50, 75, 100],
                ajax: {
                    url: "{{ route('device_registration.list') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    dataSrc: function(json) {
                        // Check if data exists and return it, else empty array
                        if (json && json.data) {
                            return json.data;
                        } else {
                            console.error('No data found in response:', json);
                            return [];
                        }
                    },
                    error: function(xhr, status, err) {
                        console.error('DataTables AJAX error:', status, err);
                        toastr.error('Failed to load device registration data.');
                    }
                },
                columns: [{
                        data: 'device_id'
                    },
                    {
                        data: 'serial_number'
                    },
                    {
                        data: 'license_key'
                    },
                    {
                        data: 'start_date'
                    },
                    {
                        data: 'end_date'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'desc']
                ], // sort by device_id
                language: {
                    emptyTable: "No records found"
                },
                drawCallback: function(settings) {
                    // Re-init tooltips or bindings
                    $('[data-bs-toggle="tooltip"]').tooltip();
                },
                columnDefs: [{
                        targets: [0, 6],
                        className: 'text-center'
                    } // center align checkbox + actions
                ]
            });


            // delegate view button clicks (if your server returns anchors or data-url)
            $(document).on('click', '.btn-view-row', function(e) {
                e.preventDefault();
                var url = $(this).data('url') || $(this).attr('href');
                if (url) viewRowDetails(url);
            });

            // viewRowDetails (keeps your previous behavior)
            window.viewRowDetails = function(url) {
                $.ajax(url, {
                        dataType: 'json'
                    })
                    .done(function(resp) {
                        if (resp.data) {
                            var d = resp.data;
                            $('#viewRowDetails #row-title').text(d.device_id || '-');
                            $('#viewRowDetails #device_id').text(d.device_id || '-');
                            $('#viewRowDetails #serial_number').text(d.serial_number || '-');
                            $('#viewRowDetails #license_key').text(d.license_key || '-');
                            $('#viewRowDetails #start_date').text(d.start_date || '-');
                            $('#viewRowDetails #end_date').text(d.end_date || '-');
                            var s = (d.status == 'ACTIVE') ?
                                '<span class="badge bg-label-success">ACTIVE</span>' : (d.status ==
                                    'INACTIVE' ? '<span class="badge bg-label-secondary">INACTIVE</span>' :
                                    '<span class="badge bg-label-danger">EXPIRE</span>');
                            $('#viewRowDetails #status').html(s);
                            var modal = new bootstrap.Modal(document.getElementById('viewRowDetails'));
                            modal.show();
                        } else {
                            toastr.error('Failed to fetch data');
                        }
                    })
                    .fail(function() {
                        toastr.error('Failed to fetch data');
                    });
            };
        });



        function onDelete(row_id) {

            if (!row_id || row_id === 0) {
                alert('No items selected for deletion.');
                return;
            }

            if (!confirm('Are you sure you want to delete? This action cannot be undone.')) {
                return;
            }
            let delete_url = "{{ route('device_registration.delete') }}" + '/' + row_id;
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: delete_url,
                method: 'POST',
                data: {
                    ids: row_id
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
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
    </script>
@endsection
