@extends('admin.layouts.master')
@section('title', 'Permissions')
@section('content')
    <section>
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">User Management / Roles /</span> Permissions</h4>
        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="table" id="role-datatable">
                    <thead>
                        <tr>
                            <th class="not_include"></th>
                            <th>Sr.No</th>
                            <th>Name</th>
                            <th class="not_include">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Add Permission Modal -->
        <div class="modal fade" id="addPermissionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Add New Permission</h3>
                            <p class="text-muted">Permissions you may use and assign to your users.</p>
                        </div>
                        <form id="addPermissionForm" class="row" onsubmit="return false">
                            @csrf
                            <div class="col-12 mb-3">
                                <label class="form-label" for="name">Permission Name</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    placeholder="Permission Name" autofocus />
                            </div>
                            <div class="col-12 text-center demo-vertical-spacing">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Create Permission</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                    aria-label="Close">
                                    Discard
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Add Permission Modal -->

        <!-- Edit Permission Modal -->
        <div class="modal fade" id="editPermissionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Edit Permission</h3>
                            <p class="text-muted">Edit permission as per your requirements.</p>
                        </div>
                        <div class="alert alert-warning" role="alert">
                            <h6 class="alert-heading mb-2">Warning</h6>
                            <p class="mb-0">
                                By editing the permission name, you might break the system permissions functionality. Please
                                ensure you're absolutely certain before proceeding.
                            </p>
                        </div>
                        <form id="editPermissionForm" class="row" onsubmit="return false">
                            @csrf
                            @method('PUT')
                            <div class="col-12 mb-3">
                                <label class="form-label" for="editPermissionName">Permission Name</label>
                                <input type="text" id="editPermissionName" name="name" class="form-control"
                                    placeholder="Permission Name" autofocus />
                            </div>
                            <div class="col-12 text-center demo-vertical-spacing">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Update Permission</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                    aria-label="Close">
                                    Discard
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Edit Permission Modal -->
    </section>
