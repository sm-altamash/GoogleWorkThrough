@extends('admin.layouts.master')
@section('title', 'Users')
@section('content')
    <section>
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Nadra /</span> Imports</h4>
        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="table" id="role-datatable">
                    <thead>
                    <tr>
                        <th class="not_include"></th>
                        <th>No</th>
                        <th>Full Name</th>
                        <th>Father Name</th>
                        <th>Gender</th>
                        <th>DOB</th>
                        <th>CNIC</th>
                        <th>Family ID</th>
                        <th>Addresses</th>
                        <th>Province</th>
                        <th>District</th>
                        <th>Category</th>
                        <th class="not_include">Actions</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Add Modal -->
        <div class="modal fade" id="addNadraModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Upload New File</h3>
                            <p class="text-muted">Add new data in system.</p>
                        </div>
                        <form class="row" id="addNadraform" method="POST" action="{{ route('nadra.import') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="col-12 col-md-6 mb-3">
                                <label for="category" class="form-label">Category (Year) <span class="text-danger">*</span></label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    @for($year = 2020; $year <= date('Y'); $year++)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="excel_file" class="form-label">Choose Excel file (.xlsx/.xls) <span class="text-danger">*</span></label>
                                <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                            </div>
                            <div class="col-12 text-center demo-vertical-spacing">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Upload File</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">
                                    Discard
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editNadraModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Edit Record</h3>
                            <p class="text-muted">Update existing record in system.</p>
                        </div>
                        <form class="row" id="editNadraform" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="edit_record_id" name="record_id">

                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_father_name" class="form-label">Father Name</label>
                                <input type="text" class="form-control" id="edit_father_name" name="father_name" required>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_gender" class="form-label">Gender</label>
                                <select class="form-control" id="edit_gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth" required>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_cnic_number" class="form-label">CNIC Number</label>
                                <input type="text" class="form-control" id="edit_cnic_number" name="cnic_number" placeholder="12345-1234567-1" required>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_family_id" class="form-label">Family ID</label>
                                <input type="text" class="form-control" id="edit_family_id" name="family_id">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="edit_addresses" class="form-label">Addresses</label>
                                <textarea class="form-control" id="edit_addresses" name="addresses" rows="3"></textarea>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_province" class="form-label">Province</label>
                                <input type="text" class="form-control" id="edit_province" name="province">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_district" class="form-label">District</label>
                                <input type="text" class="form-control" id="edit_district" name="district">
                            </div>
                            <div class="col-12 text-center demo-vertical-spacing">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Update Record</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Details Modal -->
        <div class="modal fade" id="fileDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Uploaded Files Details</h3>
                            <p class="text-muted">View all uploaded files and their data.</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Uploaded Files</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="filesList" class="list-group">
                                            <!-- Files will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">File Data</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="fileDataContainer">
                                            <div class="text-center text-muted">
                                                <i class="ti ti-file-search" style="font-size: 3rem;"></i>
                                                <p>Select a file to view its data</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
@section('script')
    <script>