@endsection
@section('script')
    <script>
        var rowId;
        $(document).ready(function() {
            dataTable = $('#role-datatable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ordering: false,
                ajax: "{{ route('permissions.index') }}",
                columns: [{
                        data: ''
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: ''
                    }
                ],
                columnDefs: [{
                        // For Responsive
                        className: 'control',
                        orderable: false,
                        searchable: false,
                        responsivePriority: 2,
                        targets: 0,
                        render: function(data, type, full, meta) {
                            return '';
                        }
                    },
                    {
                        // Actions
                        targets: -1,
                        title: 'Actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, full, meta) {
                            return (
                                '<a href="javascript:;" class="btn btn-sm btn-icon item-edit" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Edit" onclick="editPermission('+full.id+',`' +
                            full.name +
                            '`)"><i class="text-primary ti ti-pencil"></i></a>' +
                                '<a href="javascript:;" class="btn btn-sm btn-icon item-edit" onclick="deletePermission('+full.id+')"><i class="text-danger ti ti-trash"></i></a>'
                            );
                        }
                    },
                    {
                        "defaultContent": "-",
                        "targets": "_all"
                    }
                ],
                order: [
                    [2, 'desc']
                ],
                dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 7,
                lengthMenu: [7, 10, 25, 50, 75, 100],
                buttons: [{
                        extend: 'collection',
                        className: 'btn btn-label-primary dropdown-toggle me-2 waves-effect waves-light',
                        text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                        buttons: [{
                                extend: 'print',
                                text: '<i class="ti ti-printer me-1" ></i>Print',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                },
                                customize: function(win) {
                                    //customize print view for dark
                                    $(win.document.body)
                                        .css('color', config.colors.headingColor)
                                        .css('border-color', config.colors.borderColor)
                                        .css('background-color', config.colors.bodyBg);
                                    $(win.document.body)
                                        .find('table')
                                        .addClass('compact')
                                        .css('color', 'inherit')
                                        .css('border-color', 'inherit')
                                        .css('background-color', 'inherit');
                                }
                            },
                            {
                                extend: 'csv',
                                text: '<i class="ti ti-file-text me-1" ></i>Csv',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="ti ti-file-description me-1"></i>Pdf',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                }
                            },
                            {
                                extend: 'copy',
                                text: '<i class="ti ti-copy me-1" ></i>Copy',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                }
                            }
                        ]
                    },
                    {
                        text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New</span>',
                        className: 'create-new btn btn-primary waves-effect waves-light',
                        attr: {
                            'data-bs-toggle': 'modal',
                            'data-bs-target': '#addPermissionModal'
                        },
                    },
                    {
                        text: '<i class="ti ti-list-details me-sm-1"></i> <span class="d-none d-sm-inline-block">Roles</span>',
                        className: 'create-new btn btn-primary waves-effect waves-light',
                        action: function(e, dt, node, config) {
                            window.location.href = '{{ route('roles.index') }}';
                        },
                    }
                ],
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function(row) {
                                var data = row.data();
                                return 'Details of ' + data['full_name'];
                            }
                        }),
                        type: 'column',
                        renderer: function(api, rowIdx, columns) {
                            var data = $.map(columns, function(col, i) {
                                return col.title !==
                                    '' // ? Do not show row in modal popup if title is blank (for check box)
                                    ?
                                    '<tr data-dt-row="' +
                                    col.rowIndex +
                                    '" data-dt-column="' +
                                    col.columnIndex +
                                    '">' +
                                    '<td>' +
                                    col.title +
                                    ':' +
                                    '</td> ' +
                                    '<td>' +
                                    col.data +
                                    '</td>' +
                                    '</tr>' :
                                    '';
                            }).join('');

                            return data ? $('<table class="table"/><tbody />').append(data) : false;
                        }
                    }
                }
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Permissions</h5>');

            // Add Permission
            $('#addPermissionForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('permissions.store') }}",
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    responsive: true,
                    success: function(response) {
                        $('#addPermissionForm')[0].reset();
                        $("#addPermissionModal").modal("hide");
                        dataTable.ajax.reload();
                        ShowToast('success', response.message);
                    },
                    error: function(jqXHR, exception) {
                        if (jqXHR.status == 422) {
                            $.each(jqXHR.responseJSON.errors, function(index, value) {
                                ShowToast('error', value);
                            });
                        } else {
                            ShowToast('error','An error occured! Please Contact Administrator.');
                        }
                    }
                });
            });

            // Update Permission
            $('#editPermissionForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ url('permissions') }}" + "/" + rowId,
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    responsive: true,
                    success: function(response) {
                        $('#editPermissionForm')[0].reset();
                        $("#editPermissionModal").modal("hide");
                        dataTable.ajax.reload();
                        ShowToast('success', response.message);
                    },
                    error: function(jqXHR, exception) {
                        if (jqXHR.status == 422) {
                            $.each(jqXHR.responseJSON.errors, function(index, value) {
                                ShowToast('error', value);
                            });
                        } else {
                            ShowToast('error','An error occured! Please Contact Administrator.');
                        }
                    }
                });
            });
        });

        function editPermission(id,name) {
            rowId = id;
            $('#editPermissionName').val(name);
            $('#editPermissionModal').modal('show');
        }

        function deletePermission(id){
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                customClass: {
                    confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
                    cancelButton: 'btn btn-label-secondary waves-effect waves-light'
                },
                buttonsStyling: false
            }).then(function (result) {
                if (result.value) {
                    $.ajax({
                        url: "permissions/" + id,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            dataTable.ajax.reload();
                            ShowToast('success', response.message);
                        },
                        error: function(jqXHR, exception) {
                            ShowToast('error','An error occured! Please Contact Administrator.');
                        }
                    });
                }
            });
        }
    </script>
@endsection