$(document).ready(function() {
    var dataTable;

    // Initialize DataTable
    dataTable = $('#role-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ordering: false,
        ajax: {
            url: "{{ route('nadra.index') }}",
            error: function(xhr, error, code) {
                console.log('Ajax error:', xhr.responseText);
                alert('Error loading data. Check console for details.');
            }
        },
        columns: [
            {
                data: null,
                defaultContent: '',
                className: 'control',
                orderable: false,
                searchable: false
            },
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                searchable: false,
                orderable: false
            },
            {
                data: 'full_name',
                name: 'full_name'
            },
            {
                data: 'father_name',
                name: 'father_name'
            },
            {
                data: 'gender',
                name: 'gender'
            },
            {
                data: 'date_of_birth',
                name: 'date_of_birth'
            },
            {
                data: 'cnic_number',
                name: 'cnic_number'
            },
            {
                data: 'family_id',
                name: 'family_id'
            },
            {
                data: 'addresses',
                name: 'addresses'
            },
            {
                data: 'province',
                name: 'province'
            },
            {
                data: 'district',
                name: 'district'
            },
            {
                data: 'category',
                name: 'category'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        columnDefs: [
            {
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
                "defaultContent": "-",
                "targets": "_all"
            }
        ],
        order: [
            [2, 'desc']
        ],
        dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        displayLength: 5,
        lengthMenu: [5, 10, 25, 50, 75, 100],
        buttons: [
            {
                extend: 'collection',
                className: 'btn btn-label-primary dropdown-toggle me-2 waves-effect waves-light',
                text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                buttons: [
                    {
                        extend: 'print',
                        text: '<i class="ti ti-printer me-1" ></i>Print',
                        className: 'dropdown-item',
                        exportOptions: {
                            columns: ':not(.not_include)',
                        }
                    },
                    {
                        extend: 'csv',
                        text: '<i class="ti ti-file-text me-1" ></i>Csv',
                        className: 'dropdown-item',
                        exportOptions: {
                            columns: ':not(.not_include)',
                        }
                    },
                    {
                        extend: 'excel',
                        text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
                        className: 'dropdown-item',
                        exportOptions: {
                            columns: ':not(.not_include)',
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="ti ti-file-description me-1"></i>Pdf',
                        className: 'dropdown-item',
                        exportOptions: {
                            columns: ':not(.not_include)',
                        }
                    },
                    {
                        extend: 'copy',
                        text: '<i class="ti ti-copy me-1" ></i>Copy',
                        className: 'dropdown-item',
                        exportOptions: {
                            columns: ':not(.not_include)',
                        }
                    }
                ]
            },
            {
                text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New</span>',
                className: 'create-new btn btn-primary waves-effect waves-light',
                attr: {
                    'data-bs-toggle': 'modal',
                    'data-bs-target': '#addNadraModal'
                }
            },
            {
                text: '<i class="ti ti-file-search me-sm-1"></i> <span class="d-none d-sm-inline-block">Show Details</span>',
                className: 'show-details btn btn-info waves-effect waves-light',
                attr: {
                    'data-bs-toggle': 'modal',
                    'data-bs-target': '#fileDetailsModal'
                }
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
                        return col.title !== '' ?
                            '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                            '<td>' + col.title + ':</td> ' +
                            '<td>' + col.data + '</td>' +
                            '</tr>' : '';
                    }).join('');
                    return data ? $('<table class="table"/><tbody />').append(data) : false;
                }
            }
        }
    });

    $('div.head-label').html('<h5 class="card-title mb-0">Nadra Records</h5>');

    // Load files when show details modal is opened
    $('#fileDetailsModal').on('show.bs.modal', function() {
        loadUploadedFiles();
    });

    // Load uploaded files
    function loadUploadedFiles() {
        $.ajax({
            url: "{{ route('nadra.uploaded-files') }}",
            type: "GET",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                var filesList = $('#filesList');
                filesList.empty();

                if (response.length > 0) {
                    response.forEach(function(file) {
                        var uploadedDate = new Date(file.uploaded_at).toLocaleDateString();
                        var fileItem = `
                            <a href="#" class="list-group-item list-group-item-action file-item" data-file-id="${file.id}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${file.original_filename}</h6>
                                    <small class="text-muted">${uploadedDate}</small>
                                </div>
                                <p class="mb-1">Category: ${file.category}</p>
                                <small class="text-muted">Records: ${file.total_records}</small>
                            </a>
                        `;
                        filesList.append(fileItem);
                    });
                } else {
                    filesList.html('<p class="text-muted text-center">No files uploaded yet.</p>');
                }
            },
            error: function(jqXHR, exception) {
                console.log('Files load error:', jqXHR.responseText);
                $('#filesList').html('<p class="text-danger text-center">Error loading files.</p>');
            }
        });
    }

    // Handle file item click
    $(document).on('click', '.file-item', function(e) {
        e.preventDefault();
        var fileId = $(this).data('file-id');
        
        // Remove active class from all items
        $('.file-item').removeClass('active');
        // Add active class to clicked item
        $(this).addClass('active');
        
        loadFileData(fileId);
    });

    // Load file data
    function loadFileData(fileId) {
        $('#fileDataContainer').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        $.ajax({
            url: "{{ route('nadra.file-data', '') }}/" + fileId,
            type: "GET",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                var fileInfo = response.file;
                var records = response.records;
                var uploadedDate = new Date(fileInfo.uploaded_at).toLocaleDateString();
                
                var html = `
                    <div class="mb-3">
                        <h6>File Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Filename:</strong> ${fileInfo.original_filename}</p>
                                <p><strong>Category:</strong> ${fileInfo.category}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Uploaded:</strong> ${uploadedDate}</p>
                                <p><strong>Total Records:</strong> ${fileInfo.total_records}</p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Father Name</th>
                                    <th>Gender</th>
                                    <th>DOB</th>
                                    <th>CNIC</th>
                                    <th>Province</th>
                                    <th>District</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                records.forEach(function(record) {
                    html += `
                        <tr>
                            <td>${record.full_name || '-'}</td>
                            <td>${record.father_name || '-'}</td>
                            <td>${record.gender || '-'}</td>
                            <td>${record.date_of_birth || '-'}</td>
                            <td>${record.cnic_number || '-'}</td>
                            <td>${record.province || '-'}</td>
                            <td>${record.district || '-'}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                $('#fileDataContainer').html(html);
            },
            error: function(jqXHR, exception) {
                console.log('File data load error:', jqXHR.responseText);
                $('#fileDataContainer').html('<p class="text-danger text-center">Error loading file data.</p>');
            }
        });
    }

    // Edit Form Handler
    $('#editNadraform').submit(function(e) {
        e.preventDefault();

        var recordId = $('#edit_record_id').val();
        var formData = $(this).serialize();

        $.ajax({
            url: "{{ route('nadra.update', '') }}/" + recordId,
            type: "PUT",
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#editNadraform')[0].reset();
                $("#editNadraModal").modal("hide");
                dataTable.ajax.reload();

                if (typeof ShowToast === 'function') {
                    ShowToast('success', response.message || 'Record updated successfully');
                } else {
                    alert('Record updated successfully');
                }
            },
            error: function(jqXHR, exception) {
                console.log('Update error:', jqXHR.responseText);

                if (jqXHR.status == 422) {
                    var errors = jqXHR.responseJSON.errors;
                    $.each(errors, function(index, value) {
                        if (typeof ShowToast === 'function') {
                            ShowToast('error', value[0]);
                        } else {
                            alert('Error: ' + value[0]);
                        }
                    });
                } else {
                    var message = 'An error occurred! Please contact administrator.';
                    if (typeof ShowToast === 'function') {
                        ShowToast('error', message);
                    } else {
                        alert(message);
                    }
                }
            }
        });
    });

    // Edit Button Click Handler
    $(document).on('click', '.edit-btn', function() {
        var recordId = $(this).data('id');

        // Clear form first
        $('#editNadraform')[0].reset();

        // Fetch record data
        $.ajax({
            url: "{{ route('nadra.edit', '') }}/" + recordId,
            type: "GET",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Edit response:', response);

                $('#edit_record_id').val(response.id);
                $('#edit_full_name').val(response.full_name);
                $('#edit_father_name').val(response.father_name);
                $('#edit_gender').val(response.gender);
                $('#edit_date_of_birth').val(response.date_of_birth);
                $('#edit_cnic_number').val(response.cnic_number);
                $('#edit_family_id').val(response.family_id);
                $('#edit_addresses').val(response.addresses);
                $('#edit_province').val(response.province);
                $('#edit_district').val(response.district);
            },
            error: function(jqXHR, exception) {
                console.log('Edit fetch error:', jqXHR.responseText);
                if (typeof ShowToast === 'function') {
                    ShowToast('error', 'Error fetching record data');
                } else {
                    alert('Error fetching record data');
                }
            }
        });
    });

    // Delete Button Click Handler
    $(document).on('click', '.delete-btn', function() {
        var recordId = $(this).data('id');

        if (confirm('Are you sure you want to delete this record?')) {
            $.ajax({
                url: "{{ route('nadra.destroy', '') }}/" + recordId,
                type: "DELETE",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    dataTable.ajax.reload();
                    if (typeof ShowToast === 'function') {
                        ShowToast('success', response.message || 'Record deleted successfully');
                    } else {
                        alert('Record deleted successfully');
                    }
                },
                error: function(jqXHR, exception) {
                    console.log('Delete error:', jqXHR.responseText);
                    if (typeof ShowToast === 'function') {
                        ShowToast('error', 'Error deleting record');
                    } else {
                        alert('Error deleting record');
                    }
                }
            });
        }
    });
});
</script>
@endsection
